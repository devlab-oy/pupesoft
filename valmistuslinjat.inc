<?php

/**
 * Tiedosto sisältää funktioita tuotannonsuunnitteluun valmistuslinjojen käsittelyyn.
 */

/** Päivitää valmistuksen tilaa (lasku.valmistuksen_tila)
 */
function paivita_valmistus($tunnus, $tila) {
  global $kukarow;

  #echo "päivitetään valmistus $tunnus tila $tila";

  if (empty($tunnus) or empty($tila)) {
    exit("Virhe valmistuksen päivityksessä");
  }
  $query = "UPDATE lasku SET valmistuksen_tila='{$tila}' WHERE yhtio='{$kukarow['yhtio']}' AND tunnus='{$tunnus}'";
  $result = pupe_query($query) or exit("Virhe tilan päivityksessä".mysql_error());

  return $result;
}

function hae_valmistuslinjan_valmistukset($linja) {
  global $kukarow;

  // Haetaan linjalla olevat valmistukset
  $query = "SELECT *
            FROM kalenteri
            JOIN lasku on (kalenteri.yhtio=lasku.yhtio AND kalenteri.otunnus=lasku.tunnus)
            WHERE kalenteri.yhtio='{$kukarow['yhtio']}'
            AND henkilo='{$linja['id']}'
            AND lasku.valmistuksen_tila not in ('VT', 'TA')";
  $result = pupe_query($query);

  $valmistuslinjan_tuotteet = array();
  while ($valmiste = mysql_fetch_assoc($result)) {
    $valmistuslinjan_tuotteet[] = $valmiste;
  }

  return $valmistuslinjan_tuotteet;
}

function hae_valmistuksen_tuotteet($valmistus) {
  global $kukarow;

  $query = "SELECT *
            FROM tilausrivi
            WHERE yhtio='{$kukarow['yhtio']}'
            AND otunnus='{$valmistus['otunnus']}'
            AND tyyppi='W'";
  $result = pupe_query($query);

  $tuotteet = array();
  while ($tuote = mysql_fetch_assoc($result)) {
    $tuotteet[] = $tuote;
  }

  return $tuotteet;
}

/** Hakee valmistuksen
 */
function hae_valmistus($tunnus) {
  global $kukarow;

  // Haetaan valmistus
  $query = "SELECT kalenteri.kuka, kalenteri.tunnus, kalenteri.otunnus, tilausrivi.nimitys, tilausrivi.varattu, tilausrivi.yksikko
            FROM kalenteri
            JOIN lasku ON (kalenteri.yhtio=lasku.yhtio AND kalenteri.otunnus=lasku.tunnus)
            JOIN tilausrivi ON (lasku.yhtio=tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND tilausrivi.tyyppi='W')
            WHERE kalenteri.yhtio='{$kukarow['yhtio']}'
            AND kalenteri.otunnus='{$tunnus}'";
  $result = pupe_query($query);
  #echo $query;
  $valmistus = mysql_fetch_assoc($result);

  return $valmistus;
}

/**
 * Lisää valmistuksen halutulle valmistuslinjalle
 */
