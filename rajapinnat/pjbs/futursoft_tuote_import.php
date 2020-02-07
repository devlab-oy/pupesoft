<?php

/* ****************************************************************************** **
  Jdbc väylä on sen verran hidas että tätä konversiota varten on dump/loadattu
  tuotper ja tuotetyyppiriv taulut soliddb:stä mysqliin Pupekantaan.

  Itse datan käsittely tehdään siten että ei ole väliä otetaanko rivit mysqlistä
  vai jpbs:n läpi soliddb:stä.

  Tauludumpit löytyy dropboxista.
  -Henkka
** ****************************************************************************** */




// Tehdään SSH-tunneli Örskan palvelimelle
// ssh -L 4444:193.185.248.70:4444 -N devlab@193.185.248.70

require_once 'PJBS.php';

// pupe ui
if (!isset($require)) {

  $pupe_root_polku = dirname(dirname(__FILE__));
  //$pupe_root_polku = "/Users/tony/Sites/pupesoft";

  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  // error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
  // ini_set("display_errors", 0);
  //ini_set("memory_limit", "6G");

  // Pupetti includet
  require "../../inc/connect.inc";
  require "../../inc/functions.inc";

}

/*
  main app
*/

//$logfile_handle;

//init_logger('/Users/henri/futursoft_tuote_import.log');

$yhtio = 'atarv';
$laatija = 'konversio';

$debug = false;

if (!function_exists("futurmuunnos_tuote")) {
  function futurmuunnos_tuote($toimittaja, $jotain) {
    $valmis = $toimittaja != '3' ? $jotain.'-'.$toimittaja : $jotain;
    return $valmis;
  }


}
if (!function_exists("futurmuunnos")) {
  function futurmuunnos($toimittaja, $jotain) {
    $valmis = $toimittaja != '3' ? $toimittaja.'-'.$jotain : $jotain;
    return $valmis;
  }


}

/*
  main app
*/
// Tarvitaan yhteys futuriin try ja osastoa varten
$drv = new PJBS('UTF-8', 'UTF-8');
//$con = $drv->connect('jdbc:solid://mergs014:2000/pupesoft/pupesoft', 'pupesoft', 'pupesoft');
$con = $drv->connect('jdbc:solid://palvelin1:2100/pupesoft/mG1289R!', 'pupesoft', 'mG1289R!');

