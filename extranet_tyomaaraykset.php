<?php

if (strpos($_SERVER['SCRIPT_NAME'], "extranet_tyomaaraykset.php") !== FALSE) {
  require "parametrit.inc";
}

$tyom_parametrit = array(
  'valmnro' => isset($_REQUEST['valmnro']) ? $_REQUEST['valmnro'] : '',
  'asiakkaan_tilausnumero' => isset($_REQUEST['asiakkaan_tilausnumero']) ? $_REQUEST['asiakkaan_tilausnumero'] : '',
  'valmistaja' => isset($_REQUEST['valmistaja']) ? $_REQUEST['valmistaja'] : '',
  'malli' => isset($_REQUEST['malli']) ? $_REQUEST['malli'] : '',
  'valmnro' => isset($_REQUEST['valmnro']) ? $_REQUEST['valmnro'] : '',
  'tuotenro' => isset($_REQUEST['tuotenro']) ? $_REQUEST['tuotenro'] : '',
  'sla' => isset($_REQUEST['sla']) ? $_REQUEST['sla'] : '',
  'komm1' => isset($_REQUEST['komm1']) ? $_REQUEST['komm1'] : '',
);

$osoite_parametrit = array(
  'toim_nimi' => isset($_REQUEST['toim_nimi']) ? $_REQUEST['toim_nimi'] : '',
  'toim_osoite' => isset($_REQUEST['toim_osoite']) ? $_REQUEST['toim_osoite'] : '',
  'toim_postitp' => isset($_REQUEST['toim_postitp']) ? $_REQUEST['toim_postitp'] : '',
  'toim_postino' => isset($_REQUEST['toim_postino']) ? $_REQUEST['toim_postino'] : '',
  'toim_maa' => isset($_REQUEST['toim_maa']) ? $_REQUEST['toim_maa'] : '',

  'laskutus_nimi' => isset($_REQUEST['laskutus_nimi']) ? $_REQUEST['laskutus_nimi'] : '',
  'laskutus_osoite' => isset($_REQUEST['laskutus_osoite']) ? $_REQUEST['laskutus_osoite'] : '',
  'laskutus_postitp' => isset($_REQUEST['laskutus_postitp']) ? $_REQUEST['laskutus_postitp'] : '',
  'laskutus_postino' => isset($_REQUEST['laskutus_postino']) ? $_REQUEST['laskutus_postino'] : '',
  'laskutus_maa' => isset($_REQUEST['laskutus_maa']) ? $_REQUEST['laskutus_maa'] : '',

  'tilausyhteyshenkilo' => isset($_REQUEST['tilausyhteyshenkilo']) ? $_REQUEST['tilausyhteyshenkilo'] : '',
);

$request = array(
  'tyom_toiminto' => isset($_REQUEST['tyom_toiminto']) ? $_REQUEST['tyom_toiminto'] : '',
  'laite_tunnus' => isset($_REQUEST['laite_tunnus']) ? $_REQUEST['laite_tunnus'] : '',
  'tyom_tunnus' => isset($_REQUEST['tyom_tunnus']) ? $_REQUEST['tyom_tunnus'] : '',
  'nayta_poistetut' => isset($_REQUEST['nayta_poistetut']) ? $_REQUEST['nayta_poistetut'] : '',
  'tyom_parametrit' => $tyom_parametrit,
  'osoite_parametrit' => $osoite_parametrit
);


if ($kukarow['extranet'] == '') die(t("Käyttäjän parametrit - Tämä ominaisuus toimii vain extranetissä"));

if (isset($avaa_tyomaarays_nappi)) {
  // Tallennetaan työmääräys järjestelmään
  tallenna_tyomaarays($request);
  $tyom_toiminto = '';
  unset($request['tyom_parametrit']);
}

require "asiakasvalinta.inc";

if ($request['tyom_toiminto'] == '') {
  piirra_kayttajan_tyomaaraykset();
}
elseif ($request['tyom_toiminto'] == 'UUSI') {
  uusi_tyomaarays_formi($laite_tunnus);
}
elseif ($request['tyom_toiminto'] == 'EMAIL_KOPIO') {
  email_tyomaarayskopio($request);
  piirra_kayttajan_tyomaaraykset();
}