function lisaa_valmistus($valmistus, $linja) {
  global $kukarow;

  // Haetaan valmistuksen tiedot
  $query = "SELECT * FROM lasku where tunnus='{$valmistus}'";
  $result = pupe_query($query);
  $valmistus = mysql_fetch_assoc($result);

  // Haetaan valmistuksen tuotteen tiedot
  $query = "SELECT *
            FROM tilausrivi
            WHERE yhtio='{$kukarow['yhtio']}'
            AND otunnus='{$valmistus['tunnus']}'
            AND tyyppi='W'";
  $result = pupe_query($query);
  $valmistettava = mysql_fetch_assoc($result);

  // Haetaan viimeisen valmistuksen pvmloppu
  // Uusi valmistus lisätään viimeisimmän perään
  $query = "SELECT *
            FROM kalenteri
            WHERE yhtio='{$kukarow['yhtio']}'
            AND tyyppi='valmistus'
            AND henkilo='{$linja}'
            ORDER BY pvmloppu desc LIMIT 1";
  $result = pupe_query($query);
  $edellinen_valmistus = mysql_fetch_assoc($result);

  // Jos linjalla ei ole valmistuksia
  if (mysql_num_rows($result) == 0) {
    $start = round_time(strtotime('now'));
  }
  // Lisätään viimeisimmän valmistuksen perään
  else {
    $start = strtotime($edellinen_valmistus['pvmloppu']);
  }

  // Lasketaan loppuaika
  $kesto = valmistuksen_kesto($valmistus);
  $end = laske_loppuaika($start, $kesto * 60, $linja);

  $pvmalku = date('Y-m-d H:i:s', $start);
  $pvmloppu = date('Y-m-d H:i:s', $end);

  // Tarkistetaan onko työ jo kalenterissa (keskeytetty aiemmin)
  $query = "SELECT *
            FROM kalenteri
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$valmistus['tunnus']}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $query = "UPDATE kalenteri SET
              henkilo     = '$linja'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = {$valmistus['tunnus']}";
    $result = pupe_query($query);
  }
  else {
    // Lisätään valmistus kalenteriin
    $query = "INSERT INTO kalenteri SET
              yhtio           = '$kukarow[yhtio]',
              kuka            = '$kukarow[kuka]',
              henkilo         = '$linja',
              pvmalku         = '$pvmalku',
              pvmloppu        = '$pvmloppu',
              tyyppi          = 'valmistus',
              konserni        = '',
              myyntipaallikko = '',
              laatija         = '{$kukarow['kuka']}',
              luontiaika      = NOW(),
              muutospvm       = '0000-00-00 00:00:00',
              muuttaja        = '',
              asiakas         = '',
              tapa            = '',
              kuittaus        = '',
              aikaalku        = '0000-00-00 00:00:00',
              aikaloppu       = '0000-00-00 00:00:00',
              kieli           = '',
              kokopaiva       = '',
              kentta01        = '',
              kentta02        = '',
              kentta03        = '',
              kentta04        = '',
              kentta05        = '',
              kentta06        = '',
              kentta07        = '',
              kentta08        = '',
              kentta09        = '',
              kentta10        = '',
              liitostunnus= 0,
              perheid         = 0,
              otunnus         = $valmistus[tunnus]";
    $result = pupe_query($query);
  }
}

/** Valmistuksen kesto
 */
function valmistuksen_kesto($valmistus) {
  global $kukarow;

  // Lasketaan valmistuksen kokoonpanotyöt yheen
  $query = "SELECT sum(varattu) as kesto,
            sum(kalenteri.kentta03) as kaytetyttunnit
                FROM tilausrivi
                LEFT JOIN kalenteri on (kalenteri.yhtio=tilausrivi.yhtio and kalenteri.otunnus=tilausrivi.otunnus)
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus = {$valmistus['tunnus']}
                AND yksikko            ='H'";
  $result = pupe_query($query);
  $kesto = mysql_fetch_assoc($result);

  return $kesto['kesto'] - $kesto['kaytetyttunnit'];
}

function hae_valmistuslinjat() {
  global $kukarow;

  $result = t_avainsana("VALMISTUSLINJA");

  $valmistuslinjat = array();

  while ($linja = mysql_fetch_assoc($result)) {
    $valmistuslinjat[] = $linja;
  }

  return $valmistuslinjat;
}

/**
 * Pyöristää ajan halutulla tarkkuudella
 * Oletus 30 minuuttia
 *
 * @param timestamp $time
 * @param integer $seconds
 *
 * @return timestamp
 */
function round_time($time, $seconds = 1800) {
  $rounded_time = round($time / $seconds) * $seconds;
  return $rounded_time;
}

/** Laskee valmistuksen loppumiskohdan alkuajan ja valmistuksen keston mukaan.
 */
