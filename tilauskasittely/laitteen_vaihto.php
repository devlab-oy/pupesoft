<?php

require('../inc/parametrit.inc');
require('inc/laitetarkista.inc');
require_once('inc/laite_huolto_functions.inc');

if (isset($livesearch_tee) and $livesearch_tee == "TUOTEHAKU") {
  livesearch_tuotehaku();
  exit;
}

enable_ajax();

js_laite();

echo "<font class='head'>" . t("Laitteen vaihto") . ":</font>";
echo "<hr/>";
echo "<br/>";
?>
<style>

</style>
<script>

</script>

<?php

$request = array(
    'tee'                => $tee,
    'tilausrivi_tunnus'  => $tilausrivi_tunnus,
    'lasku_tunnus'       => $lasku_tunnus,
    'uusi_laite'         => $uusi_laite,
    'vanha_laite_tunnus' => $vanha_laite_tunnus,
    'asiakas_tunnus'     => $asiakas_tunnus,
);

$request['laite'] = hae_laite_ja_asiakastiedot($request['tilausrivi_tunnus']);
$request['paikat'] = hae_paikat($request['laite']['asiakas_tunnus']);

if (!empty($request['uusi_laite'])) {
  if (!empty($request['uusi_laite']['varalaite'])) {
    $request['uusi_laite']['tila'] = 'V';
  }
  else {
    $request['uusi_laite']['tila'] = 'N';
  }
  unset($request['uusi_laite']['varalaite']);

  $request['uusi_laite']['valm_pvm'] = date('Y-m-d', strtotime("{$request['uusi_laite']['vv']}-{$request['uusi_laite']['kk']}-{$request['uusi_laite']['pp']}"));
  unset($request['uusi_laite']['vv']);
  unset($request['uusi_laite']['kk']);
  unset($request['uusi_laite']['pp']);

  $request['virheet'] = validoi_uusi_laite($request['uusi_laite']);

  if (!empty($request['virheet'])) {
    $request['tee'] = '';
  }
}

