<?php

if (strpos($_SERVER['SCRIPT_NAME'], "extranet_tyomaaraykset.php") !== FALSE) {
  require "inc/parametrit.inc";
}

$tyom_parametrit = array(
  'valmnro' => isset($_REQUEST['valmnro']) ? $_REQUEST['valmnro'] : '',
  'asiakkaan_tilausnumero' => isset($_REQUEST['asiakkaan_tilausnumero']) ? $_REQUEST['asiakkaan_tilausnumero'] : '', 
  'merkki' => isset($_REQUEST['merkki']) ? $_REQUEST['merkki'] : '',
  'valmnro' => isset($_REQUEST['valmnro']) ? $_REQUEST['valmnro'] : '',
  'tuotenro' => isset($_REQUEST['tuotenro']) ? $_REQUEST['tuotenro'] : '',
  'sla' => isset($_REQUEST['sla']) ? $_REQUEST['sla'] : '',
  'komm1' => isset($_REQUEST['komm1']) ? $_REQUEST['komm1'] : '',
);

$request = array(
  'toiminto' => isset($_REQUEST['tyom_toiminto']) ? $_REQUEST['tyom_toiminto'] : '',
  'laite_tunnus' => isset($_REQUEST['laite_tunnus']) ? $_REQUEST['laite_tunnus'] : '',
  'tyom_tunnus' => isset($_REQUEST['tyom_tunnus']) ? $_REQUEST['tyom_tunnus'] : '',
  'tyom_parametrit' => $tyom_parametrit
);


#if ($kukarow['extranet'] == '') die(t("Käyttäjän parametrit - Tämä ominaisuus toimii vain extranetissä"));

if (isset($avaa_tyomaarays_nappi)) {
  // Tallennetaan työmääräys järjestelmään
  tallenna_tyomaarays($request);
  $tyom_toiminto = '';
  unset($request['tyom_parametrit']);
}

#if ($kukarow['multi_asiakkuus'] != '') {
  require "asiakasvalinta.inc";
#}

if ($tyom_toiminto == '') {
  piirra_kayttajan_tyomaaraykset();
}
elseif ($tyom_toiminto == 'UUSI') {
  uusi_tyomaarays_formi($laite_tunnus);
}
elseif ($tyom_toiminto == 'MUOKKAA') {
  muokkaa_tyomaarays_formi();
}
elseif ($tyom_toiminto == 'TALLENNA') {
  tallenna_tyomaarays();
}

function piirra_kayttajan_tyomaaraykset() {
  echo "<font class='head'>".t("Työmääräykset")."</font><hr>";

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
  global $kukarow;

  $tyomaaraykset = array();

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
            laite.sla
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
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tila     in ('A','L','N','S','C')
            AND lasku.alatila != 'X'
            AND lasku.liitostunnus = '{$kukarow['oletus_asiakas']}'
            GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22
            ORDER BY ifnull(a5.jarjestys, 9999), ifnull(a2.jarjestys, 9999), a2.selitetark, lasku.toimaika";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $tyomaaraykset[] = $row;
  }

  return $tyomaaraykset;
}

function piirra_tyomaaraysheaderit() {
  $headers = array(
    t('Viite')."<br>".t('Asiakkaan tilausnumero'),
    t('Luontiaika'),
    t('Valmistaja'),
    t('Valmnro')."<br>".t('Tuotenro'),
    t('SLA'),
    t('Luvattu'),
    t('Työjono'),
    t('Työstatus'),
    t('Vian kuvaus'),
    t('Työn toimenpiteet')
  );

  foreach ($headers as $header) {
    echo "<th>$header</th>";
  }
}

function piirra_tyomaaraysrivi($tyomaarays) {
  echo "<tr style='background-color: {$tyomaarays['tyostatusvari']};'>";
  echo "<td>{$tyomaarays['tunnus']} <br> {$tyomaarays['asiakkaan_tilausnumero']}</td>";
  echo "<td>{$tyomaarays['luontiaika']}</td>";
  echo "<td>{$tyomaarays['merkki']}</td>";
  echo "<td>{$tyomaarays['valmnro']} <br> {$tyomaarays['mallivari']}</td>";
  echo "<td>{$tyomaarays['sla']}</td>";
  echo "<td>".tv1dateconv($tyomaarays['luvattu'])."</td>";
  echo "<td>{$tyomaarays['tyojono']}</td>";
  echo "<td>{$tyomaarays['tyostatus']}</td>";
  echo "<td>{$tyomaarays['komm1']}</td>";
  echo "<td>{$tyomaarays['komm2']}</td>";
  echo "</tr>";
}

function piirra_luo_tyomaarays() {
  echo "<br>";
  echo "<form name='uusi_tyomaarays_button'>";
  echo "<input type='hidden' name='tyom_toiminto' value='UUSI'>";
  echo "<input type='submit' value='".t('Uusi työmääräys')."'>";
  echo "</form>";
}

function uusi_tyomaarays_formi($laite_tunnus) {
  echo "<font class='head'>".t("Uusi työmääräys")."</font><hr>";

  echo "<form name ='uusi_tyomaarays_form'>";
  echo "<table>";
  echo "<tr>";
  piirra_tyomaaraysheaderit();
  echo "</tr>";
  echo "<tr>";
  piirra_edit_tyomaaraysrivi($request);
  echo "</tr>";
  echo "</table>";
  echo "<br>";
  echo "<input type='submit' name='avaa_tyomaarays_nappi' value='".t('Avaa työmääräys')."'>";
  echo "</form>";
}

function piirra_edit_tyomaaraysrivi($request) {
  echo "<td><input type='text' name='asiakkaan_tilausnumero' value=''></td>";
  echo "<td></td>";
  echo "<td><input type='text' name='merkki' value=''></td>";
  echo "<td><input type='text' name='valmnro' value=''>";
  echo "<br><br><input type='text' name='tuotenro' value=''></td>";
  echo "<td><input type='text' name='sla' value=''></td>";
  echo "<td></td>";
  echo "<td></td>";
  echo "<td></td>"; 
  echo "<td><textarea cols='40' rows='5' name='komm1'></textarea></td>";
  echo "<td></td>";
}

function tallenna_tyomaarays($request) {
  global $kukarow;

  // Luodaan uusi lasku
  $query  = "INSERT INTO lasku
             SET yhtio = '{$kukarow['yhtio']}',
             luontiaika = now(),
             laatija = '{$kukarow['kuka']}',
             liitostunnus = '{$kukarow['oletus_asiakas']}',
             tilaustyyppi = 'A',
             tila = 'A',
             asiakkaan_tilausnumero = '{$request['tyom_parametrit']['asiakkaan_tilausnumero']}'";
  $result = pupe_query($query);
  $utunnus = mysql_insert_id($GLOBALS["masterlink"]);

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
}