function piirra_kayttajan_tyomaaraykset() {
  echo "<font class='head'>".t("Työmääräykset")."</font><hr>";
  piirra_nayta_aktiiviset_poistetut();
  $naytettavat_tyomaaraykset = hae_kayttajan_tyomaaraykset();
  if (count($naytettavat_tyomaaraykset) > 0) {
    echo "<form name ='tyomaaraysformi'>";
    echo "<table>";
    echo "<tr>";
    piirra_tyomaaraysheaderit();
    echo "</tr>";

    foreach ($naytettavat_tyomaaraykset as $tyomaarays) {
      piirra_tyomaaraysrivi($tyomaarays);
    }

    echo "</table>";
    echo "</form>";
  }
  else {
    echo "<br><font class='error'>".t('Työmääräyksiä ei löydy järjestelmästä')."!</font><br/>";
  }

  piirra_luo_tyomaarays();
}

function hae_kayttajan_tyomaaraykset() {
  global $kukarow, $request;

  $tyomaaraykset = array();

  if ($kukarow['oletus_asiakas'] == '') {
    return $tyomaaraykset;
  }
  $alatila = " AND lasku.alatila != 'X' ";
  if (!empty($request['nayta_poistetut'])) {
    $alatila = " AND lasku.alatila = 'X' ";
  }

  $query = "SELECT
            lasku.tunnus,
            lasku.viesti,
            lasku.nimi,
            lasku.tila,
            lasku.alatila,
            lasku.tilaustyyppi,
            lasku.ytunnus,
            lasku.toimaika,
            lasku.asiakkaan_tilausnumero,
            tyomaarays.komm1,
            tyomaarays.komm2,
            tyomaarays.tyojono,
            tyomaarays.tyostatus,
            kuka.nimi myyja,
            a1.selite tyojonokoodi,
            a1.selitetark tyojono,
            a2.selitetark tyostatus,
            a2.selitetark_2 tyostatusvari,
            yhtio.nimi yhtio,
            yhtio.yhtio yhtioyhtio,
            a3.nimi suorittajanimi,
            a5.selitetark tyom_prioriteetti,
            lasku.luontiaika,
            group_concat(a4.selitetark_2) asekalsuorittajanimi,
            group_concat(concat(left(kalenteri.pvmalku,16), '##', left(kalenteri.pvmloppu,16), '##', if(a4.selitetark_2 is null or a4.selitetark_2 = '', kalenteri.kuka, a4.selitetark_2), '##', kalenteri.tunnus, '##', a4.selitetark, '##', timestampdiff(SECOND, kalenteri.pvmalku, kalenteri.pvmloppu))) asennuskalenteri,
            tyomaarays.valmnro,
            tyomaarays.mallivari,
            tyomaarays.merkki,
            tyomaarays.luvattu,
            laite.sla,
            a6.selitetark valmistaja,
            tuote.tuotemerkki malli
            FROM lasku
            JOIN yhtio ON (lasku.yhtio=yhtio.yhtio)
            JOIN tyomaarays ON (tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus )
            LEFT JOIN laskun_lisatiedot ON (lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus)
            LEFT JOIN kuka ON (kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja)
            LEFT JOIN avainsana a1 ON (a1.yhtio=tyomaarays.yhtio and a1.laji='TYOM_TYOJONO'   and a1.selite=tyomaarays.tyojono)
            LEFT JOIN avainsana a2 ON (a2.yhtio=tyomaarays.yhtio and a2.laji='TYOM_TYOSTATUS' and a2.selite=tyomaarays.tyostatus)
            LEFT JOIN kuka a3 ON (a3.yhtio=tyomaarays.yhtio and a3.kuka=tyomaarays.suorittaja)
            LEFT JOIN kalenteri ON (kalenteri.yhtio = lasku.yhtio and kalenteri.tyyppi = 'asennuskalenteri' and kalenteri.liitostunnus = lasku.tunnus)
            LEFT JOIN avainsana a4 ON (a4.yhtio=kalenteri.yhtio and a4.laji='TYOM_TYOLINJA'  and a4.selitetark=kalenteri.kuka)
            LEFT JOIN avainsana a5 ON (a5.yhtio=tyomaarays.yhtio and a5.laji='TYOM_PRIORIT' and a5.selite=tyomaarays.prioriteetti)
            LEFT JOIN laite ON (laite.yhtio = lasku.yhtio and laite.sarjanro = tyomaarays.valmnro)
            LEFT JOIN tuote ON (tuote.yhtio = laite.yhtio and tuote.tuoteno = laite.tuoteno)
            LEFT JOIN avainsana a6 ON (a6.yhtio = tuote.yhtio and a6.laji = 'TRY' and a6.selite = tuote.try)
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tila     in ('A','L','N','S','C')
            {$alatila}
            AND lasku.liitostunnus = '{$kukarow['oletus_asiakas']}'
            GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22
            ORDER BY lasku.tunnus";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $tyomaaraykset[] = $row;
  }

  return $tyomaaraykset;
}

