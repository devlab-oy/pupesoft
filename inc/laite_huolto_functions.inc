<?php

$filepath = dirname(__FILE__);
if (file_exists($filepath . '/../tilauskasittely/luo_myyntitilausotsikko.inc')) {
  require_once("{$filepath}/../tilauskasittely/luo_myyntitilausotsikko.inc");
}
else {
  require_once("{$filepath}/luo_myyntitilausotsikko.inc");
}

function hae_laitteelle_mahdolliset_huoltosyklit($laite_tyyppi = '', $laite_koko = '', $paikan_olosuhde = '') {
  global $kukarow, $yhtiorow;

  $huoltosykli_where = "";
  if ($laite_tyyppi != '') {
    $huoltosykli_where .= "  AND huoltosykli.tyyppi = '{$laite_tyyppi}'";
  }
  if ($laite_koko != '') {
    $huoltosykli_where .= "  AND huoltosykli.koko = '{$laite_koko}'";
  }
  if ($paikan_olosuhde != '') {
    $huoltosykli_where .= "  AND huoltosykli.olosuhde = '{$paikan_olosuhde}'";
  }

  $query = "SELECT huoltosykli.tunnus,
            ta.selite AS huoltosykli_tyyppi,
            ta.selitetark AS huoltosykli_prioriteetti,
            concat_ws(' - ', tuote.nimitys, huoltosykli.tyyppi, huoltosykli.koko,
              (
                SELECT selitetark
                FROM avainsana
                WHERE avainsana.yhtio = huoltosykli.yhtio
                AND avainsana.selite = huoltosykli.olosuhde
                AND avainsana.laji = 'OLOSUHDE'
              )
            ) as dropdown_text,
            huoltosykli.huoltovali,
            huoltosykli.pakollisuus
            FROM huoltosykli
            JOIN tuote
            ON ( tuote.yhtio = huoltosykli.yhtio
              AND tuote.tuoteno = huoltosykli.toimenpide )
            JOIN tuotteen_avainsanat AS ta
            ON ( ta.yhtio = tuote.yhtio
              AND ta.tuoteno = tuote.tuoteno
              AND ta.laji = 'tyomaarayksen_ryhmittely' )
            WHERE huoltosykli.yhtio = '{$kukarow['yhtio']}'
            {$huoltosykli_where}";
  $huoltosykli_result = pupe_query($query);
  $huoltosyklit = array();
  while ($huoltosykli = mysql_fetch_assoc($huoltosykli_result)) {
    $huoltosyklit[] = $huoltosykli;
  }

  return $huoltosyklit;
}