echo "\nlaitetaan tuoteryhmä arraynä muuttujaan, jotta voidaan sitä sieltä käytttää\n";
$res = $drv->exec("SELECT * FROM TUOTRYHM"); // tuoteryhmän tietoja
// $res = $drv->exec("SELECT * FROM TUOTRYHM where TUR_TOIMITTAJANRO = 3 and TUR_KOODI = '108195' and TUR_TYYPPI like 'ALENNUS%'"); // tuoteryhmän tietoja
while ($row = $drv->fetch_array($res)) {

  if (trim($row['TUR_KOODI']) == '') continue;
  $try = substr(futurmuunnos($row['TUR_TOIMITTAJANRO'], $row['TUR_KOODI']), 0, 15);
  $tuotryhm[$try]['osasto'] = $row['TUR_OSASTO_YLEINEN'];
  $row['TUR_NIMI'] = pupesoft_cleanstring($row['TUR_NIMI']);

  if (strstr($row['TUR_TYYPPI'], 'SEURANTA') != "") {
    $tuotryhm[$try]['nimi'] = $row['TUR_NIMI'];
  }
  else {
    $tuotryhm[$try]['nimi'] = '';
  }

  $tryselitetark = $row['TUR_KOODI'] . " - " . $row['TUR_NIMI'];

  if (strstr($row['TUR_TYYPPI'], 'ALENNUS') != "") {
    $query = "SELECT *
              FROM perusalennus use index (yhtio_ryhma)
              WHERE yhtio = '$yhtio'
              AND ryhma   = '$try'";
    $result = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($result) == 0) {
      $query = "INSERT INTO perusalennus SET
                yhtio      = '$yhtio',
                ryhma      = '$try',
                selite     = '$row[TUR_NIMI]',
                alennus    = '0',
                laatija    = '$laatija',
                luontiaika = now(),
                muuttaja   = '$laatija',
                muutospvm  = now()";
      $result = mysql_query($query) or pupe_error($query);
    }
  }
  if (strstr($row['TUR_TYYPPI'], 'SEURANTA') != "") {
    $query = "SELECT *
              FROM avainsana
              WHERE yhtio    = '$yhtio'
              AND laji       = 'TRY'
              AND selitetark = '$tryselitetark'
              AND kieli      = 'fi'";
    $result = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($result) == 0) {

      $query = "SELECT count(*) + 1 kpl
                FROM avainsana
                WHERE yhtio = '$yhtio'
                AND laji    = 'TRY'
                AND kieli   = 'fi'";
      $result2 = mysql_query($query) or pupe_error($query);
      $tryselite = mysql_fetch_assoc($result2);

      $query = "INSERT INTO avainsana SET
                kieli      = 'fi',
                jarjestys  = '0',
                selite     = '$tryselite[kpl]',
                selitetark = '$tryselitetark',
                laji       = 'TRY',
                yhtio      = '$yhtio',
                laatija    = '$laatija',
                luontiaika = now(),
                muuttaja   = '$laatija',
                muutospvm  = now()";
      $result = mysql_query($query) or pupe_error($query);
    }

    $query = "SELECT *
              FROM avainsana use index (yhtio_laji_selite)
              WHERE yhtio = '$yhtio'
              AND laji    = 'OSASTO'
              AND selite  = '$row[TUR_OSASTO_YLEINEN]'
              AND kieli   = 'fi'";
    $result = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($result) == 0) {
      $query = "INSERT INTO avainsana SET
                kieli      = 'fi',
                jarjestys  = '0',
                selite     = '$row[TUR_OSASTO_YLEINEN]',
                selitetark = '',
                laji       = 'OSASTO',
                yhtio      = '$yhtio',
                laatija    = '$laatija',
                luontiaika = now(),
                muuttaja   = '$laatija',
                muutospvm  = now()";
      $result = mysql_query($query) or pupe_error($query);
    }
  }
}

//print_r($tuotryhm);

//echo "try - nimi: " . $row['TUR_KOODI'] . " - " . $row['TUR_NIMI'] . " ja osasto:" . $row['TUR_OSASTO_YLEINEN'] . "\n";

$toimi_cache = preloadaa_toimittajatiedot();



echo "\nTuotteiden pääkonversio samasta db:stä futur_tuotper taulusta\n";
$qu = "SELECT * FROM futur_tuotper2 WHERE TP_TOIMITTAJANRO in ('2','6','16','170')";

$re = pupe_query($qu);

echo "\nkasittele_tuotper\n";
while ($row = mysql_fetch_assoc($re)) {
  kasittele_tuotper($row);
}


/*
  tuotteiden päivitykset jpbs:llä
*/
/*
$date_str = date('Y-m-d', time() - 60 * 60 * 24);

# utf on väärin näissä, täytyy selvittää mitä soliddb:lle pitää kertoa tässä kohtaa

/*
$res = $drv->exec("SELECT * FROM TUOTPER WHERE TP_PAIVITETTY > '$date_str'"); // päivitetty hinnastotyökaluilla
# $res = $drv->exec("SELECT * FROM TUOTPER WHERE TP_MUUTETTUKASIN > '$date_str'"); // päivitetty käsin... TP_PAIVITETTY voi olla pienempi kuin TP_MUUTETTUKASIN

while ($row = $drv->fetch_array($res)) {
  $ok = kasittele_tuotper($row);
}

$drv->free_result();
*/