function laske_loppuaika($alkuaika, $minuutit = 0, $valmistuslinja) {
  global $kukarow;

  #echo "lasketaan loppuaika: $alkuaika, $minuutit, $valmistuslinja<br>";
  // Pienin kalenterin yksikkö, 30 minuuttia
  if (!defined('INTERVALLI'))
    define('INTERVALLI', 30);
  if (!defined('TYOPAIVAN_ALKU'))
    define('TYOPAIVAN_ALKU', '07:00');
  if (!defined('TYOPAIVAN_LOPPU'))
    define('TYOPAIVAN_LOPPU', '15:00');

  if ($minuutit == 0)
    $minuutit = 60;

  // Setataan loppuaika aluksi pyöristettyyn aloitusaikaan
  $loppuaika = round_time($alkuaika);

  #echo "kesto: ". $minuutit/60 ." aloitetaan lisäämään alkuajasta (alkuaika: ".date('Y-m-d H:i:s', $alkuaika)."): ".date('Y-m-d H:i:s', $loppuaika)."<br>";
  // Haetaan kalenteriin merkatut erikoispäivät
  // Yhtiökohtaiset ('PY')
  // Henkilökohtaiset ('SA', 'PO', 'PE', 'MT', 'LO')
  $lomat = array();

  $query = "SELECT pvmalku, pvmloppu
            FROM kalenteri
            WHERE yhtio='{$kukarow['yhtio']}'
            AND (tyyppi IN ('PY', 'MT') AND henkilo='')
            OR (tyyppi IN ('SA', 'PO', 'PE', 'MT', 'LO') AND henkilo={$valmistuslinja})
            ORDER BY pvmalku;";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $lomat[] = $row;
  }

  // Lisätään aikaa
  while ($minuutit > 0) {

    $tyoaikaa = true;

    // Jos on viikonloppu
    if (date('w', $loppuaika) == 0 or date('w', $loppuaika) == 6) {
      #echo "viikonloppua: " . date('H:i d.m.Y', $loppuaika) . "<br>";
      $tyoaikaa = false;
    }

    // Jos ollaan lomalla
    foreach ($lomat as $loma) {
      if (date('Y-m-d H:i:s', $loppuaika) >= $loma['pvmalku'] and date('Y-m-d H:i:s', $loppuaika) < $loma['pvmloppu']) {
        #echo "lomaa: " . date('H:i d.m.Y', $loppuaika) . "<br>";
        $tyoaikaa = false;
      }
    }

    // Jos ollaan työajalla
    if ($tyoaikaa == true and date('H:i', $loppuaika) >= date('H:i', strtotime(TYOPAIVAN_ALKU)) and date('H:i', $loppuaika) < date('H:i', strtotime(TYOPAIVAN_LOPPU))) {
      #echo "työaikaa: " . date('H:i d.m.Y', $loppuaika) . "<br>";
      // Vähennetään jäljellä olevaa aikaa
      $minuutit -= INTERVALLI;
    }

    #echo "loppuaika: " . date('H:i d.m.Y', $loppuaika) . "<br>";
    // Lisätään loppuaikaa joka kierroksella
    $loppuaika = mktime(date('H', $loppuaika), date('i', $loppuaika) + INTERVALLI, date('s', $loppuaika), date('m', $loppuaika), date('d', $loppuaika), date('Y', $loppuaika));
  }

  if ($loppuaika < strtotime('now')) {
    $loppuaika = strtotime('now');
  }

  return $loppuaika;
}

/** Rakennetaan valmistuslinjat uudelleen
 */