if ($request['tee'] == 'vaihda_laite') {
  //luo_uusi_laite funktio liittää myös laitteen oikealle paikalle asiakkaan laite puuhun
  $request['uusi_laite'] = luo_uusi_laite($request['uusi_laite']);

  //vanha työmääräys rivi pitää asettaa var P tilaan,
  //jotta tekemätön työmääräys rivi ei mene laskutukseen
  //ja tyomaarayksen status Laite vaihdettu tilaan, jotta käyttöliittymä on selkeämpi
  aseta_tyomaarays_var($request['tilausrivi_tunnus'], 'P');
  aseta_tyomaarays_status('V', $request['tilausrivi_tunnus']);

  //pitää luoda uusi työmääräys rivi johon tulee laitteen vaihto tuote tjsp
  $request['vaihto_toimenpide'] = hae_vaihtotoimenpide_tuote();
  $request['vaihto_toimenpide_tyomaarays_tilausrivi_tunnus'] = luo_uusi_tyomaarays_rivi($request['vaihto_toimenpide'], $request['lasku_tunnus']);
  aseta_uuden_tyomaarays_rivin_kommentti($request['vaihto_toimenpide_tyomaarays_tilausrivi_tunnus'], $request['uusi_laite']['kommentti']);

  //vaihtotoimenpide ja uusi laite pitää linkata toisiinsa
  linkkaa_uusi_laite_vaihtotoimenpiteeseen($request['uusi_laite'], $request['vaihto_toimenpide_tyomaarays_tilausrivi_tunnus']);

  //tekemättömästä työmääräys rivistä ei nollata hävinneen laitteen tunnusta.
  //Tekemätön työmääräysrivi ja hävinnyt laite asetetaan poistettu tilaan, joten unlinkkaus ei ole tarpeellinen
  //nollaa_vanhan_toimenpiderivin_asiakas_positio($request['tilausrivi_tunnus']);
  //työmääräykselle pitää liittää uusi laite.
  //HUOM uudesta laitteesta ei ehkä aina haluta luoda uutta työmääräys riviä,
  //jos esim laitetta ei myydä asiakkaalle vaan se menee vain lainaan
  if ($request['uusi_laite']['tila'] == 'N') {
    $request['uusi_laite_tyomaarays_tilausrivi_tunnus'] = luo_uusi_tyomaarays_rivi($request['uusi_laite'], $request['lasku_tunnus']);

    //työmääräykselle lisätään myös uusi laite. tähän rivin pitää linkata vanha toimenpide tilausrivi
    paivita_uuden_toimenpide_rivin_tilausrivi_linkki($request['uusi_laite_tyomaarays_tilausrivi_tunnus'], $request['tilausrivi_tunnus']);
  }

  //kun toimenpide vaihtuu työmääräyksellä niin vanha toimenpide laitetaan P tilaan (ylempänä)
  //ja vanha toimenpide linkataan uuteen toimenpide riviin tilausrivin_lisatiedot.tilausrivilinkki kentän avulla,
  //jotta raportit osaavat näyttää mikä toimenpide vaihdettiin mihin.
  paivita_uuden_toimenpide_rivin_tilausrivi_linkki($request['vaihto_toimenpide_tyomaarays_tilausrivi_tunnus'], $request['tilausrivi_tunnus']);

  if ($request['uusi_laite']['tila'] == 'N') {
    //Jos uusi laite on normaali laite asetetaan vanha laite poistettu tilaan
    aseta_laitteen_tila($request['vanha_laite_tunnus'], 'P');
  }
  else if ($request['uusi_laite']['tila'] == 'V') {
    //Jos laite on varalaite asetetaan vanha laite huollossa tilaan
    aseta_laitteen_tila($request['vanha_laite_tunnus'], 'H');
  }
  else {
    //defaulttina asetetaan vanha laite poistettu tilaan
    aseta_laitteen_tila($request['vanha_laite_tunnus'], 'P');
  }


  echo '<font class="message">' . t("Laite vaihdettu") . '</font>';
  echo "<br/>";
  echo "<br/>";

  //vanha laite on hävinnyt/mennyt rikki. käydään vanhan laitteen työmääräysrivit läpi, ja asetetaan ne poistettu tilaan.
  $poistetut_tilaukset = aseta_vanhan_laitteen_tyomaarays_rivit_poistettu_tilaan($request['vanha_laite_tunnus']);

  if (!empty($poistetut_tilaukset)) {
    $poistetut_tilaukset = implode(', ', $poistetut_tilaukset);
    $poistetut_tilaukset = substr($poistetut_tilaukset, 0, -2);
    echo t('Seuraavat työmääräykset poistettiin, koska niihin liitetty laite on kadonnut/hajonnut') . ': ' . $poistetut_tilaukset;
  }
  else {
    echo t('Laitteella ei ollut muita poistettavia työmääräyksiä');
  }

  $laitteet = hae_laitteet_ja_niiden_huoltosyklit_joiden_huolto_lahestyy($request['asiakas_tunnus']);
  list($huollettavien_laitteiden_huoltosyklirivit, $laitteiden_huoltosyklirivit_joita_ei_huolleta) = paata_mitka_huollot_tehdaan($laitteet);
  $tyomaarays_kpl = generoi_tyomaaraykset_huoltosykleista($huollettavien_laitteiden_huoltosyklirivit, $laitteiden_huoltosyklirivit_joita_ei_huolleta);

  echo "<br/>";
  echo "<font class='message'>" . t('Työmääräyksiä generoitiin muutosten pohjalta') . ": {$tyomaarays_kpl} " . t('kappaletta') . "</font>";
}
else {
  echo_laitteen_vaihto_form($request);
}

require('inc/footer.inc');

function validoi_uusi_laite($uusi_laite) {
  global $kukarow, $yhtiorow;

  $virhe = array();

  $query = "SELECT *
            FROM laite
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = ''";
  $result = pupe_query($query);
  $trow = mysql_fetch_assoc($result);

  for ($i = 1; $i < mysql_num_fields($result); $i++) {
    laitetarkista($uusi_laite, $i, $result, '', $virhe, $trow);
  }

  return $virhe;
}