function piirra_tyomaaraysheaderit($rajattu = false) {
  $headers = array(
    t('Huoltopyyntö') => true,
    t('Asiakkaan tilausnumero') => false,
    t('Luontiaika') => true,
    t('Valmistaja') => false,
    t('Sarjanumero')." / ".t('Malli') => false,
    t('Työstatus') => true,
    t('Viankuvaus') => false,
    t('Työn toimenpiteet') => true
  );

  foreach ($headers as $header => $rajataan) {
    if ($rajattu and $rajataan) continue;
    echo "<th>$header</th>";
  }
}

function piirra_tyomaaraysrivi($tyomaarays) {
  echo "<tr style='background-color: {$tyomaarays['tyostatusvari']};'>";
  echo "<td>{$tyomaarays['tunnus']}</td>";
  echo "<td>{$tyomaarays['asiakkaan_tilausnumero']}</td>";
  echo "<td>{$tyomaarays['luontiaika']}</td>";
  echo "<td>{$tyomaarays['valmistaja']}</td>";
  echo "<td>{$tyomaarays['valmnro']} / {$tyomaarays['mallivari']}</td>";
  echo "<td>{$tyomaarays['tyostatus']}</td>";
  echo "<td>{$tyomaarays['komm1']}</td>";
  echo "<td>{$tyomaarays['komm2']}</td>";
  echo "<td class='back'>";
  echo "<a href='extranet_tyomaaraykset.php?tyom_tunnus={$tyomaarays['tunnus']}&tyom_toiminto=EMAIL_KOPIO'>".t('Huoltopyyntökopio sähköpostiin')."</a>";
  echo "</td>";
  echo "</tr>";
}

function piirra_luo_tyomaarays() {
  echo "<br>";
  echo "<form name='uusi_tyomaarays_button'>";
  echo "<input type='hidden' name='tyom_toiminto' value='UUSI'>";
  echo "<input type='submit' value='".t('Uusi huoltopyyntö')."'>";
  echo "</form>";
}

function piirra_nayta_aktiiviset_poistetut() {
  global $request;
  echo "<br>";
  echo "<form name='uusi_tyomaarays_button'>";
  if (!empty($request['nayta_poistetut'])) {
    echo "<input type='hidden' name='nayta_poistetut' value=''>";
    echo "<input type='submit' value='".t('Näytä aktiiviset')."'>";
  }
  else {
    echo "<input type='hidden' name='nayta_poistetut' value='JOO'>";
    echo "<input type='submit' value='".t('Näytä suljetut')."'>";
  }
  echo "</form>";
  echo "<br><br>";
}