/*
echo "\nVastaavat -ketjujen pääkonversio samasta db:stä futur_tuotettyypriv taulusta\n";
$qu = "SELECT * FROM futur_tuotetyyppiriv ORDER BY ttr_tyyppikoodi";

$re = pupe_query($qu);

echo "\nkerätään saman tuoteketjun rivit arrayhin ja heitetään koko nippu käsiteltäväksi\n";
$nippu = array();

$edellinen_ketju = "";

while($row = mysql_fetch_assoc($re)) {

  # otetaan ketjun eka tuote tässä talteen ja niputetaan $edellinen_ketju avulla rivejä

  if($edellinen_ketju == "") {
    $edellinen_ketju = $row['TTR_TYYPPIKOODI'];
  }

  # jos rivi kuuluu samaan ketjuun kuin edellinen rivi niin laitetaan rivi $nippu muuttujaan ja mennään hakemaan seuraava rivi
  if($row['TTR_TYYPPIKOODI'] == $edellinen_ketju) {

    $nippu[] = $row;
  }
  else {
    # jos rivi on jo seuraavaa ketjua niin heitetään edellinen nippu käsittelyyn

    kasittele_tuotetyyppiriv_nippu($nippu);

    # resetoidaan nippu
    $nippu = array();

    # nimetään niputus apumuuttuja uudelleen ja heitetään käsiteltävä rivi nippuun
    $edellinen_ketju = $row['TTR_TYYPPIKOODI'];
    $nippu[] = $row;
  }
}

# viimeisen nipun käsittely tässä
if(count($nippu) > 0) {
  $ok = kasittele_tuotetyyppiriv_nippu($nippu);
  $nippu = array();
  $edellinen_ketju = "";
}
*/