function hae_laitteet_ja_niiden_huoltosyklit_joiden_huolto_lahestyy($asiakas_tunnus = 0) {
  global $kukarow;

  $asiakas_join = "";
  if (!empty($asiakas_tunnus)) {
    $asiakas_join = " AND asiakas.tunnus = {$asiakas_tunnus}";
  }

  $query = "SELECT laite.tuoteno,
            huoltosyklit_laitteet.viimeinen_tapahtuma,
            laite.tunnus AS laite_tunnus,
            huoltosyklit_laitteet.huoltosykli_tunnus,
            huoltosyklit_laitteet.huoltovali AS huoltovali,
            huoltosykli.toimenpide,
            huoltosykli.huoltovali AS toimenpide_oletus_huoltovali,
            asiakas.tunnus AS asiakas_tunnus,
            kohde.tunnus AS kohde_tunnus,
            kohde.nimi AS kohde_nimi,
            kohde.vastuuhenkilo AS kohde_vastuuhenkilo,
            paikka.nimi AS paikka_nimi,
            tuotteen_avainsanat.selite AS toimenpide_tuotteen_tyyppi,
            tuotteen_avainsanat.selitetark AS toimenpide_tuotteen_prioriteetti
            FROM   laite
            JOIN huoltosyklit_laitteet
            ON ( huoltosyklit_laitteet.yhtio = laite.yhtio
              AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus
              AND IFNULL(huoltosyklit_laitteet.viimeinen_tapahtuma, '0000-00-00') < Date_sub(CURRENT_DATE, INTERVAL (huoltosyklit_laitteet.huoltovali - 30) DAY) )
            JOIN huoltosykli
            ON ( huoltosykli.yhtio = huoltosyklit_laitteet.yhtio
              AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
            JOIN paikka
            ON ( paikka.yhtio = laite.yhtio
              AND paikka.tunnus = laite.paikka )
            JOIN kohde
            ON ( kohde.yhtio = paikka.yhtio
              AND kohde.tunnus = paikka.kohde )
            JOIN asiakas
            ON ( asiakas.yhtio = kohde.yhtio
              AND asiakas.tunnus = kohde.asiakas
              {$asiakas_join} )
            JOIN tuote
            ON ( tuote.yhtio = laite.yhtio
              AND tuote.tuoteno = huoltosykli.toimenpide )
            LEFT JOIN tuotteen_avainsanat
            ON ( tuotteen_avainsanat.yhtio = tuote.yhtio
              AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
              AND tuotteen_avainsanat.laji = 'tyomaarayksen_ryhmittely' )
            WHERE  laite.yhtio = '{$kukarow['yhtio']}'
            AND laite.tila IN ('N', 'V', 'K')/*Normaali, varalaite, kateissa*/
            AND ( laite.omistaja = '' OR laite.omistaja IS NULL )
            ORDER BY laite_tunnus ASC,
            tuotteen_avainsanat.selitetark ASC,
            kohde.tunnus ASC";
  $result = pupe_query($query);

  $laitteet = array();
  while ($laite = mysql_fetch_assoc($result)) {
    $laitteet[$laite['laite_tunnus']][] = $laite;
  }

  return $laitteet;
}

function hae_laitteet_ja_niiden_huoltosyklit_ajalta($start, $end, $asiakas) {
  global $kukarow;

  $asiakas_and = '';
  if ($asiakas != '') {
    $asiakas_and = "AND asiakas.tunnus = {$asiakas} ";
  }

  $huoltovalit = huoltovali_options();

  $query = "SELECT laite.tuoteno,
            huoltosyklit_laitteet.viimeinen_tapahtuma,
            laite.tunnus AS laite_tunnus,
            huoltosyklit_laitteet.huoltosykli_tunnus,
            huoltosyklit_laitteet.huoltovali AS huoltovali,
            huoltosykli.toimenpide,
            huoltosykli.huoltovali AS toimenpide_oletus_huoltovali,
            asiakas.tunnus AS asiakas_tunnus,
            kohde.nimi AS kohde_nimi,
            kohde.vastuuhenkilo AS vastuuhenkilo,
            paikka.nimi AS paikka_nimi,
            tuotteen_avainsanat.selite AS toimenpide_tuotteen_tyyppi,
            tuotteen_avainsanat.selitetark AS toimenpide_tuotteen_prioriteetti
            FROM   laite
            JOIN huoltosyklit_laitteet
            ON ( huoltosyklit_laitteet.yhtio = laite.yhtio
              AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus )
            JOIN huoltosykli
            ON ( huoltosykli.yhtio = laite.yhtio
              AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
            JOIN paikka
            ON ( paikka.yhtio = laite.yhtio
              AND paikka.tunnus = laite.paikka )
            JOIN kohde
            ON ( kohde.yhtio = paikka.yhtio
              AND kohde.tunnus = paikka.kohde )
            JOIN asiakas
            ON ( asiakas.yhtio = kohde.yhtio
              AND asiakas.tunnus = kohde.asiakas )
            JOIN tuote
            ON ( tuote.yhtio = laite.yhtio
              AND tuote.tuoteno = huoltosykli.toimenpide )
            LEFT JOIN tuotteen_avainsanat
            ON ( tuotteen_avainsanat.yhtio = tuote.yhtio
              AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
              AND tuotteen_avainsanat.laji = 'tyomaarayksen_ryhmittely' )
            WHERE  laite.yhtio = '{$kukarow['yhtio']}'
            AND laite.tila IN ('N', 'V', 'K')/*Normaali, varalaite, kateissa*/
            AND ( laite.omistaja = '' OR laite.omistaja IS NULL )
            AND DATE_ADD( huoltosyklit_laitteet.viimeinen_tapahtuma, INTERVAL huoltosyklit_laitteet.huoltovali DAY) BETWEEN '{$start}' AND '{$end}'
            {$asiakas_and}
            ORDER BY laite_tunnus ASC,
            tuotteen_avainsanat.selitetark ASC";

  $result = pupe_query($query);

  $laitteet = array();
  while ($laite = mysql_fetch_assoc($result)) {
    $laite['tyojono'] = $laite['vastuuhenkilo'];
    $laite['tyostatus'] = 'A';
    $huoltovali = search_array_key_for_value_recursive($huoltovalit, days, $laite['huoltovali']);
    $huoltovali = $huoltovali[0];
    $laite['seuraava_tapahtuma'] = date('Y-m-d', strtotime("{$laite['viimeinen_tapahtuma']} + {$huoltovali['years']} years"));

    //Resultti groupataan laite_tunnukset ja seuraavan tapahtuman mukaan, jotta eripäivinä
    //tapahtuvat tapahtumat osataan erottaa toisistaan.
    //Esim. jos koeponnistus 2014-01-01 ja tarkastus 2014-02-01 niin koeponnistus ei saa yliajaa tarkastusta
    if (empty($laitteet[$laite['laite_tunnus']][$laite['seuraava_tapahtuma']])) {
      $laitteet[$laite['laite_tunnus']][$laite['seuraava_tapahtuma']] = $laite;
    }
  }

  return $laitteet;
}

function paata_mitka_huollot_tehdaan($laitteiden_huoltosyklirivit) {
  global $kukarow, $yhtiorow;

  $huollettavien_laitteiden_huoltosyklirivit = array();
  $laitteiden_huoltosyklirivit_joita_ei_huolleta = array();
  foreach ($laitteiden_huoltosyklirivit as $laite_tunnus => $laitteen_huoltosyklirivit) {
    $huollettavien_laitteiden_huoltosyklirivit[] = $laitteen_huoltosyklirivit[0];
    $i = 0;
    foreach ($laitteen_huoltosyklirivit as $laitteen_huoltosyklirivi) {
      //Laitteelle ehdotettavat huollot tulevat toimenpide_tuotteen prioriteetin mukaan järjestettynä.
      //Tällöin korkein prioriteetti on siis ensimmäisenä $laitteen_huoltosyklirivit muuttujassa
      if ($i == 0) {
        $i++;
        continue;
      }
      $laitteiden_huoltosyklirivit_joita_ei_huolleta[$laite_tunnus][] = $laitteen_huoltosyklirivi;
      $i++;
    }
  }

  return array($huollettavien_laitteiden_huoltosyklirivit, $laitteiden_huoltosyklirivit_joita_ei_huolleta);
}

function generoi_tyomaaraykset_huoltosykleista($laitteet, $laitteiden_huoltosyklirivit_joita_ei_huolleta) {
  global $kukarow, $yhtiorow, $debug;

  if (!isset($debug)) {
    $debug = false;
  }

  if ($debug or php_sapi_name() == 'cli') {
    echo "Työmääräyksiä pitäisi tulla " . count($laitteet) . ' kappaletta';
    if (php_sapi_name() == 'cli') {
      echo "\n";
    }
    else {
      echo "<br/>";
      echo "<br/>";
    }
  }

  if ($debug and count($laitteet) > 0) {
    $proggress_bar = new ProgressBar(t('Työmääräykset'));
    $proggress_bar->initialize(count($laitteet));
  }

  $edellinen_kohde_tunnus = 0;
  $virheet = array();
  $laitteiden_avoimet_tyomaaraykset = laitteiden_avoimet_tyomaaraykset();
  $saldottomat_tuotteet = tuotteiden_ei_saldoa();
  $asiakkaat = hae_asiakkaat();
  $tuotteet = hae_tuotteet();
  foreach ($laitteet as $laite) {
    //Tarkistetaan löytyyko avoin työmääräys laitteelle
    if (isset($laitteiden_avoimet_tyomaaraykset[$laite['laite_tunnus']][$laite['toimenpide']])) {
      if ($debug) {
        $virheet[$laite['laite_tunnus']][] = "Tälle laitteelle " . $laite['laite_tunnus'] . " on jo luotu työmääräys";
      }
      if (isset($proggress_bar)) {
        $proggress_bar->increase();
      }
      continue;
    }

    //laitteen toimenpidetuote pitää olla saldoton
    if (!isset($saldottomat_tuotteet[$laite['toimenpide']])) {
      if ($debug) {
        $virheet[$laite['laite_tunnus']][] = "Toimenpide tuote " . $laite['laite_tunnus'] . " pitää olla saldoton! Työmääräystä tälle tuotteelle ei lisätty";
      }
      if (isset($proggress_bar)) {
        $proggress_bar->increase();
      }
      continue;
    }

    //laitteen toimenpide tuotteen tuotteen_avainsana: tyomaarayksen ryhmittely pitää olla asetettu, myös prioriteetti (selitetark) pitää olla asetettu
    if (empty($laite['toimenpide_tuotteen_tyyppi']) or $laite['toimenpide_tuotteen_prioriteetti'] == null) {
      $virheet[$laite['laite_tunnus']][] = 'laitteen toimenpide tuotteen tuotteen_avainsana: tyomaarayksen ryhmittely pitää olla asetettu, myös prioriteetti (selitetark) pitää olla asetettu';
      if (isset($proggress_bar)) {
        $proggress_bar->increase();
      }
      continue;
    }

    if ($edellinen_kohde_tunnus == 0 or $edellinen_kohde_tunnus != $laite['kohde_tunnus']) {
      $lisaa_kulut = true;
    }
    else {
      $lisaa_kulut = false;
    }

    generoi_tyomaarays($laite, $asiakkaat, $tuotteet, $laitteiden_huoltosyklirivit_joita_ei_huolleta, $lisaa_kulut);

    $edellinen_kohde_tunnus = $laite['kohde_tunnus'];

    if (isset($proggress_bar)) {
      $proggress_bar->increase();
    }
  }

  $tyomaarays_kpl = count($laitteet) - count($virheet);
  if ($debug) {
    echo "<br/>";
    echo "Työmääräyksiä tuli {$tyomaarays_kpl}";
    echo "<br/>";
    foreach ($virheet as $laitteen_virheet) {
      foreach ($laitteen_virheet as $laitteen_virhe) {
        echo $laitteen_virhe;
        echo "<br/>";
      }
    }
  }

  if (false) {
    foreach ($laitteet as $huollettavien_laitteiden_huoltosyklirivi) {
      if (!empty($huollettavien_laitteiden_huoltosyklirivi['toimenpide_tuotteen_tyyppi'])) {
        echo "Kohde: {$huollettavien_laitteiden_huoltosyklirivi['kohde_nimi']} Paikka: {$huollettavien_laitteiden_huoltosyklirivi['paikka_nimi']} Laite: {$huollettavien_laitteiden_huoltosyklirivi['tuoteno']} generoidaan: {$huollettavien_laitteiden_huoltosyklirivi['toimenpide_tuotteen_tyyppi']}";
        echo "<br/>";
      }
      else {
        echo "<font class='error'>HUOM!! Laitteen: {$huollettavien_laitteiden_huoltosyklirivi['laite_tunnus']} huoltosyklin toimenpide tuotteen: {$huollettavien_laitteiden_huoltosyklirivi['toimenpide']} tuotteen avainsana (ryhmittely) on päivittämättä </font>";
        echo "<br/>";
      }
    }

    foreach ($laitteiden_huoltosyklirivit_joita_ei_huolleta as $laite_tunnus => $laitteen_huoltosyklirivit_joita_ei_huolleta) {
      foreach ($laitteen_huoltosyklirivit_joita_ei_huolleta as $laitteen_huoltosyklirivi_jota_ei_huolleta) {
        echo "Laitteelle: {$laite_tunnus} EI generoida huoltoa: {$laitteen_huoltosyklirivi_jota_ei_huolleta['toimenpide_tuotteen_tyyppi']}";
        echo "<br/>";
      }
    }
    echo "<br/>";
    echo "<br/>";
  }

  return $tyomaarays_kpl;
}

function generoi_tyomaarays_temp($huollettavan_laitteen_huoltosyklirivi) {
  global $kukarow, $yhtiorow, $debug;

  $kukarow['kesken'] = 0;

  $huoltovalit = huoltovali_options();
  $huollettavan_laitteen_huoltovali = $huoltovalit[$huollettavan_laitteen_huoltosyklirivi['huoltovali']];
  $toimaika = date("Y-m-d", strtotime($huollettavan_laitteen_huoltosyklirivi['viimeinen_tapahtuma'] . " + {$huollettavan_laitteen_huoltovali['years']} years"));

  $tyomaarays_tunnus = luo_tyomaarays($huollettavan_laitteen_huoltosyklirivi['asiakas_tunnus'], $toimaika, 0, false, $huollettavan_laitteen_huoltosyklirivi['vastuuhenkilo'], true);

  if (empty($tyomaarays_tunnus)) {
    echo t("Joku meni pieleen");
    return false;
  }

  $laskurow = hae_tyomaarays_temp($tyomaarays_tunnus);

  $kukarow['kesken'] = $laskurow['tunnus'];

  //lisätään sammuttimen toimenpiteen palvelurivi
  $trow = hae_tuote($huollettavan_laitteen_huoltosyklirivi['toimenpide']);

  $params = array(
      'tyomaarays_tunnus' => $tyomaarays_tunnus,
      'tuote'             => $trow,
      'laite_tunnus'      => $huollettavan_laitteen_huoltosyklirivi['laite_tunnus'],
      'toimaika'          => $toimaika,
      'toimitettuaika'    => '0000-00-00 00:00:00',
      'alv_23'            => false,
      'temp'              => true,
  );
  $tilausrivi_tunnus = luo_tilausrivi($params);

  paivita_laite_tunnus_toimenpiteen_tilausriville_temp($huollettavan_laitteen_huoltosyklirivi, $tilausrivi_tunnus);
  paivita_tyojono_ja_tyostatus_tyomaaraykselle_temp($tyomaarays_tunnus, $huollettavan_laitteen_huoltosyklirivi);

  return $tyomaarays_tunnus;
}

/**
 *
 * @global array $kukarow
 * @global array $yhtiorow
 * @global boolean $debug
 * @param array $laite
 * @param array $laitteiden_huoltosyklirivit_joita_ei_huolleta
 * @param string $poikkeus_paiva Jos halutaan generoida työmääräyksiä jollekkin muulle päivälle kuin CURRENT_DATE (konversio)
 *
 * param tarvittavia tietoja:
 * asiakas_tunnus
 * toimenpide_tuotteen_tyyppi
 * toimenpide
 * laite_tunnus
 * huoltosykli_tunnus
 * tuoteno (laitteen tuoteno)
 * kohde_nimi
 * paikka_nimi
 * tyojono
 * viimeinen_tapahtuma (optional)
 */
function generoi_tyomaarays($laite, &$asiakkaat, &$tuotteet, $laitteiden_huoltosyklirivit_joita_ei_huolleta = array(), $lisaa_kulut = false) {
  global $kukarow, $yhtiorow, $debug;

  $kukarow['kesken'] = 0;

  $huoltovalit = huoltovali_options();
  $huoltovali = $huoltovalit[$laite['huoltovali']];

  $toimitusaika = date("Y-m-d", strtotime($laite['viimeinen_tapahtuma'] . " + {$huoltovali['years']} years"));
  //Fail safe. Jos viimeinen tapahtuma tyhjä niin generoidaan työmääräys tälle päivälle.
  //Tähän iffiin ei pitäisi ikinä joutua, koska laitteen luontivaiheessa asetetaan viimeiset_tapahtumat.
  if (empty($laite['viimeinen_tapahtuma'])) {
    $toimitusaika = date("Y-m-d", strtotime('now'));
  }

  $edellinen_tyomaarays = hae_laitteen_edellinen_tyomaarays($laite['laite_tunnus']);
  $asiakas = $asiakkaat[$laite['asiakas_tunnus']];
  $laskurow['tunnus'] = luo_tyomaarays($asiakas, $toimitusaika, 0.00, $laite['kohde_vastuuhenkilo']);

  if (empty($laskurow['tunnus'])) {
    echo t("Joku meni pieleen");
    return false;
  }

  $kukarow['kesken'] = $laskurow['tunnus'];

  //lisätään sammuttimen toimenpiteen palvelurivi
  $trow = $tuotteet[$laite['toimenpide']];

  if (isset($laite['tilausrivi_hinta']) and $laite['tilausrivi_hinta'] != '') {
    $hinta = $laite['tilausrivi_hinta'];
  }
  else {
    $hinta = $trow['hinta'];
  }

  if (isset($laite['ale']) and $laite['ale'] != '') {
    $ale = $laite['ale'];
  }
  else {
    $ale = 0;
  }

  $toimitettu = '0000-00-00 00:00:00';
  $params = array(
      'tyomaarays_tunnus' => $laskurow['tunnus'],
      'tuote'             => $trow,
      'laite_tunnus'      => $laite['laite_tunnus'],
      'toimaika'          => $toimitusaika,
      'toimitettu'        => $toimitettu,
      'alv_23'            => false,
      'temp'              => false,
      'hinta'             => $hinta,
      'ale1'              => $ale,
  );
  $tilausrivi_tunnus = luo_tilausrivi($params);

  if ($lisaa_kulut) {
    $kaynti_trow = $tuotteet['KAYNTI'];
    $params = array(
        'tyomaarays_tunnus' => $laskurow['tunnus'],
        'tuote'             => $kaynti_trow,
        'laite_tunnus'      => 0,
        'toimaika'          => $toimitusaika,
        'toimitettu'        => $toimitettu,
        'alv_23'            => false,
        'temp'              => false,
        'hinta'             => 0,
        'ale1'              => 0,
    );
    luo_tilausrivi($params);
  }

  paivita_viimenen_tapahtuma_laitteen_huoltosyklille($laite['laite_tunnus'], $laite['huoltosykli_tunnus'], $toimitusaika);

  if (!empty($edellinen_tyomaarays) and $edellinen_tyomaarays['tyostatus'] == 'K') {
    $kommentti = t('Edellisellä kerralla') . " ({$edellinen_tyomaarays['toimitettuaika']}) " . t('laitteelle ei voitu suorittaa toimenpidettä') . " {$edellinen_tyomaarays['toimenpidetuote_nimitys']} " . t('koska laitetta ei löytynyt');
    paivita_tilausrivin_kommentti($tilausrivi_tunnus, $kommentti);
  }

  if (!empty($laitteiden_huoltosyklirivit_joita_ei_huolleta)) {
    paivita_laitteen_muiden_jonossa_olevien_huoltosyklien_viimeinen_tapahtumapaiva($laitteiden_huoltosyklirivit_joita_ei_huolleta, $laite['laite_tunnus'], $toimitusaika);
  }

  return $laskurow['tunnus'];
}

function luo_tyomaarays($asiakas, $toimaika, $summa, $alv_23 = true, $vastuuhenkilo = '', $temp = false) {
  global $kukarow, $yhtiorow;

  if ($alv_23) {
    $alv = '23.00';
  }
  else {
    $alv = '24.00';
  }
  $maksuehdot = hae_maksuehdot();
  $maksuehto = $maksuehdot[0];
  if (empty($maksuehto)) {
    $maksuehto['tunnus'] = 0;
  }

  $lasku = 'lasku';
  $tyomaarays = 'tyomaarays';
  $laskun_lisatiedot = 'laskun_lisatiedot';
  if ($temp) {
    $lasku = 'lasku_temp';
    $tyomaarays = 'tyomaarays_temp';
    $laskun_lisatiedot = 'laskun_lisatiedot_temp';
  }

  $query = "INSERT INTO {$lasku} SET
            yhtio = '{$kukarow['yhtio']}',
            yhtio_nimi = '{$yhtiorow['nimi']}',
            yhtio_osoite = '{$yhtiorow['osoite']}',
            yhtio_postino = '{$yhtiorow['postino']}',
            yhtio_postitp = '{$yhtiorow['postitp']}',
            yhtio_maa = '{$yhtiorow['maa']}',
            yhtio_ovttunnus = '{$yhtiorow['ovttunnus']}',
            yhtio_kotipaikka = '{$yhtiorow['kotipaikka']}',
            yhtio_toimipaikka = '{$yhtiorow['toimipaikka']}',
            nimi = '{$asiakas['nimi']}',
            nimitark = '{$asiakas['nimitark']}',
            osoite = '{$asiakas['osoite']}',
            osoitetark = '{$asiakas['osoitetark']}',
            postino = '{$asiakas['postino']}',
            postitp = '{$asiakas['postitp']}',
            maa = '{$asiakas['maa']}',
            toim_nimi = '{$asiakas['toim_nimi']}',
            toim_nimitark = '{$asiakas['toim_nimitark']}',
            toim_osoite = '{$asiakas['osoite']}',
            toim_postino = '{$asiakas['postino']}',
            toim_postitp = '{$asiakas['postitp']}',
            toim_maa = '{$asiakas['maa']}',
            valkoodi = 'EUR',
            alv = '{$alv}',
            toimaika = '{$toimaika}',
            kerayspvm = NOW(),
            summa = '{$summa}',
            hinta = '{$summa}',
            lahetetyyppi = 'tulosta_lahete_eiale_eihinta.inc',
            laskutyyppi = '-9',
            myyja = '{$kukarow['tunnus']}',
            maksuehto = '{$maksuehto['tunnus']}',
            toimitustapa = 'Nouto',
            ytunnus = '{$asiakas['ytunnus']}',
            ovttunnus = '{$asiakas['ovttunnus']}',
            toim_ovttunnus = '{$asiakas['ovttunnus']}',
            chn = '100',
            mapvm = '0000-00-00',
            popvm = '0000-00-00 00:00:00',
            alv_tili = '{$yhtiorow['alv']}',
            tila = 'A',
            alatila = '',
            muutospvm = NOW(),
            muuttaja = '{$kukarow['kuka']}',
            laatija = '{$kukarow['kuka']}',
            luontiaika = NOW(),
            tilaustyyppi = 'A',
            liitostunnus = '{$asiakas['tunnus']}',
            ohjelma_moduli = 'PUPESOFT'";
  pupe_query($query);
  $tunnus = mysql_insert_id();

  $query = "INSERT INTO {$laskun_lisatiedot} SET
            yhtio = '{$kukarow['yhtio']}',
            otunnus = '{$tunnus}',
            kolm_ovttunnus = 'FI',
            kolm_maa = '{$asiakas['maa']}',
            laskutus_nimi = '{$asiakas['nimi']}',
            laskutus_osoite = '{$asiakas['osoite']}',
            laskutus_postino = '{$asiakas['postino']}',
            laskutus_postitp = '{$asiakas['postitp']}',
            laskutus_maa = '{$asiakas['maa']}',
            muutospvm = NOW(),
            muuttaja = '{$kukarow['kuka']}',
            laatija = '{$kukarow['kuka']}',
            luontiaika = NOW()";
  pupe_query($query);

  $query = "INSERT INTO {$tyomaarays} SET
            yhtio = '{$kukarow['yhtio']}',
            laatija = '{$kukarow['kuka']}',
            luontiaika = NOW(),
            tyostatus = 'A',
            tyojono = '{$vastuuhenkilo}',
            otunnus = '{$tunnus}'";
  pupe_query($query);

  return $tunnus;
}

function luo_tilausrivi(array $params) {
  global $kukarow, $yhtiorow;

  $tyomaarays_tunnus = $params['tyomaarays_tunnus'];
  $tuote = $params['tuote'];
  $laite_tunnus = $params['laite_tunnus'];
  $toimaika = $params['toimaika'];
  $toimitettu = $params['toimitettu'];
  $temp = $params['temp'];

  $alv_23 = $params['alv_23'];
  if (!isset($params['alv_23'])) {
    $alv_23 = false;
  }

  $hinta = $params['hinta'];
  if (!isset($params['hinta'])) {
    $hinta = $tuote['myyntihinta'];
  }

  if (!is_numeric($hinta)) {
    $hinta = 0;
  }

  $ale1 = $params['ale1'];
  if (!isset($params['ale1'])) {
    $ale1 = 0;
  }

  if ($alv_23) {
    $alv = 23;
  }
  else {
    $alv = 24;
  }

  $tilausrivi = 'tilausrivi';
  $tilausrivin_lisatiedot = 'tilausrivin_lisatiedot';
  if ($temp) {
    $tilausrivi = 'tilausrivi_temp';
    $tilausrivin_lisatiedot = 'tilausrivin_lisatiedot_temp';
  }

  $query = "INSERT INTO {$tilausrivi} SET
            yhtio = '{$kukarow['yhtio']}',
            tyyppi = 'L',
            toimaika = '{$toimaika}',
            kerayspvm = '{$toimaika}',
            otunnus = '{$tyomaarays_tunnus}',
            tuoteno = '{$tuote['tuoteno']}',
            try = '{$tuote['try']}',
            osasto = 0,
            nimitys = '{$tuote['nimitys']}',
            kpl = 0.00,
            kpl2 = 0.00,
            tilkpl = 1.00,
            yksikko = '',
            varattu = 1.00,
            jt = 0.00,
            hinta = {$hinta},
            hinta_valuutassa = 0.00,
            hinta_alkuperainen = 0.00,
            alv = {$alv},
            rivihinta = 0.00,
            erikoisale = 0.00,
            erikoisale_saapuminen = 0.00,
            ale1 = {$ale1},
            ale2 = 0.00,
            ale3 = 0.00,
            kate = 0.00,
            kommentti = '',
            laatija = 'import',
            laadittu = NOW(),
            keratty = 'saldoton',
            kerattyaika = NOW(),
            toimitettu = 'import',
            toimitettuaika = '{$toimitettu}',
            varastoon = 1";

  pupe_query($query);
  $tunnus = mysql_insert_id();

  $query = "INSERT INTO {$tilausrivin_lisatiedot} SET
            yhtio = '{$kukarow['yhtio']}',
            tilausrivitunnus = '{$tunnus}',
            asiakkaan_positio = '{$laite_tunnus}',
            vanha_otunnus = '{$tyomaarays_tunnus}',
            luontiaika = NOW(),
            laatija = 'import'";
  pupe_query($query);

  return $tunnus;
}

function paivita_laite_tunnus_toimenpiteen_tilausriville_temp($laite, $tilausrivi_tunnus) {
  global $kukarow, $yhtiorow;

  if (empty($tilausrivi_tunnus) or empty($laite['laite_tunnus'])) {
    return false;
  }

  $query = "UPDATE tilausrivin_lisatiedot_temp
            JOIN tilausrivi_temp
            ON ( tilausrivi_temp.yhtio = tilausrivin_lisatiedot_temp.yhtio
              AND tilausrivi_temp.tunnus = tilausrivin_lisatiedot_temp.tilausrivitunnus
              AND tilausrivi_temp.tunnus = {$tilausrivi_tunnus} )
            SET asiakkaan_positio = {$laite['laite_tunnus']}
            WHERE tilausrivin_lisatiedot_temp.yhtio = '{$kukarow['yhtio']}'";
  pupe_query($query);

  return true;
}

function paivita_tyojono_ja_tyostatus_tyomaaraykselle_temp($tyomaarays_tunnus, $laite) {
  global $kukarow, $yhtiorow;

  $query = "UPDATE tyomaarays_temp
            SET tyojono = '{$laite['tyojono']}',
            tyostatus = 'A'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$tyomaarays_tunnus}'";
  pupe_query($query);
}

function paivita_tyojono_ja_tyostatus_tyomaarayksille($tyomaarays_tunnukset, $laite) {
  global $kukarow, $yhtiorow;

  if (!isset($laite['tyojono']) and !isset($laite['tyostatus'])) {
    return false;
  }

  if (empty($tyomaarays_tunnukset)) {
    return false;
  }

  $update = "";
  if (isset($laite['tyojono'])) {
    $update .= "tyojono = '{$laite['tyojono']}',";
  }
  if (isset($laite['tyostatus'])) {
    $update .= "tyostatus = '{$laite['tyostatus']}',";
  }
  $update = substr($update, 0, -1);

  if (!is_array($tyomaarays_tunnukset)) {
    $tyomaarays_tunnukset = array($tyomaarays_tunnukset);
  }

  $query = "UPDATE tyomaarays SET
            {$update}
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus IN ('" . implode("','", $tyomaarays_tunnukset) . "')";
  pupe_query($query);

  return true;
}

function paivita_viimenen_tapahtuma_laitteen_huoltosyklille($laite_tunnus, $huoltosykli_tunnus, $viimeinen_tapahtuma = '') {
  global $kukarow, $yhtiorow;

  if ($viimeinen_tapahtuma != '') {
    $query = "SELECT IFNULL(viimeinen_tapahtuma, '0000-00-00') AS viimeinen_tapahtuma
              FROM huoltosyklit_laitteet
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laite_tunnus = '{$laite_tunnus}'
              AND huoltosykli_tunnus = '{$huoltosykli_tunnus}'";
    $result = pupe_query($query);
    $huoltosykli = mysql_fetch_assoc($result);

    //Jos kannasta löytyvä huoltosyklin viimeinen_tapahtuma on tuoreempi kuin päivitettävä huoltosykli niin ei tehdä päivitystä.
    //Ongelma ainoastaan importissa, koska laitteen tapahtumat eivät tule kronologisessa järjestyksessä.
    if ($huoltosykli['viimeinen_tapahtuma'] >= $viimeinen_tapahtuma) {
      return false;
    }
  }

  if (empty($laite_tunnus) or empty($huoltosykli_tunnus)) {
    return false;
  }
  //Viimeisen tapahtuman manual settiä käytetään konversio-ajossa.
  if (empty($viimeinen_tapahtuma)) {
    $huoltosyklit_laitteet_update = "viimeinen_tapahtuma = CURRENT_DATE";
  }
  else {
    $huoltosyklit_laitteet_update = "viimeinen_tapahtuma = '" . date('Y-m-d', strtotime($viimeinen_tapahtuma)) . "'";
  }

  $query = "UPDATE huoltosyklit_laitteet
            SET {$huoltosyklit_laitteet_update},
            muutospvm = NOW(),
            muuttaja = '{$kukarow['kuka']}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND laite_tunnus = '{$laite_tunnus}'
            AND huoltosykli_tunnus = '{$huoltosykli_tunnus}'";
  pupe_query($query);
}

function paivita_laitteen_muiden_jonossa_olevien_huoltosyklien_viimeinen_tapahtumapaiva(&$laitteiden_huoltosyklirivit_joita_ei_huolleta, $laite_tunnus, $toimitusaika) {
  global $kukarow, $yhtiorow;

  //Haetaan $laitteiden_huoltosyklirivit_joita_ei_huolleta resultista laite_tunnuksella huoltosyklirivit, joille päivitetään viimeiseksi_tapahtumapäiväksi tämä päivä,
  //koska esim. kun suoritetaan koeponnistus, niin se sisältää huollon ja tarkastuksen, jolloin näille huoltosykliriveille pitää päivittää tämä päivä.

  $huoltosyklirivit = search_array_key_for_value_recursive($laitteiden_huoltosyklirivit_joita_ei_huolleta, 'laite_tunnus', $laite_tunnus);

  foreach ($huoltosyklirivit as $huoltosyklirivi) {
    paivita_viimenen_tapahtuma_laitteen_huoltosyklille($laite_tunnus, $huoltosyklirivi['huoltosykli_tunnus'], $toimitusaika);
  }
}

function paivita_tilausrivin_kommentti($tilausrivi_tunnus, $kommentti) {
  global $kukarow, $yhtiorow;

  $query = "SELECT kommentti
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tilausrivi_tunnus}'";
  $result = pupe_query($query);
  $tilausrivi = mysql_fetch_assoc($result);

  $kommentti = $tilausrivi['kommentti'] . ' ' . $kommentti;
  $query = "UPDATE tilausrivi
            SET kommentti = '{$kommentti}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tilausrivi_tunnus}'";
  pupe_query($query);
}

