<?php

// DataTables p‰‰lle
$pupe_DataTables = "selaalaitteita";

if (strpos($_SERVER['SCRIPT_NAME'], "laiterekisteri.php") !== FALSE) {
  require("inc/parametrit.inc");
}

pupe_DataTables(array(array($pupe_DataTables, 13, 13, true, true)));

if (isset($tallennetaan_muutokset) and isset($muokattava_laite) and $muokattava_laite > 0) {
  // Tallennetaan muutokset laitteelle
  $kveri = "UPDATE laite
            SET kommentti = '{$kommentti}',
            sla = '{$sla}',
            lcm_info = '{$lcm_info}',
            ip_osoite = '{$ip_osoite}',
            mac_osoite = '{$mac_osoite}',
            muutospvm = now(),
            muuttaja = '{$kukarow['kuka']}'
            WHERE yhtio='{$kukarow['yhtio']}'
            AND tunnus = '{$muokattava_laite}'";
  pupe_query($kveri);
}
elseif (isset($tallenna_uusi_laite) and isset($valitse_sarjanumero) and $valitse_sarjanumero > 0 and !isset($muokattava_laite)) {
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
            sla = '{$sla}',
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
  "sopimustiedot",
  "asiakastiedot",
  "sla",
  "kommentti",
  "lcm info",
  "ip",
  "mac"
);

if ($toiminto == 'LINKKAA') {
  // Halutaan linkata laitteita sopimuksen palveluriville

  if (!isset($tilausrivin_tunnus) or $tilausrivin_tunnus < 1) {
    echo "Et voi linkata laitteita ilman tilausrivin tunnusta";
    exit;
  }

  // Uusi sarake mihin piirret‰‰n checkboxit
  array_unshift($headerit, '');

  // Joka kierroksella haetaan valitut laitteet uudestaan
  if (isset($valitut_laitteet) and isset($vanhat_laitteet)) {

    foreach ($valitut_laitteet as $laite) {
      // Tarkistetaan oliko valittu laite jo linkattu
      if(!in_array($laite, $vanhat_laitteet)) {

        $query = "INSERT INTO laitteen_sopimukset
                  SET sopimusrivin_tunnus = '{$tilausrivin_tunnus}',
                  laitteen_tunnus = '{$laite}'";
        pupe_query($query);
      }
    }

    foreach ($vanhat_laitteet as $vanhalaite) {
      // Tarkistetaan oliko kaikki vanhat laitteet linkattuna vai pit‰‰kˆ poistaa joku niist‰
      if (!in_array($vanhalaite, $valitut_laitteet)) {
        $query = "DELETE FROM laitteen_sopimukset
                  WHERE laitteen_tunnus = '{$vanhalaite}'
                  AND sopimusrivin_tunnus = '{$tilausrivin_tunnus}'";
        pupe_query($query);
      }
    }
  }
  elseif (isset($valitut_laitteet)) {

    foreach($valitut_laitteet as $laite) {
      $query = "INSERT INTO laitteen_sopimukset
                SET sopimusrivin_tunnus = '{$tilausrivin_tunnus}',
                laitteen_tunnus = '{$laite}'";
      pupe_query($query);
    }
  }

  // P‰ivitet‰‰n (palvelu)tilausriville oikea kappalem‰‰r‰
  $query = "SELECT 
            count(*) lkm
            FROM laitteen_sopimukset
            WHERE sopimusrivin_tunnus = '{$tilausrivin_tunnus}'";
  $res = pupe_query($query);
  $laiterivi = mysql_fetch_assoc($res);

  if ($laiterivi['lkm'] > 0) {
    $query = "UPDATE tilausrivi
              SET tilkpl = '{$laiterivi['lkm']}',
              varattu = '{$laiterivi['lkm']}'
              WHERE tunnus = '{$tilausrivin_tunnus}'
              AND yhtio = '{$kukarow['yhtio']}'
              AND netto = ''";
    pupe_query($query);
  }
  unset($valitut_laitteet);
  unset($vanhat_laitteet);
}


// Ekotellaan headerit
echo "<table class='display dataTable' id='$pupe_DataTables'>";
echo "<thead>";
echo "<tr>";
foreach ($headerit as $hiid) {
  echo "<th>{$hiid}</th>";
}
echo "</tr>";