function luo_uusi_laite($uusi_laite) {
  global $kukarow, $yhtiorow;

  //tämän funktion jälkeen ajetaan aseta_uuden_tyomaarays_rivin_kommentti.
  //alla oleva unset ei vaikuta siihen
  unset($uusi_laite['kommentti']);
  $huoltosyklit = $uusi_laite['huoltosyklit'];
  unset($uusi_laite['huoltosyklit']);

  $uusi_laite['yhtio'] = $kukarow['yhtio'];
  $query = "INSERT INTO
            laite (" . implode(", ", array_keys($uusi_laite)) . ")
            VALUES('" . implode("', '", array_values($uusi_laite)) . "')";
  pupe_query($query);

  $laite_tunnus = mysql_insert_id();

  $query = "SELECT laite.*,
            tuote.nimitys as tuote_nimitys,
            tuote.myyntihinta as tuote_hinta,
            tuote.try as tuote_try
            FROM laite
            JOIN tuote
            ON ( tuote.yhtio = laite.yhtio
              AND tuote.tuoteno = laite.tuoteno )
            WHERE laite.yhtio = '{$kukarow['yhtio']}'
            AND laite.tunnus = '{$laite_tunnus}'";
  $result = pupe_query($query);

  foreach ($huoltosyklit as $huoltosykli) {
    liita_huoltosykli_laitteeseen($laite_tunnus, $huoltosykli);
  }

  return mysql_fetch_assoc($result);
}

function liita_huoltosykli_laitteeseen($laite_tunnus, $huoltosykli) {
  global $kukarow, $yhtiorow;

  $huoltovalit = huoltovali_options();
  $pakollisuus = 0;
  if (!empty($huoltosykli['pakollisuus'])) {
    $pakollisuus = 1;
  }

  $huoltovali = $huoltovalit[$huoltosykli['huoltovali']];
  $viimeinen_tapahtuma_query = "";
  if (!empty($huoltosykli['seuraava_tuleva_tapahtuma'])) {
    $seuraava_tuleva_tapahtuma = date('Y-m-d', strtotime($huoltosykli['seuraava_tuleva_tapahtuma']));
    $viimeinen_tapahtuma = date('Y-m-d', strtotime("{$seuraava_tuleva_tapahtuma} - {$huoltovali['years']} years"));

    $viimeinen_tapahtuma_query = "viimeinen_tapahtuma = '{$viimeinen_tapahtuma}',";
  }
  else {
    $viimeinen_tapahtuma_query = "viimeinen_tapahtuma = CURRENT_DATE,";
  }

  $query = "INSERT INTO huoltosyklit_laitteet
            SET yhtio = '{$kukarow['yhtio']}',
            huoltosykli_tunnus = '{$huoltosykli['huoltosykli_tunnus']}',
            laite_tunnus = '{$laite_tunnus}',
            huoltovali = '{$huoltosykli['huoltovali']}',
            pakollisuus = '{$pakollisuus}',
            {$viimeinen_tapahtuma_query}
            laatija = '{$kukarow['kuka']}',
            luontiaika = NOW()";
  pupe_query($query);
}

function aseta_uuden_tyomaarays_rivin_kommentti($tilausrivi_tunnus, $kommentti) {
  global $kukarow, $yhtiorow;

  $query = "UPDATE tilausrivi
            SET kommentti = '{$kommentti}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = {$tilausrivi_tunnus}";
  pupe_query($query);
}