function rebuild_valmistuslinjat($from = 'now') {
  global $kukarow;

  $from = date('Y-m-d', strtotime($from));
  # echo "rakennetaan valmistuslinja uudelleen alkaen: $from <br>";
  // Hetaan kaikki valmistuslinjat
  $query = "SELECT selite
            FROM avainsana
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND laji    = 'VALMISTUSLINJA'
            ORDER BY selite";
  $valmistuslinjat_result = pupe_query($query);

  // Järjestellään linja kerrallaan
  while ($linja = mysql_fetch_assoc($valmistuslinjat_result)) {

    // Haetaan kaikki linjan valmistukset (paitsi valmiit tarkastukseen ja tarkastetut)
    $query = "SELECT *, kalenteri.tunnus as tunnus
              FROM kalenteri
              JOIN lasku ON (kalenteri.yhtio=lasku.yhtio AND kalenteri.otunnus=lasku.tunnus)
              WHERE kalenteri.yhtio = '{$kukarow['yhtio']}'
              AND henkilo           = '{$linja['selite']}'
              AND valmistuksen_tila not in('VT', 'TA')
              ORDER BY pvmalku";
    $result = pupe_query($query);

    $valmistukset = array();

    // Järjestellään tulokset niin että valmistuksessa oleva on ensimmäisenä
    while ($valmistus = mysql_fetch_assoc($result)) {
      if ($valmistus['valmistuksen_tila'] == 'VA') {
        array_unshift($valmistukset, $valmistus); // Laitetaan valmistuksessa oleva jonon alkuun
      }
      else {
        array_push($valmistukset, $valmistus); // Muuten lisätään vain taulukkoon
      }
    }

    // Valmistusten käsittely
    $previous = '';
    $current = '';

    // Loopataan valmistukset läpi ja järjestellään
    foreach ($valmistukset as $valmistus) {
      $current = $valmistus;

      // Jos tila on VA tai VT
      if ($current['valmistuksen_tila'] == 'VA' or $current['valmistuksen_tila'] == 'VT' or $current['valmistuksen_tila'] == 'TA') {
        // Ei saa siirtää (ei kosketa alkupvm)
      }
      // Jos tila on OV tai TK (..tai mitä tahansa muuta?) saa siirellä
      else {
        // Saa siirtää (alkupvm on edellisen loppuvm) jos edellinen on olemassa
        if ($previous) {
          $current['pvmalku'] = $previous['pvmloppu'];
        }
        else {
          $current['pvmalku'] = date('Y-m-d H:i:s');
        }
        // Jos edellisetä ei ole setattu, eli ollaan ekassa kalenterin valmistuksessa.
        // Kellutetaan kaikki tähän hetkeen?
      }

      if ($current['valmistuksen_tila'] == 'VT' or $current['valmistuksen_tila'] == 'TA') {
        continue;
      }
      // Lasketaan valmistuksen kesto
      // @params tunnus => lasku.tunnus
      $kesto = valmistuksen_kesto(array('tunnus' => $current['otunnus']));

      // Lasketaan uusi loppuaika
      $pvmloppu = laske_loppuaika(strtotime($current['pvmalku']), $kesto * 60, $linja['selite']);

      // Valmistuksen uusi loppuaika
      $current['pvmloppu'] = date('Y-m-d H:i:s', $pvmloppu);

      // Päivitetään uudet päivämäärät tietokantaan
      $query = "UPDATE kalenteri
                SET pvmalku = '{$current['pvmalku']}', pvmloppu='{$current['pvmloppu']}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$current['tunnus']}'";
      pupe_query($query);

      // Päivitetään laskulle uusi kerayspvm ja tilausrivieille uusi toimaika
      $lasku_query = "UPDATE lasku SET
                      kerayspvm   = '{$current['pvmloppu']}',
                      toimaika    = '{$current['pvmloppu']}'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = {$current['otunnus']}";
      pupe_query($lasku_query);

      $tilausrivi_query = "UPDATE tilausrivi SET
                           kerayspvm   = '{$current['pvmalku']}',
                           toimaika    = '{$current['pvmalku']}'
                           WHERE yhtio = '{$kukarow['yhtio']}'
                           AND otunnus = {$current['otunnus']}
                           AND tyyppi  = 'V'";
      pupe_query($tilausrivi_query);

      $tilausrivi_query = "UPDATE tilausrivi SET
                           kerayspvm   = '{$current['pvmloppu']}',
                           toimaika    = '{$current['pvmloppu']}'
                           WHERE yhtio = '{$kukarow['yhtio']}'
                           AND otunnus = {$current['otunnus']}
                           AND tyyppi  = 'W'";
      pupe_query($tilausrivi_query);

      // Laitetaan kierroksen valmistus edellinen muuttujaan
      $previous = $current;
    }
  }
}

/**
 * Tarkistaa valmistuksen raaka-aineiden saldot tiettynä päivänä
 */