/*
  funktiot
*/
/*

* *******************
* Tuotepuukonversioita ei tarvitse tehdä Mercan käyttöönotossa!!!!!!!!! -HKa
* *******************
function synkronoi_tuotepuu() {
  global $yhtio;
  # tuotepuun synkronointi
  # Tallennetaan artr-yhtiön dynaaminen_puu.tunnus -> atarv dynaaminen_puu.toimittajan_koodi. Hallitaan tämän avulla päivitystä sekä puun_alkioiden linkitystä tunnukseen.

  $qu = "SELECT
         *
         FROM
         dynaaminen_puu
         WHERE
         yhtio = 'artr' AND
         laji  = 'tuote'
         ";

  $re = pupe_query($qu);

  while($row = mysql_fetch_assoc($re)) {

    # duplikaatti- ja päivitystarkistukset tähän !
    $qu = "SELECT * FROM dynaaminen_puu WHERE yhtio = 'atarv' AND laji = 'tuote' AND toimittajan_koodi = {$row['tunnus']}";
    $dupl = pupe_query($qu);

    if(mysql_num_rows($dupl) == 0) {
      $qu = "INSERT INTO dynaaminen_puu
             SET
               yhtio             = '$yhtio',
               nimi              = '{$row['nimi']}',
               koodi             = {$row['koodi']},
               toimittajan_koodi = {$row['tunnus']},
               lft               = {$row['lft']},
               rgt               = {$row['rgt']},
               laji              = '{$row['laji']}',
               syvyys            = '{$row['syvyys']}',
               laatija           = '{$row['laatija']}',
               luontiaika        = '{$row['luontiaika']}',
               muuttaja          = '{$row['muuttaja']}',
               muutospvm         = '{$row['muutospvm']}'
             ";

      $insert_ok = pupe_query($qu);

    }
    elseif(mysql_num_rows($dupl) == 1) {
      # päivitetään jos löytyy jo rivi
      $upd_row = mysql_fetch_assoc($dupl);

      $update_query = "UPDATE
                       dynaaminen_puu
                       SET
                         yhtio             = '$yhtio',
                         nimi              = '{$row['nimi']}',
                         koodi             = {$row['koodi']},
                         toimittajan_koodi = {$row['tunnus']},
                         lft               = {$row['lft']},
                         rgt               = {$row['rgt']},
                         laji              = '{$row['laji']}',
                         syvyys            = '{$row['syvyys']}',
                         laatija           = '{$row['laatija']}',
                         luontiaika        = '{$row['luontiaika']}',
                         muuttaja          = '{$row['muuttaja']}',
                         muutospvm         = '{$row['muutospvm']}'
                       WHERE
                         tunnus            = {$upd_row['tunnus']}";

      $update_ok = pupe_query($update_query);

    }
    else {
      # no nyt on jännää....
    }

    # poistettujen rivien heivaus
  }

  return true;
}

function synkronoi_tuotepuun_liitokset() {

  # otetaan örum -tuotteiden liitokset artr yhtiöstä, sen jälkeen päivitetään vastaavuusketjun avulla liitokset ketjujen muille tuotteille
  # joinataan tähän suoraan mukaan uuden puun tunnus jotta ei tarvitse tätä jälkikäteen enää erikseen queryttää
  $qu = "SELECT
         puun_alkio.*, dynaaminen_puu.tunnus uuden_puun_tunnus
         FROM
         puun_alkio
         JOIN dynaaminen_puu ON (
         puun_alkio.yhtio       = dynaaminen_puu.yhtio AND
         puun_alkio.laji        = dynaaminen_puu.laji AND
         puun_alkio.puun_tunnus = dynaaminen_puu.toimittajan_koodi
         )
         WHERE
         puun_alkio.yhtio       = 'artr' AND
         puun_alkio.laji        = 'tuote'
         ";

  $re = pupe_query($qu);

  while($row = mysql_fetch_assoc($re)) {

    # tarkistetaan että tuoterivi löytyy
    $tuote_tarkistus_qu = "SELECT tunnus FROM tuote where yhtio = '$yhtio' AND tuoteno = '{$row['tuoteno']}'";
    $tuote_tarkistus_re = pupe_query($tuote_tarkistus_qu);
    if(mysql_num_rows($tuote_tarkistus_re) == 0) {
      # suottapa tehdään mitään kun ei ole tuotetta, next
      continue;
    }

    # duplikaattitarkistus, sen mukaisesti insert / update
    $dupl_qu = "SELECT
                tunnus
                FROM
                puun_alkio
                WHERE
                yhtio       = '$yhtio' AND
                liitos      = '{$row['liitos']}' AND
                puun_tunnus = '{$row['puun_tunnus']}'
                ";

    $dupl_re = pupe_query($dupl_qu);

    if(mysql_num_rows($dupl_re) == 1) {
      # update

      $update_row = mysql_fetch_assoc($dupl_re);

      $update_qu = "UPDATE puun_alkio
                    SET
                      yhtio       = '$yhtio',
                      liitos      = '{$row['liitos']}',
                      kieli       = '{$row['kieli']}',
                      laji        = '{$row['laji']}',
                      puun_tunnus = '{$row['uuden_puun_tunnus']}'
                      jarjestys   = '{$row['jarjestys']}',
                      laatija     = '{$row['laatija']}',
                      luontiaika  = '{$row['luontiaika']}',
                      muuttaja    = '{$row['muuttaja']}',
                      muutospvm   = '{$row['muutospvm']}'
                    WHERE
                      tunnus      = {$update_row['tunnus']}
                    ";
    }
    elseif(mysql_num_rows($dupl_re) == 0) {
      # insert

      # tehdään uusi liitos
      $insert_qu = "INSERT INTO puun_alkio
                    SET
                      yhtio       = '$yhtio',
                      liitos      = '{$row['liitos']}',
                      kieli       = '{$row['kieli']}',
                      laji        = '{$row['laji']}',
                      puun_tunnus = '{$row['uuden_puun_tunnus']}'
                      jarjestys   = '{$row['jarjestys']}',
                      laatija     = '{$row['laatija']}',
                      luontiaika  = '{$row['luontiaika']}',
                      muuttaja    = '{$row['muuttaja']}',
                      muutospvm   = '{$row['muutospvm']}'
                    ";
      $insert_re = pupe_query($insert_qu);

    }
    else {
      # fail?
    }
  }

  return true;
}

function tuoteketjun_puuliitokset() {
  # synkataan tuoteketjun (vastaavuus) kaikille tuotteille örum -tuotteen puuliitos

  # kysellään puuliitoksien ketju -id ja päivitetään sen avulla

}
*/