function uusi_tyomaarays_formi($laite_tunnus) {
  echo "<font class='head'>".t("Uusi huoltopyyntö")."</font><hr>";
  // Jos ollaan tultu laiterekisteristä ja halutaan tehdä työmääräys tietylle laitteelle
  if (!empty($laite_tunnus)) {
    $request['tyom_parametrit'] = hae_laitteen_parametrit($laite_tunnus);
  }
  $asiakasdata = hae_asiakasdata();
  echo "<form name ='uusi_tyomaarays_form'>";
  echo "<table>";
  echo "<tr>";
  piirra_tyomaaraysheaderit(true);
  echo "</tr>";
  echo "<tr>";
  piirra_edit_tyomaaraysrivi($request, true);
  echo "</tr>";
  echo "</table>";
  echo "<br>";
  piirra_yhteyshenkilontiedot_taulu();
  piirra_toimitusosoite_taulu($asiakasdata);
  piirra_laskutusosoite_taulu($asiakasdata);
  echo "<input type='submit' name='avaa_tyomaarays_nappi' value='".t('Avaa huoltopyyntö')."'>";
  echo "</form>";
}

function piirra_edit_tyomaaraysrivi($request, $piilota = false) {
  if (!$piilota) echo "<td></td>";
  echo "<td><input type='text' name='asiakkaan_tilausnumero' value='{$request['tyom_parametrit']['asiakkaan_tilausnumero']}'></td>";
  if (!$piilota) echo "<td></td>";
  echo "<td><input type='text' name='valmistaja' value='{$request['tyom_parametrit']['valmistaja']}'></td>";
  echo "<td><input type='text' name='valmnro' value='{$request['tyom_parametrit']['valmnro']}'>";
  echo "<br><br><input type='text' name='tuotenro' value='{$request['tyom_parametrit']['tuotenro']}'></td>";
  if (!$piilota) echo "<td></td>";
  echo "<td><textarea cols='40' rows='5' name='komm1'>{$request['tyom_parametrit']['komm1']}</textarea></td>";
  if (!$piilota) echo "<td></td>";
}

function piirra_yhteyshenkilontiedot_taulu() {
  global $kukarow;

  $yhteysquery = "SELECT nimi
                  FROM yhteyshenkilo
                  where yhtio              = '$kukarow[yhtio]'
                  and liitostunnus         = '$kukarow[oletus_asiakas]'
                  and tyyppi               = 'A'
                  and tilausyhteyshenkilo != ''
                  and oletusyhteyshenkilo != ''
                  ORDER BY nimi
                  LIMIT 1";
  $yhteysresult = pupe_query($yhteysquery);
  
  $tilausyhteyshenkilo = '';
  if ($yhteysrow = mysql_fetch_assoc($yhteysresult)) {
    $tilausyhteyshenkilo = $yhteysrow['nimi'];
  }
  echo "<br>";
  echo "<table>";
  echo "<tr><th colspan='4'>".t('Tilausyhteyshenkilö')."</th><td><input type='text' name='tilausyhteyshenkilo' value='{$tilausyhteyshenkilo}'></td></tr>";
  echo "</table>";
}

function piirra_toimitusosoite_taulu($asiakas) {
  echo "<br>";
  echo "<table>";
  echo "<tr><th colspan='2'>".t('Toimitusosoite')."</th></tr>";
  echo "<tr><th>".t('Nimi')."</th>";
  echo "<td><input type='text' name='toim_nimi' value='{$asiakas['toim_nimi']}'></td></tr>";

  echo "<tr><th>".t('Osoite')."</th>";
  echo "<td><input type='text' name='toim_osoite' value='{$asiakas['toim_osoite']}'></td></tr>";

  echo "<tr><th>".t('Postinumero')."</th>";
  echo "<td><input type='text' name='toim_postino' value='{$asiakas['toim_postino']}'></td></tr>";
  
  echo "<tr><th>".t('Postitoimipaikka')."</th>";
  echo "<td><input type='text' name='toim_postitp' value='{$asiakas['toim_postitp']}'></td></tr>";

  echo "<tr><th>".t('Maa')."</th>";
  echo "<td><input type='text' name='toim_maa' value='{$asiakas['toim_maa']}'></td></tr>";
  echo "</table>";
}