function laitteiden_avoimet_tyomaaraykset() {
  global $kukarow, $yhtiorow;

  //tarkistetaan löytyykö tälle laitteelle kesken tilassa / ei valmis oleva työmääräys
  $query = "SELECT tilausrivin_lisatiedot.asiakkaan_positio AS laite_tunnus,
            tilausrivi.tuoteno AS toimenpide
            FROM lasku
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus )
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tila = 'A'";
  $result = pupe_query($query);

  $tyomaaraykset = array();
  while ($tyomaarays = mysql_fetch_assoc($result)) {
    $tyomaaraykset[$tyomaarays['laite_tunnus']][$tyomaarays['toimenpide']] = true;
  }

  return $tyomaaraykset;
}

function tuotteiden_ei_saldoa() {
  global $kukarow, $yhtiorow;

  $query = "SELECT tuoteno
            FROM tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ei_saldoa = 'o'";
  $result = pupe_query($query);

  $tuotteet = array();
  while ($tuote = mysql_fetch_assoc($result)) {
    $tuotteet[$tuote['tuoteno']] = true;
  }

  return $tuotteet;
}

function aseta_tyomaarayksen_toimitusajankohta($tyomaarays_tunnus, $ajankohta) {
  global $kukarow;

  if (empty($tyomaarays_tunnus)) {
    return false;
  }
  $ajankohta = date("Y-m-d", strtotime($ajankohta));

  $query = "UPDATE lasku
            SET toimaika = '{$ajankohta}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tyomaarays_tunnus}'";
  pupe_query($query);
}