function kasittele_tuotper($data) {
  // tuotteen perustiedot

  global $debug, $laatija, $yhtio, $tuotryhm;

  if ($debug) {
    print_r($data);
    return true;
  }

  // datan safetus (kakkelimerkit pois)
  $data = db_safe_data($data);

  // rivin käsittely

  /*
  TUOTENUMERO
    Örumin tuotenumerot on pidettävä samoina kuin artr yhtiössä, futurissa toimittajanro 3
    Muiden toimittajien tuotenumerot muunnetaan TP_TUOTEKOODI + '-' + TP_TOIMITTAJANRO jotta päästään eroon duplikaattituotenumeroista.
  */
  //$tuoteno = $data['TP_TOIMITTAJANRO'] != '3' ? $data['TP_TUOTEKOODI'].'-'.$data['TP_TOIMITTAJANRO'] : $tuoteno = $data['TP_TUOTEKOODI'];

  // tuoteno
  $tuoteno = futurmuunnos_tuote($data['TP_TOIMITTAJANRO'], $data['TP_TUOTEKOODI']);
  // try
  $try = futurmuunnos($data['TP_TOIMITTAJANRO'], $data['TP_RYHMAKOODI']);
  // aleryhma (koivuselle eri sääntö kuin muille)
  if ($data['TP_TOIMITTAJANRO'] == '2') $data['TP_ALEKOODI'] = $data['TP_RYHMAKOODI']."#".$data['TP_ALEKOODI'];
  else $data['TP_ALEKOODI'] = futurmuunnos($data['TP_TOIMITTAJANRO'], $data['TP_ALEKOODI']);

  if (strlen($data['TP_ALEKOODI']) > 14) echo "\n tuotenumero $tuoteno aleryhmä on yli 14 merkkiä pitkä ({$data['TP_ALEKOODI']})\n";

  // nöy nöy nöy, hae try nimitykset sun muut, joil haetaa avainsanoist oikea try selite tuottelle. samal löytyy osasto
  $trynimi = isset($tuotryhm[$try]) ? $tuotryhm[$try]['nimi'] : '';
  $osasto = isset($tuotryhm[$try]) ? $tuotryhm[$try]['osasto'] : '';
  $tryselitetark = $data['TP_RYHMAKOODI'] . " - " . $trynimi;

  /*
  echo "\ntry: " . $try;
  echo "\ntrynimi: " . $trynimi;
  echo "\ntryselitetark: " . $tryselitetark;
  echo "\nosasto: " . $osasto;
  echo "\n";
  die;
  */

  $trybup = $try;  // otetaan tää ja alekoodi varmuuden vuoksi talteen malliin ja mallitarkenteen

  $query = "SELECT *
            FROM avainsana
            WHERE yhtio    = '$yhtio'
            AND laji       = 'TRY'
            AND selitetark = '$tryselitetark'
            AND kieli      = 'fi'";
  $result2 = mysql_query($query) or pupe_error($query);
  if (mysql_num_rows($result2) == 1) {
    $try_row = mysql_fetch_assoc($result2);
    $try = $try_row['selite'];
  }
  else {
    $try = "660066";
  }

  // onko tuoteryhmä olemassa? Luodaan jos ei ole
  // onko aleryhmä olemassa? Luodaan jos ei ole
  // --> nää luodaa asiakhin skriptassa

  // lasketaan myyntihinnasta alv pois
  $myyntihinta = $data['TP_MYYNTIHINTA'] / 1.24;

  // sarakkeet

  $sarakkeet = "yhtio = '$yhtio',
        tuoteno = '$tuoteno',
        nimitys = '{$data['TP_NIMI']}',
        osasto = '$osasto',
        try = '$try',
        kuvaus = '{$data['TP_LISATIETO']}',
        aleryhma = '{$data['TP_ALEKOODI']}',
        malli = '{$trybup}',
        mallitarkenne = '{$data['TP_ALEKOODI']}',
        myyntihinta = '$myyntihinta',
        yksikko = 'KPL',
        hinnastoon = 'K',
        status = 'X',
        eankoodi = '{$data['TP_EANKOODI']}',
        alv = 24,
        muuta = '{$data['TP_TOIMITTAJANRO']}',
        epakurantti25pvm = '0000-00-00',
        epakurantti50pvm = '0000-00-00',
        epakurantti75pvm = '0000-00-00',
        epakurantti100pvm = '0000-00-00'
        ";

  // duplikaattitarkastukset
  $qu = "SELECT
         tuoteno
         FROM
         tuote
         WHERE
         yhtio   = '$yhtio' AND
         tuoteno = '$tuoteno'
         ";

  $re = pupe_query($qu);

  if (mysql_num_rows($re) == 1) {
    // päivitys
    $sarake_lisa = ",muuttaja = '$laatija', muutospvm = now()";

    $qu = "UPDATE tuote SET "
      . $sarakkeet
      . $sarake_lisa
      . " WHERE yhtio = '$yhtio' AND tuoteno = '$tuoteno'";

    pupe_query($qu);

  }
  elseif (mysql_num_rows($re) == 0) {
    // uusi tuote

    $sarake_lisa = ",laatija = '$laatija', luontiaika = now()";

    $qu = "INSERT INTO tuote SET "
      . $sarakkeet
      . $sarake_lisa;

    pupe_query($qu);

  }
  else {
    logger("Tuotenumerolla löytyi monta tuotetta??? Eikai tätä voi edes tapahtua $tuoteno");
    return false;
  }

  luo_tuotteen_toimittajat_rivi($tuoteno, $data);

  return true;
}