function luo_uusi_tyomaarays_rivi($tuote, $lasku_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "INSERT INTO tilausrivi
            SET hyllyalue = '',
            hyllynro = '',
            hyllyvali = '',
            hyllytaso = '',
            tilaajanrivinro = '',
            laatija = '{$kukarow['kuka']}',
            laadittu = now(),
            yhtio = '{$kukarow['yhtio']}',
            tuoteno = '{$tuote['tuoteno']}',
            varattu = '0',
            yksikko = '',
            kpl = '0',
            kpl2 = '',
            tilkpl = '1',
            jt = '1',
            ale1 = '0',
            erikoisale = '0.00',
            alv = '0',
            netto = '',
            hinta = '{$tuote['hinta']}',
            kerayspvm = CURRENT_DATE,
            otunnus = '{$lasku_tunnus}',
            tyyppi = 'L',
            toimaika = CURRENT_DATE,
            kommentti = '',
            var = 'J',
            try= '{$tuote['try']}',
            osasto = '0',
            perheid = '0',
            perheid2 = '0',
            tunnus = '0',
            nimitys = '{$tuote['tuote_nimitys']}',
            jaksotettu = ''";
  pupe_query($query);

  $tilausrivi_tunnus = mysql_insert_id();

  $query = "INSERT INTO tilausrivin_lisatiedot
            SET yhtio = '{$kukarow['yhtio']}',
            positio = '',
            toimittajan_tunnus = '',
            tilausrivitunnus = '{$tilausrivi_tunnus}',
            jarjestys = '0',
            vanha_otunnus = '{$lasku_tunnus}',
            ei_nayteta = '',
            ohita_kerays = '',
            luontiaika = now(),
            laatija = '{$kukarow['kuka']}'";
  pupe_query($query);

  return $tilausrivi_tunnus;
}