function puuttuvat_raaka_aineet($tunnus, $aloitus_pvm = 'now') {
  global $kukarow;

  // Kerätään riittämättömät tuotteet talteen
  $vajaat_saldot = array();

  // Valmistuksen päivämäärä, minkä mukaan muut saldot tarkistetaan
  $aloitus_pvm = date('Y-m-d H:i:s', strtotime($aloitus_pvm));

  // Haetaan laskun tiedot (valmistuksen otsikko)
  $query = "SELECT *
            FROM lasku
            JOIN kalenteri on (lasku.yhtio=kalenteri.yhtio and lasku.tunnus=kalenteri.otunnus)
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tunnus  = '$tunnus'";
  $result = pupe_query($query);
  $lasku = mysql_fetch_assoc($result);

  // Haetaan valmistuksen raaka-aineet
  $query = "SELECT *
            FROM tilausrivi
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND otunnus  = '{$tunnus}'
            AND tuoteno != 'TYÖ'
            AND tyyppi   = 'V'";
  $result = pupe_query($query);

  // Loopataan raaka-aineet läpi ja tarkistetaan niiden saldo.
  while ($raaka_aine = mysql_fetch_assoc($result)) {

    $saldo = array();

    // Haetaan raaka-aineiden saldot valmistuksen aloitus päivänä.
    // Sisältää kaikkien tämän valmistuksen aloituspäivää aiemmat valmistukset KAIKILTA linjoilta.
    // HUOM! saldo_myytavissa palauttaa vain haluttua päivää aiemmat saldot? pvm <= $kysytty_pvm
    list($saldo['saldo'], $saldo['hyllyssa'], $saldo['myytavissa']) = saldo_myytavissa($raaka_aine['tuoteno'], '', '', '', '', '', '', '', '', $aloitus_pvm);

    // Varatut kappaleet valmistuksilta jotka ovat jo valmistuslinjalla.
    // Valmistuslinjalla olevat valmistukset varaavat saldoa ja uuden valmistuksen on
    // tarkistettava paljon ne vähentävät raaka-aineiden saldoa.
    $query = "SELECT IFNULL(sum(tilausrivi.varattu), 0) AS varattu
              FROM kalenteri
              JOIN tilausrivi ON (tilausrivi.yhtio = kalenteri.yhtio
                AND tilausrivi.otunnus  = kalenteri.otunnus
                AND tilausrivi.tyyppi   = 'V'
                AND tilausrivi.varattu != 0
                AND tilausrivi.tuoteno  = '{$raaka_aine['tuoteno']}')
              WHERE kalenteri.yhtio     = '{$kukarow['yhtio']}'
              AND kalenteri.tyyppi      = 'valmistus'
              AND kalenteri.pvmalku     <= '$aloitus_pvm'
              AND kalenteri.henkilo    != {$lasku['henkilo']}";
//    $muut_valmistukset_result = pupe_query($query);
//    $muut_valmistukset = mysql_fetch_assoc($muut_valmistukset_result);

    // Haetaan raaka-aineen ostotilauksia, jotka vaikuttavat valmistuksen aloitukseen
    $query = "SELECT COALESCE(sum(varattu), 0) AS varattu
              FROM tilausrivi
              WHERE yhtio     = '{$kukarow['yhtio']}'
              AND tuoteno     = '{$raaka_aine['tuoteno']}'
              AND tyyppi      = 'O'
              # AND kerattyaika != '0000-00-00 00:00:00'
              AND kerattyaika < '$aloitus_pvm'";

//    $ostotilaukset_result = pupe_query($query);
//    $ostotilaukset = mysql_fetch_assoc($ostotilaukset_result);

    /**
      Raaka-aineen riiton laskentakaava:
      "Saldo nyt"
      - "Varatut kappaleet valmistuksilta, joiden päivä on vanhempi kun ko. päivä"
      - "Varatut kappaleet myyntitilauksilta, joiden päivä on vanhempi kun ko. päivä"
      + "Vahvistetut kappaleet ostotilausriveilta, joiden saapumispäivä on vanhempi kun ko. päivä".
     */
    // saldo_myytavissa sisältää valmistuksen varaamat raaka-aineet ja myyntitilausten varaamat kappaleet.
    // Huomioidaan vain muiden valmistusten varaamat ja mahdollisten ostotilausten vaikutus saldoihin.
    # $raaka_aine_saldo = $saldo['myytavissa'] - $muut_valmistukset['varattu'] + $ostotilaukset['varattu'];
    $raaka_aine_saldo = $saldo['myytavissa'];

    // Jos raaka_aineella on saldoa vähemmin kuin valmistuksen varaama saldo niin lisätään raaka-aine puutteeksi
    if ($raaka_aine_saldo <= $raaka_aine['varattu']) {
      // Vähennetään valmistuksen aloituspäivän saldosta valmistuksen varaama saldo
      $vajaat_saldot[$raaka_aine['tuoteno']] = $raaka_aine_saldo;
    }
  }

  return $vajaat_saldot;
}

/** Etsii edellisen valmistuksen
 *
 * @param integer $valmistus Valmistuksen tunnus
 */