$drv->free_result();

function luo_tuotteen_toimittajat_rivi($tuoteno, $data) {
  // tehdään täällä tuotteen toimittajatieto
  global $debug, $laatija, $yhtio, $toimi_cache;

  // etsitään toimittaja toimi_cachesta
  if (isset($toimi_cache[$data['TP_TOIMITTAJANRO']])) {
    $toimi_row = $toimi_cache[$data['TP_TOIMITTAJANRO']];
  }
  else {
    logger("Virhe toimittajan haussa: {$data['TP_TOIMITTAJANRO']}");
    return false;
  }

  // tuotteen_toimittajat sarakkeet
  $sarakkeet = "
    yhtio = '$yhtio',
    tuoteno = '$tuoteno',
    toimittaja = '{$toimi_row['ytunnus']}',
    ostohinta = '{$data['TP_OSTOHINTA']}',
    valuutta = 'EUR',
    osto_alv = 24,
    toim_tuoteno = '{$data['TP_TUOTEKOODI']}',
    toim_nimitys = '{$data['TP_NIMI']}',
    viivakoodi = '{$data['TP_EANKOODI']}',
    alkuperamaa = '{$toimi_row['maa']}',
    jarjestys = 1,
    liitostunnus = '{$toimi_row['tunnus']}'
    ";


  // duplikaattitarkistus
  $qu = "SELECT
         tunnus
         FROM
         tuotteen_toimittajat
         WHERE
         yhtio        = '$yhtio' AND
         tuoteno      = '$tuoteno' AND
         liitostunnus = '{$toimi_row['tunnus']}'
         ";

  $re = pupe_query($qu);

  if (mysql_num_rows($re) == 0) {
    // luodaan uusi
    $sarake_lisa = ", laatija = '$laatija', luontiaika = now()";

    $qu = "INSERT INTO tuotteen_toimittajat SET "
      . $sarakkeet
      . $sarake_lisa;

    pupe_query($qu);

  }
  elseif (mysql_num_rows($re) == 1) {
    $row = mysql_fetch_assoc($re);

    $sarake_lisa = ", muutospvm = now(), muuttaja = '$laatija'";

    $qu = "UPDATE tuotteen_toimittajat SET "
      . $sarakkeet
      . $sarake_lisa
      . " WHERE tunnus = {$row['tunnus']}
                          ";
    pupe_query($qu);
  }
  else {
    // eikait tänne voi joutua
    logger("liikaa tuotteen toimittajia: {$data['TP_TUOTEKOODI']}");
    return false;
  }

  return true;
}


