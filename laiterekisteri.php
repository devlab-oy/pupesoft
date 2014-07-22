<?php

if (strpos($_SERVER['SCRIPT_NAME'], "laiterekisteri.php") !== FALSE) {
  require("inc/parametrit.inc");
}

if (isset($tallennetaan_muutokset) and isset($muokattava_laite) and $muokattava_laite > 0) {
  // Tallennetaan muutokset laitteelle
  $kveri = "UPDATE laite
            SET kommentti = '{$kommentti}',
            lcm_info = '{$lcm_info}',
            ip_osoite = '{$ip_osoite}',
            mac_osoite = '{$mac_osoite}',
            muutospvm = now(),
            muuttaja = '{$kukarow['kuka']}'
            WHERE yhtio='{$kukarow['yhtio']}'
            AND tunnus = '{$muokattava_laite}'";
  pupe_query($kveri);
}
elseif (isset($tallenna_uusi_laite) and isset($valitse_sarjanumero) and $valitse_sarjanumero > 0) {
  // Lis‰t‰‰n uusi laite
  $kveri = "INSERT INTO laite
            SET yhtio = '{$kukarow['yhtio']}',
            paikka = '{$uusilaite_myyntirivitunnus}',
            sarjanro = '{$uusilaite_sarjanumero}',
            tuoteno = '{$uusilaite_tuotenumero}',
            lcm_info = '{$lcm_info}',
            ip_osoite = '{$ip_osoite}',
            mac_osoite = '{$mac_osoite}',
            kommentti = '{$kommentti}',
            luontiaika = now(),
            laatija = '{$kukarow['kuka']}'";
  pupe_query($kveri);
  unset($toiminto);
}
elseif (isset($peruuta_uusi)) {
  unset($toiminto);
  unset($valitse_sarjanumero);
  unset($myyntirivitunnus);
  unset($lcm_info);
  unset($ip_osoite);
  unset($mac_osoite);
}


echo "<font class='head'>".t("Laiterekisteri")."</font><hr>";
var_dump($_REQUEST);
$laiterajaus = '';
if (isset($laitetunnus) and $laitetunnus > 0) {
  $laiterajaus = " AND laite.tunnus = '{$laitetunnus}'";
}

$headerit = array(
  "nro",
  "sopimus",
  "valmistaja",
  "malli",
  "sarjanumero",
  "tuotenumero",
  "kommentti",
  "lcm info",
  "ip",
  "mac"
);

// Ekotellaan headerit
echo "<table>";
echo "<tr>";
foreach ($headerit as $hiid) {
  echo "<th>{$hiid}</th>";
}
echo "</tr>";

// Haetaan kaikkien laiterekisterin laitteiden tuotteiden ja sopimusten tiedot
$query = "SELECT
          laite.*,
          avainsana.selitetark valmistaja,
          tuote.tuotemerkki malli,
          if(ifnull(laitteen_sopimukset.sopimusrivin_tunnus, 0),'Kyll‰','Ei') sopimusrivi
          FROM laite
          JOIN tuote on tuote.yhtio = laite.yhtio
            AND tuote.tuoteno = laite.tuoteno
          JOIN avainsana on avainsana.yhtio = tuote.yhtio
            AND avainsana.laji = 'TRY'
            AND avainsana.selite = tuote.try
          LEFT JOIN laitteen_sopimukset on laitteen_sopimukset.laitteen_tunnus = laite.tunnus
          WHERE laite.yhtio = '{$kukarow['yhtio']}'
          {$laiterajaus}";
$res = pupe_query($query);