function etsi_edellinen_valmistus($valmistus) {
  global $kukarow;

  // Etsitään edellinen valmistus
  $query = "SELECT *, lasku.tunnus
            FROM kalenteri
            JOIN lasku on (kalenteri.yhtio=lasku.yhtio AND kalenteri.otunnus=lasku.tunnus)
            WHERE kalenteri.yhtio='{$kukarow['yhtio']}'
            AND pvmloppu <= '{$valmistus['pvmalku']}'
            AND henkilo  = '{$valmistus['henkilo']}'
            ORDER BY pvmloppu DESC LIMIT 1";
  $result = pupe_query($query);
  $edellinen = mysql_fetch_assoc($result);

  // Palautetaan löydetty valmistus
  return $edellinen;
}

/**
 * Etsii seuraavan_valmistuksen
 *
 * @param integer $valmistus Valmistuksen tunnus
 */
function etsi_seuraava_valmistus($valmistus) {
  global $kukarow;

  // Etsitään seuraava valmistus
  $query = "SELECT *, lasku.tunnus
            FROM kalenteri
            JOIN lasku on (kalenteri.yhtio=lasku.yhtio AND kalenteri.otunnus=lasku.tunnus)
            WHERE kalenteri.yhtio='{$kukarow['yhtio']}'
            AND pvmalku >= '{$valmistus['pvmloppu']}'
            AND henkilo = '{$valmistus['henkilo']}'
            ORDER BY pvmalku LIMIT 1";
  $result = pupe_query($query);
  $seuraava = mysql_fetch_assoc($result);

  // Palautetaan löydetty valmistus
  return $seuraava;
}

/**
 * Vaihtaa valmistusten paikkoja
 * Olettaa että vasen on aina aikaisempi valmistus (vasen[pvmalku] < oikea[pvmalku])
 *
 * @param array $vasen
 * @param array $oikea
 */
function vaihda_valmistusten_paikkaa($vasen, $oikea, $linja) {
  global $kukarow;

  // VAlmistuksessa olevia ei siirretä
  if (in_array($vasen['valmistuksen_tila'], array('VA', 'VT', 'TA')) or in_array($oikea['valmistuksen_tila'], array(
        'VA', 'VT', 'TA'))) {
    echo "<font class='error'>Valmistusta ei voida siirtää</font><br>";
    return false;
  }

  // Aloitetaan laskemaan päivämääriä oikein
  // Oikea siirtyy alkamaan vasemman alusta
  $oikea['pvmalku'] = $vasen['pvmalku'];

  // swapataan muuttujat, jotta loppu on selkeämpää
  // (Left is Right, Right!)
  $_temp = $vasen;
  $vasen = $oikea;
  $oikea = $_temp;

  // Lasketaan vasemman loppuaika uudelleen
  $kesto = valmistuksen_kesto($vasen);
  #echo "vasen kesto: $kesto <br>";
  $uusi_pvmloppu = laske_loppuaika(strtotime($vasen['pvmalku']), $kesto * 60, $linja);
  $vasen['pvmloppu'] = date('Y-m-d H:i:s', $uusi_pvmloppu);

  // Oikean alkuaika on vasemman uusi loppuaika
  $oikea['pvmalku'] = $vasen['pvmloppu'];

  // Lasketaan oikean loppuaika uudelleen
  $kesto = valmistuksen_kesto($oikea);
  #echo "oikea kesto: $kesto <br>";
  $uusi_pvmloppu = laske_loppuaika(strtotime($oikea['pvmalku']), $kesto * 60, $linja);
  $oikea['pvmloppu'] = date('Y-m-d H:i:s', $uusi_pvmloppu);

  // Päivitetään valmistusten uudet päivämäärät kantaan
  $query = "UPDATE kalenteri
            SET pvmalku='{$vasen['pvmalku']}', pvmloppu='{$vasen['pvmloppu']}'
            WHERE yhtio='{$kukarow['yhtio']}' and otunnus={$vasen['tunnus']}";
  #echo $query."<br>";
  pupe_query($query);

  $query = "UPDATE kalenteri
            SET pvmalku='{$oikea['pvmalku']}', pvmloppu='{$oikea['pvmloppu']}'
            WHERE yhtio='{$kukarow['yhtio']}' and otunnus={$oikea['tunnus']}";
  pupe_query($query);
  #echo $query."<br>";

  return true;
}

/** Valmistuksen jakaminen
 *
 * @params array   'valmisteen_tunnus' => 'kpl'
 * @return string  'virheet' tai ''
 */