function piirra_laskutusosoite_taulu($asiakas) {
  echo "<br>";
  echo "<table>";
  echo "<tr><th colspan='2'>".t('Laskutusosoite')."</th></tr>";
  echo "<tr><th>".t('Nimi')."</th>";
  echo "<td><input type='text' name='laskutus_nimi' value='{$asiakas['laskutus_nimi']}'></td></tr>";

  echo "<tr><th>".t('Osoite')."</th>";
  echo "<td><input type='text' name='laskutus_osoite' value='{$asiakas['laskutus_osoite']}'></td></tr>";

  echo "<tr><th>".t('Postinumero')."</th>";
  echo "<td><input type='text' name='laskutus_postino' value='{$asiakas['laskutus_postino']}'></td></tr>";
  
  echo "<tr><th>".t('Postitoimipaikka')."</th>";
  echo "<td><input type='text' name='laskutus_postitp' value='{$asiakas['laskutus_postitp']}'></td></tr>";

  echo "<tr><th>".t('Maa')."</th>";
  echo "<td><input type='text' name='laskutus_maa' value='{$asiakas['laskutus_maa']}'></td></tr>";
  echo "</table>";  
}

function tallenna_tyomaarays($request) {
  global $kukarow;

  $asiakastiedot = hae_asiakasdata();
  // Luodaan uusi lasku
  $query  = "INSERT INTO lasku
             SET yhtio = '{$kukarow['yhtio']}',
             luontiaika = now(),
             laatija = '{$kukarow['kuka']}',
             nimi = '{$asiakastiedot['nimi']}',
             nimitark = '{$asiakastiedot['nimitark']}',
             osoite = '{$asiakastiedot['osoite']}',
             postino = '{$asiakastiedot['postino']}',
             postitp = '{$asiakastiedot['postitp']}',
             maa = '{$asiakastiedot['maa']}',
             toim_nimi     = '{$request['osoite_parametrit']['toim_nimi']}',
             toim_nimitark = '{$request['osoite_parametrit']['toim_nimitark']}',
             toim_osoite   = '{$request['osoite_parametrit']['toim_osoite']}',
             toim_postino  = '{$request['osoite_parametrit']['toim_postino']}',
             toim_postitp  = '{$request['osoite_parametrit']['toim_postitp']}',
             toim_maa      = '{$request['osoite_parametrit']['toim_maa']}',
             ytunnus = '{$asiakastiedot['ytunnus']}',
             liitostunnus = '{$kukarow['oletus_asiakas']}',
             tilaustyyppi = 'A',
             tila = 'A',
             tilausyhteyshenkilo = '{$request['osoite_parametrit']['tilausyhteyshenkilo']}',
             asiakkaan_tilausnumero = '{$request['tyom_parametrit']['asiakkaan_tilausnumero']}'";
  $result = pupe_query($query);
  $utunnus = mysql_insert_id($GLOBALS["masterlink"]);

  $query = "INSERT INTO laskun_lisatiedot SET
            laskutus_nimi     = '{$request['osoite_parametrit']['laskutus_nimi']}',
            laskutus_nimitark = '{$request['osoite_parametrit']['laskutus_nimitark']}',
            laskutus_osoite   = '{$request['osoite_parametrit']['laskutus_osoite']}',
            laskutus_postino  = '{$request['osoite_parametrit']['laskutus_postino']}',
            laskutus_postitp  = '{$request['osoite_parametrit']['laskutus_postitp']}',
            laskutus_maa      = '{$request['osoite_parametrit']['laskutus_maa']}',
            yhtio             = '{$kukarow['yhtio']}',
            otunnus           = '{$utunnus}',
            laatija           = '{$kukarow['kuka']}',
            luontiaika        = now()";
  $lisatiedot_result = pupe_query($query);

  // Luodaan uusi työmääräys
  $query  = "INSERT INTO tyomaarays
             SET yhtio = '{$kukarow['yhtio']}',
             luontiaika = now(),
             otunnus = '{$utunnus}',
             laatija = '{$kukarow['kuka']}',
             tyostatus = 'O',
             prioriteetti = '3',
             hyvaksy = 'Kyllä',
             komm1 = '{$request['tyom_parametrit']['komm1']}',
             sla = '{$request['tyom_parametrit']['sla']}',
             mallivari = '{$request['tyom_parametrit']['tuotenro']}',
             valmnro = '{$request['tyom_parametrit']['valmnro']}',
             merkki = '{$request['tyom_parametrit']['merkki']}'";
  $result  = pupe_query($query);

  $request['tyom_tunnus'] = $utunnus;
  email_tyomaarayskopio($request);
}