if ($toiminto == 'MUOKKAA') {
  // Halutaan muuttaa laitteen tietoja

  while ($rowi = mysql_fetch_assoc($res)) {
    echo "<tr>";
    echo "<form>";
    echo "<input type='hidden' name='muokattava_laite' value='{$rowi['tunnus']}'>";
    echo "<td nowrap>{$rowi['tunnus']}</td>";
    echo "<td>".$rowi['sopimusrivi']."</td>";
    // Tuote
    echo "<td nowrap>".$rowi['valmistaja']."</td>";
    echo "<td nowrap>".$rowi['malli']."</td>";
    // Sopimukset 1/2
    echo "<td nowrap>".$rowi['sarjanro']."</td>";
    echo "<td nowrap>".$rowi['tuoteno']."</td>";
     // Sopimukset 2/2
    echo "<td><textarea name='kommentti' rows='5' columns='30'>{$rowi['kommentti']}</textarea></td>";
    echo "<td><textarea name='lcm_info' rows='5' columns='30'>{$rowi['lcm_info']}</textarea></td>";
    echo "<td><input type='text' name='ip_osoite' value='{$rowi['ip_osoite']}'</td>";
    echo "<td><input type='text' name='mac_osoite' value='{$rowi['mac_osoite']}'/></td>";
    echo "<td class='back'><input type='submit' name='tallennetaan_muutokset' value='Tallenna'/></td>";
    echo "<td class='back'><input type='submit' name='peruuta'  value='Peruuta'/></td>";
    echo "</form>";
    echo "</tr>";
  }
  echo "</table>";
}
elseif ($toiminto == 'UUSILAITE') {
  // Halutaan lis‰t‰ uusi laite
  // Esivalintamuuttujat
  $esiv_tuotenumero = $esiv_valmistaja = $esiv_malli = $esiv_sopimus = '';
  echo "<tr>";
  echo "<form>";
  echo "<input type='hidden' name='toiminto' value='UUSILAITE' />";

  //Jos on selectoitu joku sarjanumero niin esit‰ytet‰‰n
  if (isset($valitse_sarjanumero) and $valitse_sarjanumero > 0) {
    $kveri = "SELECT
              sarjanumeroseuranta.sarjanumero,
              tuote.*,
              avainsana.selitetark valmistaja,
              tuote.tuotemerkki malli
              FROM sarjanumeroseuranta
              JOIN tuote on tuote.yhtio = sarjanumeroseuranta.yhtio
                AND tuote.tuoteno = sarjanumeroseuranta.tuoteno
              JOIN avainsana on avainsana.yhtio = sarjanumeroseuranta.yhtio
                AND avainsana.laji = 'TRY'
                AND avainsana.selite = tuote.try
              WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
              AND sarjanumeroseuranta.tunnus = '{$valitse_sarjanumero}'";
    $resu = pupe_query($kveri);
    query_dump($kveri);
    $rivikka = mysql_fetch_assoc($resu);

    $esiv_tuotenumero = $rivikka['tuoteno'];
    $esiv_valmistaja = $rivikka['valmistaja'];
    $esiv_malli = $rivikka['malli'];
    $esiv_sopimus = "Ei";
    echo "<input type='hidden' name='uusilaite_myyntirivitunnus' value ='{$rivikka['myyntirivitunnus']}'/>";
    echo "<input type='hidden' name='uusilaite_sarjanumero' value ='{$rivikka['sarjanumero']}'/>";
    echo "<input type='hidden' name='uusilaite_tuotenumero' value ='{$rivikka['tuoteno']}'/>";
  }
  echo "<td></td>";
  echo "<td>{$esiv_sopimus}</td>";
  echo "<td>{$esiv_valmistaja}</td>";
  echo "<td>{$esiv_malli}</td>";
  // Sopimukset 1/2
  $kveri = "SELECT sarjanumeroseuranta.*
            FROM sarjanumeroseuranta
            /*LEFT JOIN laite on laite.yhtio = sarjanumeroseuranta.yhtio and laite.sarjanro*/
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND myyntirivitunnus = 0
            AND sarjanumero != ''
            AND sarjanumero NOT IN (SELECT sarjanro from laite where yhtio='{$kukarow['yhtio']}')";
  $ressu = pupe_query($kveri);
  echo "<td><select name='valitse_sarjanumero' onchange='submit();'>";
  echo "<option value =''>Valitse</option>";
  while ($rivi = mysql_fetch_assoc($ressu)) {
    $sel = $rivi['tunnus'] == $valitse_sarjanumero ? "SELECTED" : "";
    echo "<option value='{$rivi['tunnus']}' $sel>{$rivi['sarjanumero']}</option>";
  }
  echo "</select></td>";
  
  echo "<td>{$esiv_tuotenumero}</td>";
  
   // Sopimukset 2/2
  echo "<td><textarea name='kommentti' rows='5' columns='30'>{$rowi['kommentti']}</textarea></td>";
  echo "<td><textarea name='lcm_info' rows='5' columns='30'>{$rowi['lcm_info']}</textarea></td>";
  echo "<td><input type='text' name='ip_osoite'/></td>";
  echo "<td><input type='text' name='mac_osoite'/></td>";
  echo "<td class='back'><input type='submit' name='tallenna_uusi_laite' value='Tallenna'/></td>";
  echo "<td class='back'><input type='submit' name='peruuta_uusi'  value='Peruuta'/></td>";
  echo "</form>";
  echo "</tr>";
}
else {
  
  //echo "<th>Nro</th>";
  // ----------Sopimukset1/2
  // Sopimusnumero
  //echo "<th>Sopimus</th>";
  // LCC k/e
  // MDM k/e
  // NOC k/e
  // Service Desk k/e
  // LCM k/e

  // Invoice site

  // ---- Tuote
  //echo "<th>Valmistaja</th>";
  //echo "<th>Malli</th>";
  // ----- Laite 1/2
  //echo "<th>Sarjanumero</th>";
  //echo "<th>Tuotenumero</th>";
  // ----------Sopimukset2/2
  // LCC SLA
  // LCC start date
  // LCC end date
  // VC
  // VC No
  // VC end date
  // VC SLA
  // MDM end date
  // SD SLA
  // NOC e/kk
  // MDM e/kk
  // LCC e/kk
  // LCM e/kk

  // ----- Laite 2/2
  //echo "<th>Kommentti</th>";
  //echo "<th>LCM info</th>";
  //echo "<th>IP</th>";
  //echo "<th>MAC</th>";

  //echo "</tr>";
  while ($rowi = mysql_fetch_assoc($res)) {
    echo "<tr>";

    echo "<td nowrap><a href='{$palvelin2}/laiterekisteri.php?toiminto=MUOKKAA&laitetunnus=$rowi[tunnus]'>{$rowi['tunnus']}</a></td>";
    echo "<td>".$rowi['sopimusrivi']."</td>";
    // Tuote
    echo "<td nowrap>".$rowi['valmistaja']."</td>";
    echo "<td nowrap>".$rowi['malli']."</td>";
    // Sopimukset 1/2
    echo "<td nowrap>".$rowi['sarjanro']."</td>";
    echo "<td nowrap>".$rowi['tuoteno']."</td>";
     // Sopimukset 2/2
    echo "<td style='width:300px;'>".$rowi['kommentti']."</td>";
    echo "<td>".$rowi['lcm_info']."</td>";
    echo "<td nowrap>".$rowi['ip_osoite']."</td>";
    echo "<td nowrap>".$rowi['mac_osoite']."</td>";

    echo "</tr>";
  }
  echo "</table>";
  echo "<br><br>";
  echo "<form>";
  echo "<input type='hidden' name='toiminto' value ='UUSILAITE' />";
  echo "<input type='submit' name='asd' value='Uusi laite' />";
  echo "</form>";
}