function kasittele_tuotetyyppiriv_nippu($data) {
  global $yhtio, $laatija;
  // käsittelee tuotetyyppirivi -nipun (eli yhden ketjun kaikki rivit)


  // haetaan ketjun tunnus, jos ei löydy niin tehdään uusi

  // ja itseasiassa nyt toistaiseksi oletetaan tyhmänä että ketjua ei löydy jotta saadaan suorituskykyä nostettua
  // datasta siivotaan duplikaatit ja puuttuvat tuotteet

  $qu = "SELECT
         max(id)+1 id
         FROM
         vastaavat
         WHERE
         yhtio = '$yhtio'";

  $re = pupe_query($qu);

  $res_row = mysql_fetch_assoc($re);

  // ensimmäisen ketjun id on ykkönen, muutoin otetaan se mitä kyselyllä saadaan
  $new_id = $res_row['id'] != '' ? $res_row['id'] : 1;

  // tehdään nipun riveistä sopivia stringejä INSERT lauseelle
  $insert_values = array();

  foreach ($data as $row) {
    $row = db_safe_data($row);

    // tehdään tuotenumeromuunnokset
    // $tuoteno = $row['TTR_TOIMITTAJANRO'] != '3' ? $row['TTR_TUOTEKOODI'].'-'.$row['TTR_TOIMITTAJANRO'] : $row['TTR_TUOTEKOODI'];
    // tuoteno
    $tuoteno = futurmuunnos_tuote($row['TTR_TOIMITTAJANRO'], $row['TTR_TUOTEKOODI']);


    $insert_values[] = "('$yhtio', $new_id, '$tuoteno', '{$row['TTR_TILAUSPRI']}')";
  }


  // rusautetaan rivit kantaan
  $qu = "INSERT INTO vastaavat (yhtio, id, tuoteno, jarjestys) values ".implode(",", $insert_values);

  $re = pupe_query($qu);
}


function db_safe_data($data) {
  // escapoidaan arrayn arvot
  foreach ($data as $key => $row) {
    $data[$key] = mysql_real_escape_string($row);
  }

  return $data;
}


function preloadaa_toimittajatiedot() {
  global $yhtio;
  // napataan toimittajat arrayhin niin saadaan toimittajan tuotteiden luonti performoimaan kivasti

  // toimittajanumero on tavaratoimittajalla pakko olla, ilman sitä ei osata perustaa tuotteen toimittajatietoja
  $qu = "SELECT
         ytunnus,
         maa,
         tunnus,
         toimittajanro
         FROM
         toimi
         WHERE
         yhtio          = '$yhtio' AND
         tyyppi         = '' AND
         toimittajanro != ''";

  $re = pupe_query($qu);

  while ($row = mysql_fetch_assoc($re)) {

    if (isset($data[$row['toimittajanro']])) {
      // kuollaan tähän, mielummin datan korjaus kuin handlaus
      echo "<pre>";
      print_r($data);
      echo "</pre>";
      logger("Duplikaattitoimittajanumero: {$row['toimittajanro']} {$row['nimi']}");
      die;
    }

    $data[$row['toimittajanro']] = $row;
  }

  // array key = toimittajanumero
  return $data;
}


function init_logger($path = null) {
  global $logfile_handle;

  if ($path == null) {
    $path = 'c:/xampp/tmp/log/futur_tuote_import.log';
  }

  //$logfile_handle = fopen($path, 'w');

  //fwrite($logfile_handle, "Script started\n");

  return true;
}


function logger($data) {
  global $logfile_handle, $debug;

  if (true) {
    echo $data."\n";
  }

  //fwrite($logfile_handle, date('d-m-y H:i:s').": ".$data."\n");

  return true;
}