function hae_laitteen_parametrit($laite_tunnus) {
  global $kukarow;

  $laiteparametrit = array();

  $query = "SELECT
            laite.*,
            avainsana.selitetark valmistaja,
            tuote.tuotemerkki malli
            FROM laite
            LEFT JOIN tuote ON (tuote.yhtio = laite.yhtio
            AND tuote.tuoteno = laite.tuoteno)
            LEFT JOIN avainsana ON (avainsana.yhtio = tuote.yhtio
            AND avainsana.laji = 'TRY'
            AND avainsana.selite = tuote.try)
            WHERE laite.yhtio = '{$kukarow['yhtio']}'
            AND laite.tunnus = '{$laite_tunnus}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  $laiteparametrit['valmistaja'] = $row['valmistaja'];
  $laiteparametrit['malli'] = $row['malli'];
  $laiteparametrit['valmnro'] = $row['sarjanro'];
  $laiteparametrit['tuotenro'] = $row['tuotenro'];
  $laiteparametrit['sla'] = $row['sla'];

  return $laiteparametrit;
}

function hae_asiakasdata() {
  global $kukarow;               

  // Haetaan oletusasiakkuus
  $query = "SELECT asiakas.*,
            IF(laskutus_nimi = '', nimi, laskutus_nimi) laskutus_nimi,
            IF(laskutus_osoite = '', osoite, laskutus_osoite) laskutus_osoite,
            IF(laskutus_postino = '', postino, laskutus_postino) laskutus_postino,
            IF(laskutus_postitp = '', postitp, laskutus_postitp) laskutus_postitp,
            IF(laskutus_maa = '', maa, laskutus_maa) laskutus_maa,
            IF(toim_nimi = '', nimi, toim_nimi) toim_nimi,
            IF(toim_osoite = '', osoite, toim_osoite) toim_osoite,
            IF(toim_postino = '', postino, toim_postino) toim_postino,
            IF(toim_postitp = '', postitp, toim_postitp) toim_postitp,
            IF(toim_maa = '', maa, toim_maa) toim_maa
            FROM asiakas
            WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
            AND asiakas.tunnus = '{$kukarow['oletus_asiakas']}'";
  $result = pupe_query($query);
  $asiakasdata = mysql_fetch_assoc($result);
  return $asiakasdata;
}