function hae_laite_ja_asiakastiedot($tilausrivi_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT laite.*,
            laite.tunnus AS laite_tunnus,
            tilausrivi.tunnus AS tilausrivi_tunnus,
            paikka.nimi AS paikka_nimi,
            paikka.tunnus AS paikka_tunnus,
            paikka.olosuhde AS paikka_olosuhde,
            kohde.nimi AS kohde_nimi,
            asiakas.tunnus AS asiakas_tunnus,
            asiakas.nimi AS asiakas_nimi,
            lasku.tunnus AS lasku_tunnus
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            JOIN lasku
            ON ( lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus )
            JOIN laite
            ON ( laite.yhtio = tilausrivin_lisatiedot.yhtio
              AND laite.tunnus = tilausrivin_lisatiedot.asiakkaan_positio )
            JOIN paikka
            ON ( paikka.yhtio = laite.yhtio
              AND paikka.tunnus = laite.paikka )
            JOIN kohde
            ON ( kohde.yhtio = paikka.yhtio
              AND kohde.tunnus = paikka.kohde )
            JOIN asiakas
            ON ( asiakas.yhtio = kohde.yhtio
              AND asiakas.tunnus = kohde.asiakas )
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.tunnus = '{$tilausrivi_tunnus}'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function hae_vaihtotoimenpide_tuote() {
  global $kukarow, $yhtiorow;

  $query = "SELECT tuote.*,
            tuote.nimitys as tuote_nimitys
            FROM tuote
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = 'LAITTEEN_VAIHTO'";
  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function linkkaa_uusi_laite_vaihtotoimenpiteeseen($uusi_laite, $tilausrivi_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "UPDATE tilausrivi
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            SET tilausrivin_lisatiedot.asiakkaan_positio = '{$uusi_laite['tunnus']}'
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.tunnus = '{$tilausrivi_tunnus}'";
  pupe_query($query);
}

function nollaa_vanhan_toimenpiderivin_asiakas_positio($tilausrivi_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "UPDATE tilausrivi
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            SET tilausrivin_lisatiedot.asiakkaan_positio = '0'
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.tunnus = '{$tilausrivi_tunnus}'";
  pupe_query($query);
}

function paivita_uuden_toimenpide_rivin_tilausrivi_linkki($toimenpide_tilausrivi_tunnus, $vanha_tilausrivi_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "UPDATE tilausrivi
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
            SET tilausrivin_lisatiedot.tilausrivilinkki = '{$vanha_tilausrivi_tunnus}'
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.tunnus = '{$toimenpide_tilausrivi_tunnus}'";
  pupe_query($query);
}

function hae_paikat($asiakas_tunnus) {
  global $kukarow, $yhtiorow;

  $query = "SELECT paikka.*
            FROM paikka
            JOIN kohde
            ON ( kohde.yhtio = paikka.yhtio
              AND kohde.tunnus = paikka.kohde )
            JOIN asiakas
            ON ( asiakas.yhtio = kohde.yhtio
              AND asiakas.tunnus = kohde.asiakas
              AND asiakas.tunnus = '{$asiakas_tunnus}')
            WHERE paikka.yhtio = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  $paikat = array();
  while ($paikka = mysql_fetch_assoc($result)) {
    $paikat[] = $paikka;
  }

  return $paikat;
}

function aseta_vanhan_laitteen_tyomaarays_rivit_poistettu_tilaan($vanha_laite_tunnus) {
  global $kukarow, $yhtiorow;

  //Asetetaan vain sellaiset tilausrivit poistettu tilaan, jotka ovat avoimella työmääräyksellä
  $query = "SELECT tilausrivi.otunnus,
            tilausrivi.tunnus AS tilausrivi_tunnus
            FROM tilausrivi
            JOIN tilausrivin_lisatiedot
            ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
              AND tilausrivin_lisatiedot.asiakkaan_positio = '{$vanha_laite_tunnus}' )
            JOIN lasku
            ON ( lasku.yhtio = tilausrivi.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
              AND lasku.tila = 'A'
              AND alatila = '')
            WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
            AND tilausrivi.var != 'P'";
  $result = pupe_query($query);

  $poistettavat_tilausrivit = array();
  $tilaukset = array();
  while ($poistettava_tilausrivi = mysql_fetch_assoc($result)) {
    $poistettavat_tilausrivit[] = $poistettava_tilausrivi['tilausrivi_tunnus'];
    $tilaukset[] = $poistettava_tilausrivi['otunnus'];
  }

  if (!empty($poistettavat_tilausrivit)) {
    $query = "UPDATE tyomaarays
              JOIN lasku
              ON ( lasku.yhtio = tyomaarays.yhtio
                AND lasku.tunnus = tyomaarays.otunnus )
              JOIN tilausrivi
              ON ( tilausrivi.yhtio = lasku.yhtio
                AND tilausrivi.otunnus = lasku.tunnus
                AND tilausrivi.tunnus IN ('" . implode("','", $poistettavat_tilausrivit) . "') )
              SET tyomaarays.tyostatus = 'V',
              tilausrivi.var = 'P'
              WHERE tyomaarays.yhtio = '{$kukarow['yhtio']}'";
    pupe_query($query);
  }

  return $tilaukset;
}

function echo_laitteen_vaihto_form($request = array()) {
  global $kukarow, $yhtiorow, $oikeurow;

  $huoltosyklit = hae_laitteelle_mahdolliset_huoltosyklit('', '', $request['laite']['paikka_olosuhde']);

  if (!empty($request['virheet'])) {
    $virhe_message = implode('<br/>', $request['virheet']);

    echo '<font class="error">' . $virhe_message . '</font>';
    echo '<br/>';
  }

  if (!empty($request['uusi_laite'])) {
    $sarjanro = $request['uusi_laite']['sarjanro'];
    $oma_numero = $request['uusi_laite']['oma_numero'];
    $sijainti = $request['uusi_laite']['sijainti'];
  }
  else {
    $sarjanro = $request['laite']['sarjanro'];
    $oma_numero = $request['laite']['oma_numero'];
    $sijainti = $request['laite']['sijainti'];
  }

  echo "<form name='uusi_laite_form' class='multisubmit' method='POST' action=''>";
  echo "<input type='hidden' name='tee' value='vaihda_laite' />";
  echo "<input type='hidden' name='tilausrivi_tunnus' value='{$request['laite']['tilausrivi_tunnus']}' />";
  echo "<input type='hidden' name='lasku_tunnus' value='{$request['laite']['lasku_tunnus']}' />";
  echo "<input type='hidden' name='vanha_laite_tunnus' value='{$request['laite']['laite_tunnus']}' />";
  echo "<input type='hidden' name='asiakas_tunnus' value='{$request['laite']['asiakas_tunnus']}' />";
  echo "<table>";

  echo "<tr>";
  echo "<th>" . t("Asiakas") . "</th>";
  echo "<td>{$request['laite']['asiakas_nimi']}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t("Kohde") . "</th>";
  echo "<td>{$request['laite']['kohde_nimi']}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t("Paikka") . "</th>";
  echo "<td>{$request['laite']['paikka_nimi']}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t("Vaihdettava laite") . "</th>";
  echo "<td>{$request['laite']['tuoteno']}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>" . t("Uusi laite") . "</th>";
  echo "<td>";

  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo '<th align="left">' . t("Tuotenumero") . '</th>';
  echo "<td id='tuoteno_td'>";
  echo livesearch_kentta('uusi_laite_form', 'TUOTEHAKU', 'uusi_laite[tuoteno]', '226', $request['uusi_laite']['tuoteno'], 'NOSUBMIT');
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo '<th align="left">' . t("Sarjanumero") . '</th>';
  echo "<td>";
  echo '<input type="text" name="uusi_laite[sarjanro]" value="' . $sarjanro . '" size="35" maxlength="60" />';
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo '<th align="left">' . t("Valmistus päivä") . '</th>';
  echo "<td>";
  $vva = substr($request['uusi_laite']['valm_pvm'], 0, 4);
  $kka = substr($request['uusi_laite']['valm_pvm'], 5, 2);
  echo "<input type='hidden' class='valm_pvm_tpp' name='uusi_laite[pp]' value='1' />";
  echo "<select name='uusi_laite[kk]' class='valm_pvm_tkk'>";
  $sel = "";
  foreach (range(1, 12) as $kuukausi) {
    if ($kka == $kuukausi) {
      $sel = "SELECTED";
    }
    echo "<option value='{$kuukausi}' {$sel}>{$kuukausi}</option>";
    $sel = "";
  }
  echo "</select>";
  echo "<select name='uusi_laite[vv]' class='valm_pvm_tvv'>";
  $sel = "";
  if (empty($vuosi_vaihteluvali)) {
    $vuosi_vaihteluvali['min'] = 1970;
    $vuosi_vaihteluvali['max'] = date('Y', strtotime('now + 4 years'));
  }
  foreach (range($vuosi_vaihteluvali['min'], $vuosi_vaihteluvali['max']) as $vuosi) {
    if ($vva == $vuosi) {
      $sel = "SELECTED";
    }
    echo "<option value='{$vuosi}' {$sel}>{$vuosi}</option>";
    $sel = "";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo '<th align="left">' . t("Oma numero") . '</th>';
  echo "<td>";
  echo '<input type="text" name="uusi_laite[oma_numero]" value="' . $oma_numero . '" size="35" maxlength="20" />';
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo '<th align="left">' . t("Paikka") . '</th>';
  echo "<td>";
  echo "<select name='uusi_laite[paikka]'>";
  foreach ($request['paikat'] as $paikka) {
    $selected = ($paikka['tunnus'] == $request['laite']['paikka_tunnus']) ? 'SELECTED' : '';
    echo "<option value='{$paikka['tunnus']}' $selected>{$paikka['nimi']}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo '<th align="left">' . t("Sijainti") . '</th>';
  echo "<td>";
  echo '<input type="text" name="uusi_laite[sijainti]" value="' . $sijainti . '" size="35" maxlength="60" />';
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo '<th align="left">' . t("Kommentti") . '</th>';
  echo "<td>";
  echo '<input type="text" name="uusi_laite[kommentti]" value="' . $request['uusi_laite']['kommentti'] . '" size="35" maxlength="60" />';
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo '<th align="left">' . t("Varalaite") . '</th>';
  echo "<td>";

  $varalaite_chk = '';
  if (!empty($request['uusi_laite']['varalaite'])) {
    $varalaite_chk = 'CHECKED';
  }
  echo '<input type="checkbox" name="uusi_laite[varalaite]" ' . $varalaite_chk . '/>';
  echo "</td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";

  echo "</td>";
  echo "</tr>";

  $query = "SELECT DISTINCT selite
            FROM tuotteen_avainsanat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND laji = 'tyomaarayksen_ryhmittely'";
  $result = pupe_query($query);

  $req = array(
      'tuoteno'      => $request['uusi_laite']['tuoteno'],
      'huoltosyklit' => $huoltosyklit
  );

  while ($selite = mysql_fetch_assoc($result)) {
    huoltosykli_rivi($selite['selite'], $req, true);
  }

  echo "</table>";

  echo "<input type='submit' value='" . t("Vaihda laite") . "' />";
  echo "</form>";
}