function jaa_valmistus($tunnus, $params) {
  global $kukarow;

  if (empty($tunnus) or !is_array($params)) {
    exit("Virheelliset parametrit, jaa_valmistus()");
  }

  // Haetaan valmistuksen tiedot (lasku)
  $original_query = "SELECT *
                     FROM lasku
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus  = $tunnus
                     LIMIT 1";
  $original_result = pupe_query($original_query);
  $original = mysql_fetch_array($original_result, 1);

  if (mysql_num_rows($original_result) == 0) {
    throw new Exception("Virhe valmistuksen jakamisessa, laskua ei löytynyt");
  }

  // laskun kopio, ei luoda ennenkuin tiedetään että tilausrivejä tarvitsee kopioida
  $copy = '';

  // Haetaan kaikki valmistuksen valmisteet (tyypit 'W' tai 'M')
  $valmisteet_query = "SELECT *
                       FROM tilausrivi
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND otunnus = $tunnus
                        AND tyyppi  in ('W', 'M')";
  $valmisteet_result = pupe_query($valmisteet_query);

  // Loopataan valmisteet
  while ($valmiste = mysql_fetch_assoc($valmisteet_result)) {

    // Alkuperäiselle jätettävä määrä ja kopiolle tuleva määrä
    $original_kpl = $params[$valmiste['tunnus']]; // esim. valmistettiin 3
    $copy_kpl = $valmiste['varattu'] - $original_kpl;  // 9 = 12 - 3, uudelle valmisteelle siirretään 9 kpl
    $raaka_aineen_suhde = $original_kpl / $valmiste['varattu'];

    // Jos syötetty nolla ei tehdä kopioita
    if (!is_numeric($original_kpl))
      throw new Exception("Syötetyn määrän on oltava numero");

    if (($original_kpl == 0 or $original_kpl == $valmiste['varattu']) and mysql_num_rows($valmisteet_result) == 1) {
      $errors = "Valmistusta ei jaettu";
      continue;
    }

    // Jos splitatavan määrä on oikein eli välillä (0 - tilattu_määrä)
    if ($original_kpl >= 0 and $original_kpl <= $valmiste['varattu']) {

      // Jos määrä on sallitun rajoissa, tehdään laskusta kopio
      if (empty($copy)) {

        // Päivitetään kopiolle tulevat tiedot
        $copy = $original;
        $copy['laatija']   = $kukarow['kuka'];
        $copy['muutospvm'] = '';

        unset($copy['luontiaika']); // now() ilman hipsukoita, setataan viimeisenä
        unset($copy['kate_korjattu']); // NULL ilman hipsukoita, setataan viimeisenä
        unset($copy['tunnus']);

        if ((isset($copy['muutospvm']) and $copy['muutospvm'] == '') or !isset($copy['muutospvm'])) {
          $copy['muutospvm'] = '0000-00-00 00:00:00';
        }

        // Luodaan otsikon kopio
        $copy_query = "INSERT INTO
                       lasku (".implode(", ", array_keys($copy)).", luontiaika, kate_korjattu)
                       VALUES('".implode("', '", array_values($copy))."', now(), NULL)";

        if (!pupe_query($copy_query)) {
          exit("Virhe laskun kopioinnissa, uutta laskua ei luotu");
        }

        // Kopioidun laskun tunnus talteen
        $copy['tunnus'] = mysql_insert_id();
      }

      // Kopioidaan valmiste
      $poikkeukset = array(
        'tilausrivi.varattu' => $copy_kpl,
        'tilausrivi.otunnus' => $copy['tunnus']);
      $kopioidun_valmisteen_id = kopioi_tilausrivi($valmiste['tunnus'], $poikkeukset);

      // Päivitetään splitatulle riville perheid
      $query = "UPDATE tilausrivi SET
                perheid     = $kopioidun_valmisteen_id
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = $kopioidun_valmisteen_id";
      $res = pupe_query($query);

      // Päivitetään alkuperäinen valmiste
      $query = "UPDATE tilausrivi
                SET varattu = $original_kpl
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = {$valmiste['tunnus']}";
      $res = pupe_query($query);

      // Päivitetään tilausrivin_lisatietoihin kopioidun laskun tunnus (valmiste)
      $query = "UPDATE tilausrivin_lisatiedot SET
                vanha_otunnus        = {$copy['tunnus']}
                WHERE yhtio          = '{$kukarow['yhtio']}'
                AND tilausrivitunnus = $kopioidun_valmisteen_id";
      $res = pupe_query($query);

      // Loopataan valmisteen raaka-aineet
      $raaka_aine_query = "SELECT *
                           FROM tilausrivi
                           WHERE yhtio = '{$kukarow['yhtio']}'
                           AND otunnus = {$valmiste['otunnus']}
                           AND perheid = {$valmiste['perheid']}
                           AND tyyppi  = 'V'";
      $raaka_aine_result = pupe_query($raaka_aine_query);

      // Loopataan valmisteen raaka-aineet
      while ($raaka_aine = mysql_fetch_assoc($raaka_aine_result)) {

        // Raaka-aineen kappaleet
        $r_original_kpl = $raaka_aine['varattu'] * $raaka_aineen_suhde; # 3 = 12 * 0.25
        $r_copy_kpl = $raaka_aine['varattu'] - $r_original_kpl;  # 9 = 12 - 3
        // Kopioidaan Raaka-aine
        $poikkeukset = array(
          'tilausrivi.varattu' => $r_copy_kpl,
          'tilausrivi.otunnus' => $copy['tunnus'],
          'tilausrivi.perheid' => $kopioidun_valmisteen_id);
        $kopioidun_raaka_aineen_id = kopioi_tilausrivi($raaka_aine['tunnus'], $poikkeukset);

        // Päivitetään alkuperäinen raaka_aine
        $query = "UPDATE tilausrivi SET
                  varattu     = $r_original_kpl
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = {$raaka_aine['tunnus']}";
        $res = pupe_query($query);

        // Päivitetään tilausrivin_lisätiedolle kopioidun laskun tunnus (raaka-aine)
        $query = "UPDATE tilausrivin_lisatiedot SET
                  vanha_otunnus        = {$copy['tunnus']}
                  WHERE yhtio          = '{$kukarow['yhtio']}'
                  AND tilausrivitunnus = $kopioidun_raaka_aineen_id";
        $res = pupe_query($query);
      }
    }
    else {
      throw new Exception("Syötetyn määrän on oltava välillä 0 - {$valmiste['varattu']}");
    } // End of splittailut
  } // End of valmisteet
  // palautetaan kopioidun laskun tunnus
  $return = (isset($copy['tunnus'])) ? $copy['tunnus'] : null;
}