function email_tyomaarayskopio($request) {
  global $kukarow;

  require_once "tulosta_tyomaarays.inc";

  //Tehdään joini
  $query = "SELECT tyomaarays.*, lasku.*
            FROM lasku
            LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            and lasku.tunnus  = '{$request['tyom_tunnus']}'";
  $result = pupe_query($query);
  $laskurow = mysql_fetch_assoc($result);

  //haetaan asiakkaan tiedot
  $query = "SELECT luokka, puhelin, if (asiakasnro!='', asiakasnro, ytunnus) asiakasnro, asiakasnro as asiakasnro_aito
            FROM asiakas
            WHERE tunnus = '{$laskurow['liitostunnus']}'
            and yhtio    = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);
  $asrow = mysql_fetch_assoc($result);

  $yhtiorow =  hae_yhtion_parametrit($kukarow['yhtio']);
  $query_ale_lisa = generoi_alekentta('M');
  $sorttauskentta = generoi_sorttauskentta($yhtiorow["tyomaarayksen_jarjestys"]);
  $order_sorttaus = $yhtiorow["tyomaarayksen_jarjestys_suunta"];

  if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
  else $pjat_sortlisa = "";

  //työmääräyksen rivit
  $query = "SELECT tilausrivi.*,
            round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1), '$yhtiorow[hintapyoristys]') hinta,
            round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1) * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa}, $yhtiorow[hintapyoristys]) rivihinta_verollinen,
            round(tilausrivi.hinta / if (lasku.vienti_kurssi > 0, lasku.vienti_kurssi, 1) * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * {$query_ale_lisa},'$yhtiorow[hintapyoristys]') rivihinta,
            $sorttauskentta,
            if (tuote.tuotetyyppi='K','2 Työt','1 Muut') tuotetyyppi,
            if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
            tuote.sarjanumeroseuranta
            FROM tilausrivi
            JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
            JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
            WHERE tilausrivi.otunnus  = '{$laskurow['tunnus']}'
            and tilausrivi.yhtio      = '{$kukarow['yhtio']}'
            and tilausrivi.tyyppi    != 'D'
            and tilausrivi.var       != 'O'
            ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
  $result = pupe_query($query);

  //generoidaan rivinumerot
  $rivinumerot = array();

  $kal = 1;

  while ($row = mysql_fetch_assoc($result)) {
    $rivinumerot[$row["tunnus"]] = $kal;
    $kal++;
  }

  mysql_data_seek($result, 0);

  if ((isset($tyomtyyppi) and $tyomtyyppi == "O") or $kukarow['hinnat'] != 0) {
    $tyyppi = "O";
  }
  elseif (isset($tyomtyyppi) and $tyomtyyppi == "P") {
    $tyyppi = "P";
  }
  elseif (isset($tyomtyyppi) and $tyomtyyppi == "A") {
    $tyyppi = "";
  }
  elseif (isset($tyomtyyppi) and $tyomtyyppi == "Q") {
    $tyyppi = "Q";
  }
  else {
    $tyyppi = $yhtiorow["tyomaaraystyyppi"];
  }

  $params_tyomaarays = array( "asrow"           => $asrow,
    "boldi"           => $boldi,
    "edtuotetyyppi"   => "",
    "iso"             => $iso,
    "kala"            => 0,
    "kieli"           => $kieli,
    "komento"      => $komento["Työmääräys"],
    "laskurow"        => $laskurow,
    "lineparam"       => $lineparam,
    "norm"            => $norm,
    "page"            => NULL,
    "pdf"             => NULL,
    "perheid"         => 0,
    "perheid2"        => 0,
    "pieni"           => $pieni,
    "pieni_boldi"     => $pieni_boldi,
    "rectparam"       => $rectparam,
    "returnvalue"     => 0,
    "rivinkorkeus"    => $rivinkorkeus,
    "rivinumerot"     => $rivinumerot,
    "row"             => NULL,
    "sivu"            => 1,
    "tee"             => $tee,
    "thispage"      => NULL,
    "toim"            => $toim,
    "tots"        => 0,
    "tyyppi"          => $tyyppi, );

  // Aloitellaan lomakkeen teko
  $params_tyomaarays = tyomaarays_alku($params_tyomaarays);

  if ($yhtiorow["tyomaarayksen_palvelutjatuottet"] == "") {
    // Ekan sivun otsikot
    $params_tyomaarays['kala'] -= $params_tyomaarays['rivinkorkeus']*3;
    $params_tyomaarays = tyomaarays_rivi_otsikot($params_tyomaarays);
  }

  while ($row = mysql_fetch_assoc($result)) {
    $params_tyomaarays["row"] = $row;
    $params_tyomaarays = tyomaarays_rivi($params_tyomaarays);
  }

  if ($yhtiorow['tyomaarays_tulostus_lisarivit'] == 'L') {
    $params_tyomaarays["tots"] = 1;
    $params_tyomaarays = tyomaarays_loppu_lisarivit($params_tyomaarays);
  }
  else {
    $params_tyomaarays["tots"] = 1;
    $params_tyomaarays = tyomaarays_loppu($params_tyomaarays);
  }

  $params_tyomaarays['komento'] = 'email';

  //tulostetaan sivu
  tyomaarays_print_pdf($params_tyomaarays);
}