function aseta_tyomaarayksen_tilausrivien_toimaika($tyomaarays_tunnus, $ajankohta) {
  global $kukarow;

  if (empty($tyomaarays_tunnus)) {
    return false;
  }
  $ajankohta = date("Y-m-d", strtotime($ajankohta));

  $query = "UPDATE tilausrivi
            SET toimaika = '{$ajankohta}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$tyomaarays_tunnus}'";
  pupe_query($query);
}

function aseta_tyomaarayksen_toimitusajankohta_temp($tyomaarays_tunnus, $ajankohta) {
  global $kukarow;

  $query = "UPDATE lasku_temp
            SET toimaika = '{$ajankohta}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tyomaarays_tunnus}'";
  pupe_query($query);
}

function aseta_tyomaaraysten_status($lasku_tunnukset, $status) {
  global $kukarow, $yhtiorow;

  if (empty($lasku_tunnukset)) {
    return false;
  }

  $query = "UPDATE tyomaarays
            SET tyostatus = '{$status}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tyomaarays.otunnus IN ('" . implode("','", $lasku_tunnukset) . "')";
  pupe_query($query);
}

function aseta_laitteen_tila($laite_tunnus, $tila) {
  global $kukarow, $yhtiorow;

  if (empty($laite_tunnus)) {
    return false;
  }

  $query = "UPDATE laite
            SET tila = '{$tila}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$laite_tunnus}'";
  pupe_query($query);
}