/**
 * Hakee käyttöliittymässä dropdowniin piirrettävät valmistuksien tila rajaukset
 * (raportti_valmistuksista.php ja puuttuvat_raaka_aineet.php)
 *
 * @param string $tyyppi puuttuvat_raaka_aineet.php ei haluta valmistuksia, jotka on jo valmistettu
 * @global type $kukarow
 * @global type $yhtiorow
 * @return array
 */
function hae_valmistuksien_tilat($tyyppi = '') {
  global $kukarow, $yhtiorow;

  $valmistuksien_tilat = array(
    0   => array(
      'value'       => '',
      'dropdown_text'   => t('Näytä kaikki valmistukset'),
      'query_where'   => '',
    ),
    1   => array(
      'value'       => 'K',
      'dropdown_text'   => t('Näytä valmistukset, jotka ovat kesken'),
      'query_where'   => ' AND lasku.tila IN ("V") AND lasku.alatila IN ("")',
    ),
    2   => array(
      'value'       => 'T',
      'dropdown_text'   => t('Näytä valmistukset, jotka ovat tulostettu'),
      'query_where'   => ' AND lasku.tila IN ("V") AND lasku.alatila IN ("A")',
    ),
    3   => array(
      'value'       => 'R',
      'dropdown_text'   => t('Näytä valmistukset, jotka ovat kerätty'),
      'query_where'   => ' AND lasku.tila IN ("V") AND lasku.alatila IN ("C")',
    ),
  );

  if (!empty($tyyppi)) {
    $a = array(
      'value'       => 'V',
      'dropdown_text'   => t('Näytä valmistukset, jotka ovat valmistettu'),
      'query_where'   => ' AND lasku.tila IN ("V") AND tilausrivi.kpl != 0',
    );
    array_push($valmistuksien_tilat, $a);
  }
  return $valmistuksien_tilat;
}