// Hakukent‰t
if (empty($toiminto)) {
  echo "<tr>";
  foreach ($headerit as $hiid) {
    echo "<td><input type='text' class='search_field' name='search_{$hiid}'/></td>";
  }
  echo "</tr>";
}
echo "</thead>";
// Haetaan kaikkien laiterekisterin laitteiden tuotteiden ja sopimusten tiedot
$query = "SELECT
          laite.*,
          avainsana.selitetark valmistaja,
          tuote.tuotemerkki malli,
          if(ifnull(laitteen_sopimukset.sopimusrivin_tunnus, 0),'Kyll‰','Ei') sopimusrivi,
          group_concat(laitteen_sopimukset.sopimusrivin_tunnus) sopimusrivin_tunnukset
          FROM laite
          JOIN tuote on tuote.yhtio = laite.yhtio
            AND tuote.tuoteno = laite.tuoteno
          JOIN avainsana on avainsana.yhtio = tuote.yhtio
            AND avainsana.laji = 'TRY'
            AND avainsana.selite = tuote.try
          LEFT JOIN laitteen_sopimukset on laitteen_sopimukset.laitteen_tunnus = laite.tunnus
          WHERE laite.yhtio = '{$kukarow['yhtio']}'
          {$laiterajaus}
          GROUP BY laite.sarjanro,laite.tuoteno";
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
    echo "<td></td><td></td>";
     // Sopimukset 2/2
    echo "<td><input type='text' name='sla' value='{$rowi['sla']}'/></td>";
    echo "<td><textarea name='kommentti' rows='5' columns='30'>{$rowi['kommentti']}</textarea></td>";
    echo "<td><textarea name='lcm_info' rows='5' columns='30'>{$rowi['lcm_info']}</textarea></td>";
    echo "<td><input type='text' name='ip_osoite' value='{$rowi['ip_osoite']}'/></td>";
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
  $esiv_tuotenumero = $esiv_valmistaja = $esiv_malli = $esiv_sopimus = '';
  echo "<tr>";
  echo "<form>";
  echo "<input type='hidden' name='toiminto' value='UUSILAITE' />";

  //Jos on selectoitu joku sarjanumero niin t‰ytet‰‰n sarakkeet joihin ei voi vaikuttaa
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

  // Haetaan sarjanumerot jotka eiv‰t ole viel‰ laite-taulussa
  $kveri = "SELECT sarjanumeroseuranta.*
            FROM sarjanumeroseuranta
            WHERE yhtio = '{$kukarow['yhtio']}'
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
  echo "<td></td><td></td>";
  echo "<td><input type='text' name='sla' value='{$rowi['sla']}'/></td>";
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

  if ($toiminto == "LINKKAA") echo "Muokataan sopimusrivin {$tilausrivin_tunnus} laitteita<br><br>";
  echo "<form>";
  while ($rowi = mysql_fetch_assoc($res)) {
    $asiakas = '';
    
    echo "<tr>";
    if ($toiminto == "LINKKAA") {
      $sel = '';
      if (strpos($rowi['sopimusrivin_tunnukset'], $tilausrivin_tunnus) !== false) {
        $sel = ' CHECKED';
        echo "<input type='hidden' name='vanhat_laitteet[]' value ='{$rowi['tunnus']}' />";
      }
      echo "<td><input type='checkbox' name='valitut_laitteet[]'  value='{$rowi['tunnus']}'  $sel onclick='submit();'/></td>";
    }
    
    echo "<td nowrap><a href='{$palvelin2}/laiterekisteri.php?toiminto=MUOKKAA&laitetunnus=$rowi[tunnus]&lopetus=$PHP_SELF'>{$rowi['tunnus']}</a></td>";
    echo "<td>".$rowi['sopimusrivi']."</td>";
    $puuttuja = '';
    
    if (isset($rowi['sopimusrivin_tunnukset'])) {

      $kveri = "SELECT
                distinct(tilausrivi.otunnus) sopimusnumero,
                tilausrivi.*,
                tilausrivin_lisatiedot.sopimuksen_lisatieto2,
                tilausrivin_lisatiedot.sopimus_alkaa,
                tilausrivin_lisatiedot.sopimus_loppuu
                FROM tilausrivi
                JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
                WHERE tilausrivi.tunnus IN ({$rowi['sopimusrivin_tunnukset']})";
      $ressi = pupe_query($kveri);

      $ed_sop_tun = '';
      $lassurivi = '';
      while ($lelo = mysql_fetch_assoc($ressi)) {

        // Katsotaan onko sopimustunnus tyhj‰(eka ajo)tai muuttunut
        if ($ed_sop_tun == '' or $ed_sop_tun != $lelo['sopimusnumero']) {

          if ($ed_sop_tun != '') {
            $puuttuja .= "</table>";
          }
          else {
            $asiakas = '';
          }

          $ed_sop_tun = $lelo['sopimusnumero'];
          $sopimuslinkki = "<a href='{$palvelin2}/tilauskasittely/tilaus_myynti.php?toim=YLLAPITO&tilausnumero=$lelo[sopimusnumero]'>{$lelo['sopimusnumero']}</a><br>";
          $puuttuja .= "<br>Sopimusnumero: {$sopimuslinkki}<table><tr><th>Nimitys</th><th>Hinta</th><th>Alkupvm</th><th>Loppupvm</th></tr>";
          $kveeri = "SELECT
                     lasku.nimi asiakas,
                     lasku.*, 
                     laskun_lisatiedot.* 
                     FROM lasku 
                     JOIN laskun_lisatiedot ON lasku.yhtio = laskun_lisatiedot.yhtio 
                       AND lasku.tunnus = laskun_lisatiedot.otunnus 
                     WHERE lasku.tunnus = '{$lelo['sopimusnumero']}' 
                     AND lasku.yhtio = '{$kukarow['yhtio']}'";
          $ressukka = pupe_query($kveeri);

          $lassurivi = mysql_fetch_assoc($ressukka);
          $asiakas .= $lassurivi['asiakas']."<br><br>";
        }

        $puuttuja .= "<tr nowrap><td>";
        $puuttuja .= $lelo['nimitys'];
        $puuttuja .= "</td>";
        $puuttuja .= "<td nowrap>";
        $puuttuja .= hintapyoristys($lelo['hinta'], 2)." e/kk";
        $puuttuja .= "</td>";
        $puuttuja .= "<td nowrap>";
        $puuttuja .= $lelo['sopimus_alkaa'] == '0000-00-00' ? $lassurivi['sopimus_alkupvm'] : $lelo['sopimus_alkaa'];
        $puuttuja .= "</td>";
        $puuttuja .= "<td nowrap>";
        $puuttuja .= $lelo['sopimus_loppuu'] == '0000-00-00' ? $lassurivi['sopimus_loppupvm'] : $lelo['sopimus_loppuu'];
        $puuttuja .= "</td></tr>";
      }
      $puuttuja .= "</table>";
      
    }
    else {
      $query = "SELECT
                lasku.nimi asiakas
                FROM sarjanumeroseuranta
                JOIN tilausrivi ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio
                  AND tilausrivi.tunnus = sarjanumeroseuranta.myyntirivitunnus
                JOIN lasku ON lasku.yhtio = sarjanumeroseuranta.yhtio
                  AND lasku.tunnus = tilausrivi.otunnus
                WHERE sarjanumeroseuranta.yhtio = '{$kukarow['yhtio']}'
                AND sarjanumeroseuranta.sarjanumero = '{$rowi['sarjanro']}'
                AND sarjanumeroseuranta.tuoteno = '{$rowi['tuoteno']}'
                ORDER BY sarjanumeroseuranta.luontiaika desc
                LIMIT 1";
      $sarjanumerores = pupe_query($query);
      $sarjanumerorow = mysql_fetch_assoc($sarjanumerores);
      $asiakas = $sarjanumerorow['asiakas'];
    }

    echo "<td nowrap>".$rowi['valmistaja']."</td>";
    echo "<td nowrap>".$rowi['malli']."</td>";

    echo "<td nowrap>".$rowi['sarjanro']."</td>";
    echo "<td nowrap>".$rowi['tuoteno']."</td>";

    echo "<td>$puuttuja</td>";

    echo "<td>$asiakas</td>";
    echo "<td>".$rowi['sla']."</td>";    
    echo "<td style='width:300px;'>".$rowi['kommentti']."</td>";
    echo "<td>".$rowi['lcm_info']."</td>";
    echo "<td nowrap>".$rowi['ip_osoite']."</td>";
    echo "<td nowrap>".$rowi['mac_osoite']."</td>";

    echo "</tr>";
  }

  echo "<br><br>";

  if ($toiminto == 'LINKKAA') {
    echo "<input type='hidden' name='tilausrivin_tunnus' value ='$tilausrivin_tunnus' />";
    echo "<input type='hidden' name='toiminto' value ='LINKKAA' />";
  }
  else {
    echo "<input type='hidden' name='toiminto' value ='UUSILAITE' />";
    echo "<input type='submit' name='uusi_laite' value='Uusi laite' />";
  }
  echo "<input type='hidden' name='lopetus' value='$lopetus' />";
  echo "</form>";
  echo "</table>";
}