function hae_laitteen_edellinen_tyomaarays($laite_tunnus) {
  global $kukarow, $yhtiorow;

  if (empty($laite_tunnus)) {
    return false;
  }

  $query = "SELECT tyomaarays.*,
            tilausrivin_lisatiedot.asiakkaan_positio AS laite_tunnus,
            tilausrivi.nimitys AS toimenpidetuote_nimitys,
            tilausrivi.toimitettuaika
            FROM tyomaarays
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = tyomaarays.yhtio
              AND tilausrivi.otunnus = tyomaarays.otunnus )
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
              AND tilausrivin_lisatiedot.asiakkaan_positio = '{$laite_tunnus}')
            WHERE tyomaarays.yhtio = '{$kukarow['yhtio']}'
            ORDER BY tilausrivi.toimitettuaika DESC
            LIMIT 1";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function merkkaa_tyomaarays_tehdyksi($request) {
  global $kukarow, $yhtiorow;

  $query = "UPDATE lasku
            SET tila = 'L',
            alatila = 'D'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus IN ('" . implode("','", $request['lasku_tunnukset']) . "')";
  pupe_query($query);

  //Toimitettuaika manual settiä käytetään konversio-ajossa sekä käyttöliittymässä
  $valid = FormValidator::validateItem($request['toimitettuaika'], 'paiva');
  if ($valid) {
    $poikkeava_toimitettuaika = date('Y-m-d H:i:s', strtotime($request['toimitettuaika']));
    $toimitettuaika_update = "toimitettuaika = '{$poikkeava_toimitettuaika}'";
  }
  else {
    $toimitettuaika_update = "toimitettuaika = NOW()";
  }

  $query = "UPDATE tilausrivi
            SET toimitettu = '{$kukarow['kuka']}',
            {$toimitettuaika_update}
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus IN ('" . implode("','", $request['lasku_tunnukset']) . "')";
  pupe_query($query);

  foreach ($request['lasku_tunnukset'] as $lasku_tunnus) {
    $query = "SELECT tyostatus
              FROM tyomaarays
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$lasku_tunnus}'";
    $result = pupe_query($query);
    $tyomaarays = mysql_fetch_assoc($result);

    //Jos työmääräyksen status on K = Kateissa niin ei merkitä työmääräystä tehdyksi
    //koska laitetta ei ole tällöin oikeasti huollettu vaan se on kadonnut
    if ($tyomaarays['tyostatus'] != 'K') {
      aseta_tyomaarays_status('X', '', $lasku_tunnus);
    }
  }
}

function hae_tyomaarays($tyomaarays_tunnus) {
  global $kukarow;

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tyomaarays_tunnus}'
            AND tila != 'D'";
  $result = pupe_query($query);
  return mysql_fetch_assoc($result);
}

function hae_tyomaarays_temp($tyomaarays_tunnus) {
  global $kukarow;

  $query = "SELECT *
            FROM lasku_temp
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tyomaarays_tunnus}'
            AND tila != 'D'";
  $result = pupe_query($query);
  return mysql_fetch_assoc($result);
}

function hae_kohde($kohde_tunnus) {
  global $kukarow;

  $query = "SELECT *
            FROM kohde
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$kohde_tunnus}'";
  $kohde_result = pupe_query($query);
  return mysql_fetch_assoc($kohde_result);
}

function onko_huoltosyklilla_laitteita($huoltosykli_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT *
            FROM huoltosyklit_laitteet
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND huoltosykli_tunnus = '{$huoltosykli_tunnus}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    return true;
  }

  return false;
}

function aseta_tyomaarays_var($tilausrivi_tunnus, $var) {
  global $kukarow, $yhtiorow;

  $query = "UPDATE tilausrivi
            SET var = '{$var}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$tilausrivi_tunnus}'";
  pupe_query($query);
}

function aseta_tyomaarays_status($status, $tilausrivi_tunnus = '', $lasku_tunnus = '') {
  global $kukarow, $yhtiorow;

  if (empty($tilausrivi_tunnus) and empty($lasku_tunnus)) {
    throw new Exception("Tilausrivi tunnus ja lasku tunnus eivät molemmat voi olla tyhjiä");
  }

  $join = "";
  $where = "";
  if (!empty($tilausrivi_tunnus)) {
    $join = " JOIN lasku
              ON ( lasku.yhtio = tyomaarays.yhtio
                AND lasku.tunnus = tyomaarays.otunnus )
              JOIN tilausrivi
              ON ( tilausrivi.yhtio = lasku.yhtio
                AND tilausrivi.otunnus = lasku.tunnus
                AND tilausrivi.tunnus = '{$tilausrivi_tunnus}')";
  }
  else if (!empty($lasku_tunnus)) {
    $where = "  AND tyomaarays.otunnus = '{$lasku_tunnus}'";
  }

  $query = "UPDATE tyomaarays
            {$join}
            SET tyomaarays.tyostatus = '{$status}'
            WHERE tyomaarays.yhtio = '{$kukarow['yhtio']}'
            {$where}";
  pupe_query($query);
}

//luodaan huoltosyklirivi huoltosyklityypin perusteella
function huoltosykli_rivi($tyyppi, $request, $tyojono = false) {
  global $tunnus;

  $laite_tunnus = $tunnus;

  if (!empty($laite_tunnus)) {
    $laitteen_huoltosykli = hae_laitteen_huoltosyklit($laite_tunnus, $tyyppi);
    $laitteen_huoltosykli = $laitteen_huoltosykli[0];
  }
  else {
    $laitteen_huoltosykli = $request['huoltosyklit'][$tyyppi];
  }

  $pakollisuus_chk = "";

  $args = array(
      'laite_tunnus' => $laite_tunnus,
      'tyyppi'       => $tyyppi,
      'tuoteno'      => $request['tuoteno'],
  );
  $huoltosyklit = hae_mahdolliset_huoltosyklit($args);

  if ($laitteen_huoltosykli['pakollisuus'] == 1) {
    $pakollisuus_chk = "CHECKED";
  }

  if ($tyojono) {
    $nimi = 'uusi_laite[huoltosyklit]';
  }
  else {
    $nimi = 'laite[huoltosyklit]';
  }

  echo "<tr>";
  echo "<th>" . t('Huoltosykli') . " <br /> {$tyyppi}";
  echo "<input type='hidden' name='{$nimi}[{$tyyppi}][huoltosykli_laite_tunnus]' value='{$laitteen_huoltosykli['huoltosykli_laite_tunnus']}' />";
  echo "</th>";
  echo "<td>";

  echo "<table>";
  echo "<tr>";
  echo "<th>" . t("{$tyyppi}sykli") . "</th>";
  echo "<td>";
  echo "<select class='huoltosykli_select' id='huoltosykli_select_{$tyyppi}' name='{$nimi}[{$tyyppi}][huoltosykli_tunnus]'>";

  $sel = "";
  echo "<option value='0'>Ei huoltosykliä</option>";

  if ($huoltosyklit) {
    foreach ($huoltosyklit as $huoltosykli) {
      if ($laitteen_huoltosykli['huoltosykli_tunnus'] == $huoltosykli['tunnus']) {
        $sel = "SELECTED";
      }
      echo "<option value='{$huoltosykli['tunnus']}' {$sel}>" . $huoltosykli['dropdown_text'] . "</option>";
      $sel = "";
    }
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t("{$tyyppi}vali") . "</th>";
  echo "<td>";

  echo "<select class='huoltovali_select' id='huoltovali_select_{$tyyppi}' name='{$nimi}[{$tyyppi}][huoltovali]'>";

  if ($huoltosyklit) {

    $huoltovali_options = huoltovali_options($laitteen_huoltosykli['tunnus']);
    foreach ($huoltovali_options as $key => $val) {
      if ($key == $laitteen_huoltosykli['huoltovali']) {
        $sel = "SELECTED";
      }
      echo "<option value='{$key}' {$sel}>{$val['dropdown_text']}</option>";
      $sel = "";
    }
  }
  else {
    echo "<option value='0'>Ei huoltoväliä</option>";
  }

  echo "</select>";

  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t("Seuraava tuleva {$tyyppi}") . " (pp.kk.vvvv)</th>";
  echo "<td>";
  echo "<input type='hidden' id='paiva_ei_validi' value='<font class=\"error\">" . t('Päivämäärä väärin') . "</font>' />";
  $seuraava_tuleva_tapahtuma = '';
  if (!empty($laitteen_huoltosykli['viimeinen_tapahtuma'])) {
    $huoltovalit = huoltovali_options();
    $huollettavan_laitteen_huoltovali = $huoltovalit[$laitteen_huoltosykli['huoltovali']];
    $seuraava_tuleva_tapahtuma = date('d.m.Y', strtotime("{$laitteen_huoltosykli['viimeinen_tapahtuma']} + {$huollettavan_laitteen_huoltovali['years']} years"));
  }
  else {
    $seuraava_tuleva_tapahtuma = $laitteen_huoltosykli['seuraava_tuleva_tapahtuma'];
  }
  echo "<input type='text' id='tuleva_toimenpide_{$tyyppi}' class='tuleva_toimenpide' name='{$nimi}[{$tyyppi}][seuraava_tuleva_tapahtuma]' value='{$seuraava_tuleva_tapahtuma}'/>";
  echo "<div class='error_element'>";
  echo "</div>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "</td>";
  echo "</tr>";
}

//haetaan laitteeseen yhdistetyt huoltosyklit
function hae_laitteen_huoltosyklit($laitetunnus, $tyyppi = '') {
  global $kukarow;

  $tuotteen_avainsanat_join = "";
  if (!empty($tyyppi)) {
    $tuotteen_avainsanat_join = "  AND t1.selite = '{$tyyppi}' ";
  }

  $query = "SELECT huoltosykli.tunnus AS huoltosykli_tunnus,
            t1.selite AS huoltosykli_tyyppi,
            t1.selitetark AS huoltosykli_prioriteetti,
            huoltosyklit_laitteet.huoltovali,
            huoltosyklit_laitteet.pakollisuus,
            huoltosyklit_laitteet.viimeinen_tapahtuma,
            huoltosyklit_laitteet.tunnus AS huoltosykli_laite_tunnus
            FROM huoltosyklit_laitteet
            JOIN huoltosykli
            ON ( huoltosykli.yhtio = huoltosyklit_laitteet.yhtio
              AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
            JOIN tuote
            ON ( tuote.yhtio = huoltosykli.yhtio
              AND tuote.tuoteno = huoltosykli.toimenpide )
            JOIN tuotteen_avainsanat AS t1
            ON ( t1.yhtio = tuote.yhtio
              AND t1.tuoteno = tuote.tuoteno
              {$tuotteen_avainsanat_join})
            WHERE huoltosyklit_laitteet.yhtio = '{$kukarow['yhtio']}'
            AND laite_tunnus = '{$laitetunnus}'";

  $result = pupe_query($query);

  $laitteen_huoltosyklit = array();

  while ($huoltosykli = mysql_fetch_assoc($result)) {
    $laitteen_huoltosyklit[] = $huoltosykli;
  }

  if (empty($laitteen_huoltosyklit)) {
    $laitteen_huoltosyklit[] = array(
        'tunnus'                   => 0,
        'huoltovali'               => 0,
        'pakollisuus'              => 0,
        'huoltosykli_laite_tunnus' => 0,
        'viimeinen_tapahtuma'      => '',
    );
  }

  return $laitteen_huoltosyklit;
}

//haetaan sallitut huoltosyklit huoltosyklityypin ja laitenumeron perusteella
function hae_mahdolliset_huoltosyklit(array $args) {
  global $kukarow;

  $laite_tunnus = (isset($args['laite_tunnus'])) ? (int) $args['laite_tunnus'] : 0;
  $tyyppi = (isset($args['tyyppi'])) ? $args['tyyppi'] : '';
  $tuoteno = (isset($args['tuoteno'])) ? $args['tuoteno'] : '';

  if ($laite_tunnus == '' and $tuoteno == '') {
    return false;
  }

  if (!empty($laite_tunnus)) {
    $tuoteno_query = "SELECT tuoteno
                      FROM laite
                      WHERE tunnus = $laite_tunnus
                      AND yhtio = '{$kukarow['yhtio']}'";
    $tuoteno_result = pupe_query($tuoteno_query);
    $tuoteno = mysql_result($tuoteno_result, 0);
  }

  $sammutin_koko_query = "SELECT selite
                          FROM tuotteen_avainsanat
                          WHERE tuoteno = '{$tuoteno}'
                          AND laji = 'sammutin_koko'
                          AND yhtio = '{$kukarow['yhtio']}'";
  $sammutin_koko_result = pupe_query($sammutin_koko_query);
  $sammutin_koko = mysql_result($sammutin_koko_result, 0);

  $sammutin_tyyppi_query = "SELECT selite
                            FROM tuotteen_avainsanat
                            WHERE tuoteno = '{$tuoteno}'
                            AND laji = 'sammutin_tyyppi'
                            AND yhtio = '{$kukarow['yhtio']}'";
  $sammutin_tyyppi_result = pupe_query($sammutin_tyyppi_query);
  $sammutin_tyyppi = mysql_result($sammutin_tyyppi_result, 0);

  return hae_huoltosyklit_tyyppi_koko($sammutin_tyyppi, $sammutin_koko, $tyyppi);
}

function hae_huoltosyklit_tyyppi_koko($sammutin_tyyppi, $sammutin_koko, $tyyppi = '') {
  global $kukarow, $yhtiorow;

  if (empty($sammutin_tyyppi)) {
    return array();
  }

  $where = "";
  if (!empty($sammutin_koko)) {
    $where = " AND huoltosykli.koko = $sammutin_koko";
  }

  $tuotteen_avainsanat_join = "";
  if ($tyyppi != '') {
    $tuotteen_avainsanat_join = " AND tuotteen_avainsanat.selite = '{$tyyppi}'";
  }

  $query = "SELECT huoltosykli.tunnus,
            concat_ws(' - ', tuote.nimitys, huoltosykli.tyyppi, huoltosykli.koko,
              (
                SELECT selitetark
                FROM avainsana
                WHERE avainsana.yhtio = huoltosykli.yhtio
                AND avainsana.selite = huoltosykli.olosuhde
                AND avainsana.laji = 'OLOSUHDE'
              )
            ) as dropdown_text,
            huoltosykli.huoltovali,
            huoltosykli.pakollisuus,
            huoltosykli.tyyppi,
            huoltosykli.koko,
            huoltosykli.olosuhde
            FROM huoltosykli
            JOIN tuote
            ON ( tuote.yhtio = huoltosykli.yhtio
              AND tuote.tuoteno = huoltosykli.toimenpide )
            JOIN tuotteen_avainsanat
            ON ( tuotteen_avainsanat.yhtio = huoltosykli.yhtio
              AND tuotteen_avainsanat.tuoteno = huoltosykli.toimenpide
              {$tuotteen_avainsanat_join} )
            WHERE huoltosykli.yhtio = '{$kukarow['yhtio']}'
            AND huoltosykli.tyyppi = '{$sammutin_tyyppi}'
            {$where}
            ORDER BY tuote.nimitys ASC";

  $huoltosykli_result = pupe_query($query);

  $huoltosyklit = array();

  while ($huoltosykli = mysql_fetch_assoc($huoltosykli_result)) {
    $huoltosyklit[] = $huoltosykli;
  }

  return $huoltosyklit;
}
if (!function_exists('hae_sammuttimien_koot')) {

  function hae_sammuttimien_koot() {
    global $kukarow, $yhtiorow;

    $query = "SELECT DISTINCT selite
              FROM tuotteen_avainsanat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji = 'sammutin_koko'";
    $result = pupe_query($query);

    $sammuttimien_koot = array();
    while ($sammuttimen_koko = mysql_fetch_assoc($result)) {
      $sammuttimien_koot[] = $sammuttimen_koko['selite'];
    }

    return $sammuttimien_koot;
  }
}

if (!function_exists('hae_sammuttimien_tyypit')) {

  function hae_sammuttimien_tyypit() {
    global $kukarow, $yhtiorow;

    $query = "SELECT DISTINCT selite
              FROM tuotteen_avainsanat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND laji = 'sammutin_tyyppi'";
    $result = pupe_query($query);

    $sammuttimien_tyypit = array();
    while ($sammuttimen_tyyppi = mysql_fetch_assoc($result)) {
      $sammuttimien_tyypit[] = $sammuttimen_tyyppi['selite'];
    }

    return $sammuttimien_tyypit;
  }
}

if (!function_exists('hae_mahdolliset_sammutin_tyypit')) {

  function hae_mahdolliset_sammutin_tyypit() {
    return array(
        'jauhesammutin'         => t('Jauhesammutin'),
        'hiilidioksidisammutin' => t('Hiilidioksidisammutin'),
        'nestesammutin'         => t('Nestesammutin'),
        'halonisammutin'        => t('Halonisammutin'),
        'kalvovaahtosammutin'   => t('Kalvovaahtosammutin'),
        'paloposti'             => t('Paloposti'),
        'muistutus'             => t('Muistutus'),
    );
  }
}

if (!function_exists("huoltovali_options")) {

  function huoltovali_options($huoltosykli_tunnus = '') {
    global $kukarow;

    $huoltovali_options = array(
        3650 => array(
            'dropdown_text' => '10 ' . t('vuotta'),
            'days'          => 3650,
            'months'        => 120,
            'years'         => 10,
        ),
        1825 => array(
            'dropdown_text' => '5 ' . t('vuotta'),
            'days'          => 1825,
            'months'        => 60,
            'years'         => 5,
        ),
        730  => array(
            'dropdown_text' => '2 ' . t('vuotta'),
            'days'          => 730,
            'months'        => 24,
            'years'         => 2,
        ),
        365  => array(
            'dropdown_text' => '1 ' . t('vuotta'),
            'days'          => 365,
            'months'        => 12,
            'years'         => 1,
        ),
        330  => array(
            'dropdown_text' => '11 ' . t('kuukautta'),
            'days'          => 330,
            'months'        => 11,
            'years'         => 11 / 12,
        ),
        300  => array(
            'dropdown_text' => '10 ' . t('kuukautta'),
            'days'          => 300,
            'months'        => 10,
            'years'         => 10 / 12,
        ),
        270  => array(
            'dropdown_text' => '9 ' . t('kuukautta'),
            'days'          => 270,
            'months'        => 9,
            'years'         => 9 / 12,
        ),
        240  => array(
            'dropdown_text' => '8 ' . t('kuukautta'),
            'days'          => 240,
            'months'        => 8,
            'years'         => 8 / 12,
        ),
        210  => array(
            'dropdown_text' => '7 ' . t('kuukautta'),
            'days'          => 210,
            'months'        => 7,
            'years'         => 7 / 12,
        ),
        180  => array(
            'dropdown_text' => '6 ' . t('kuukautta'),
            'days'          => 180,
            'months'        => 6,
            'years'         => 6 / 12,
        ),
        150  => array(
            'dropdown_text' => '5 ' . t('kuukautta'),
            'days'          => 150,
            'months'        => 5,
            'years'         => 5 / 12,
        ),
        120  => array(
            'dropdown_text' => '4 ' . t('kuukautta'),
            'days'          => 120,
            'months'        => 4,
            'years'         => 4 / 12,
        ),
        90   => array(
            'dropdown_text' => '3 ' . t('kuukautta'),
            'days'          => 90,
            'months'        => 3,
            'years'         => 3 / 12,
        ),
        60   => array(
            'dropdown_text' => '2 ' . t('kuukautta'),
            'days'          => 60,
            'months'        => 2,
            'years'         => 2 / 12,
        ),
        30   => array(
            'dropdown_text' => '1 ' . t('kuukautta'),
            'days'          => 30,
            'months'        => 1,
            'years'         => 1 / 12,
        ),
    );

    if (empty($huoltosykli_tunnus)) {
      return $huoltovali_options;
    }

    $query = "SELECT huoltovali
              FROM huoltosykli
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = {$huoltosykli_tunnus}";
    $result = pupe_query($query);
    $max_huoltovali = mysql_result($result, 0);

    foreach ($huoltovali_options as $key => $val) {
      if ($key > $max_huoltovali) {
        unset($huoltovali_options[$key]);
      }
    }
    return $huoltovali_options;
  }
}

if (!function_exists('echo_kust_tulevat_tyot_kayttoliittyma')) {

  function echo_kust_tulevat_tyot_kayttoliittyma($request) {
    global $kukarow, $yhtiorow, $toim;

    echo "<div>";

    echo "<form name='tulevat_tyot' method = 'post'>";
    echo "<input type='hidden' name='toim' value='{$toim}'>";
    echo "<input type='hidden' name='ala_tee' value='hae_tyomaaraykset'>";

    echo "<table>";

    echo "<tr>";
    echo "<th>" . t("Asiakas") . "</th>";
    echo "<td colspan='3'>";

    echo livesearch_kentta("asiakashinnasto_haku_form", "ASIAKASHAKU", "valittu_asiakas", 315, $request['valittu_asiakas'], 'EISUBMIT', '', 'valittu_asiakas', 'ei_break_all');

    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>" . t("Alku pvm. Muodossa pp-kk-vvvv") . "</th>";
    echo "<td><input type='text' name='ppa' value='" . $request['ppa'] . "' size='3' /></td>";
    echo "<td><input type='text' name='kka' value='" . $request['kka'] . "' size='3' /></td>";
    echo "<td><input type='text' name='vva' value='" . $request['vva'] . "' size='5' /></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>" . t("Loppu pvm. Muodossa pp-kk-vvvv") . "</th>";
    echo "<td><input type='text' name='ppl' value='" . $request['ppl'] . "' size='3' /></td>";
    echo "<td><input type='text' name='kkl' value='" . $request['kkl'] . "' size='3' /></td>";
    echo "<td><input type='text' name='vvl' value='" . $request['vvl'] . "' size='5' /></td>";
    echo "</tr>";

    echo "</table>";

    echo "<br />";
    echo "<input type='submit' value='" . t("Hae") . "'>";
    echo "</form>";

    echo "</div>";
  }
}

if (!function_exists('hae_paikan_asiakas')) {

  function hae_paikan_asiakas($paikka_tunnus) {
    global $kukarow, $yhtiorow;

    if (empty($paikka_tunnus)) {
      return false;
    }

    $query = "SELECT asiakas.*
              FROM asiakas
              JOIN kohde
              ON ( kohde.yhtio = asiakas.yhtio
                AND kohde.asiakas = asiakas.tunnus )
              JOIN paikka
              ON ( paikka.yhtio = kohde.yhtio
                AND paikka.kohde = kohde.tunnus
                AND paikka.tunnus = {$paikka_tunnus})
              WHERE asiakas.yhtio = '{$kukarow['yhtio']}'";

    $result = pupe_query($query);

    return mysql_fetch_assoc($result);
  }
}

if (!function_exists('hae_asiakkaat')) {

  function hae_asiakkaat() {
    global $kukarow, $yhtiorow;

    $query = "SELECT *
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'";
    $result = pupe_query($query);

    $asiakkaat = array();
    while ($asiakas = mysql_fetch_assoc($result)) {
      $asiakkaat[$asiakas['tunnus']] = $asiakas;
    }

    return $asiakkaat;
  }
}

if (!function_exists('hae_tuotteet')) {

  function hae_tuotteet() {
    global $kukarow, $yhtiorow;

    $query = "SELECT *
              FROM tuote
              WHERE yhtio = '{$kukarow['yhtio']}'";
    $result = pupe_query($query);

    $tuotteet = array();
    while ($tuote = mysql_fetch_assoc($result)) {
      $tuotteet[$tuote['tuoteno']] = $tuote;
    }

    return $tuotteet;
  }
}