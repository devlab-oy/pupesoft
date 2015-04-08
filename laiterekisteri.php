<?php

// DataTables p��lle
$pupe_DataTables = "selaalaitteita";

if (strpos($_SERVER['SCRIPT_NAME'], "laiterekisteri.php") !== FALSE) {
  require "inc/parametrit.inc";
}

if ($yhtiorow['laiterekisteri_kaytossa'] == '') die(t("Yhti�n parametrit - Laiterekisteri ei ole k�yt�ss�"));

if (isset($livesearch_tee) and $livesearch_tee == "SARJANUMEROHAKU") {
  livesearch_sarjanumerohaku();
  exit;
}

// Enaboidaan ajax kikkare
enable_ajax();

if ($toiminto == 'LINKKAA') {
  pupe_DataTables(array(array($pupe_DataTables, 17, 17, true, true)));
}
else {
  pupe_DataTables(array(array($pupe_DataTables, 16, 16, true, true)));
}

$maara_paivitetty = false;

if ($toiminto == "LINKKAA" and isset($tilausrivin_tunnus) and isset($poista_laite_sopimusrivilta)) {
  // Poistetaan laite sopimusrivilt�
  $query = "DELETE FROM laitteen_sopimukset
            WHERE laitteen_tunnus   = '{$poista_laite_sopimusrivilta}'
            AND sopimusrivin_tunnus = '{$tilausrivin_tunnus}'
            AND yhtio               = '{$kukarow['yhtio']}'";
  pupe_query($query);
  $maara_paivitetty = true;
}
elseif ($toiminto == "LINKKAA" and isset($tilausrivin_tunnus) and isset($lisaa_laite_sopimusriville)) {
  // Lis�t��n laite sopimusriville
  $query = "INSERT INTO laitteen_sopimukset
            SET sopimusrivin_tunnus = '{$tilausrivin_tunnus}',
            laitteen_tunnus = '{$lisaa_laite_sopimusriville}',
            yhtio           = '{$kukarow['yhtio']}'";
  pupe_query($query);
  $maara_paivitetty = true;
}

if ($maara_paivitetty and isset($tilausrivin_tunnus)) {
  $paivita_params = array(0 => $tilausrivin_tunnus);
  paivita_sopimusrivit($paivita_params);
}

if (isset($tallennetaan_muutokset) and isset($muokattava_laite) and $muokattava_laite > 0) {
  // Tallennetaan muutokset laitteen tietoihin
  $kveri = "UPDATE laite
            SET kommentti                      = '{$kommentti}',
            sla                                = '{$sla}',
            sd_sla                             = '{$sd_sla}',
            lcm_info                           = '{$lcm_info}',
            ip_osoite                          = '{$ip_osoite}',
            mac_osoite                         = '{$mac_osoite}',
            valmistajan_sopimusnumero          = '{$valmistajan_sopimusnumero}',
            valmistajan_sopimus_paattymispaiva = '{$vcloppuvv}-{$vcloppukk}-{$vcloppupp}',
            muutospvm                          = now(),
            muuttaja                           = '{$kukarow['kuka']}'
            WHERE yhtio                        = '{$kukarow['yhtio']}'
            AND tunnus                         = '{$muokattava_laite}'";
  pupe_query($kveri);
  unset($toiminto);
}
elseif (isset($tallenna_uusi_laite) and isset($valitse_sarjanumero) and !empty($valitse_sarjanumero)
  and !isset($muokattava_laite)) {

  // Jos sy�tet��n sarjanumeroita mitk� eiv�t ole sarjanumeroseurannassa
  $uusilaite_sarjanumero = $uusilaite_sarjanumero == '' ? $valitse_sarjanumero : $uusilaite_sarjanumero;

  // Tarkistetaan ettei tuote/sarjanumeropari ole jo taulussa
  $query = "SELECT *
            FROM laite
            WHERE tuoteno = '{$uusilaite_tuotenumero}'
            AND sarjanro  = '{$uusilaite_sarjanumero}'
            AND yhtio     = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);
  if (mysql_affected_rows() == 0) {
    // Lis�t��n uusi laite
    $kveri = "INSERT INTO laite
              SET yhtio = '{$kukarow['yhtio']}',
              paikka                             = '{$uusilaite_myyntirivitunnus}',
              sarjanro                           = '{$uusilaite_sarjanumero}',
              tuoteno                            = '{$uusilaite_tuotenumero}',
              lcm_info                           = '{$lcm_info}',
              ip_osoite                          = '{$ip_osoite}',
              mac_osoite                         = '{$mac_osoite}',
              kommentti                          = '{$kommentti}',
              sla                                = '{$sla}',
              sd_sla                             = '{$sd_sla}',
              valmistajan_sopimusnumero          = '{$valmistajan_sopimusnumero}',
              valmistajan_sopimus_paattymispaiva = '{$vcloppuvv}-{$vcloppukk}-{$vcloppupp}',
              luontiaika                         = now(),
              laatija                            = '{$kukarow['kuka']}'";
    pupe_query($kveri);
    unset($toiminto);
  }
  else {
    echo "<br><font class='error'>".t("Sarjanumero %s l�ytyy jo laite-taulusta", "", $uusilaite_sarjanumero)."!</font><br/>";
  }
}
elseif (isset($peruuta_uusi) or isset($peruuta)) {
  unset($toiminto);
  unset($valitse_sarjanumero);
  unset($myyntirivitunnus);
  unset($lcm_info);
  unset($ip_osoite);
  unset($mac_osoite);
  unset($sla);
  unset($kommentti);
  unset($valmistajan_sopimusnumero);
  unset($sopimuspaattyypvm);
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
  "sd_sla",
  "vc",
  "vc end",
  "kommentti",
  "lcm info",
  "ip",
  "mac"
);

if ($toiminto == "LINKKAA") {
  // Halutaan linkata laitteita sopimuksen palveluriville
  if (!isset($tilausrivin_tunnus) or $tilausrivin_tunnus < 1) {
    echo "Et voi linkata laitteita ilman tilausrivin tunnusta";
    exit;
  }

  // Uusi sarake mihin piirret��n checkboxit
  array_unshift($headerit, 'lis��');

  // Valitut laitteet
  echo "<form>";
  echo "<input type='hidden' name='toiminto' value='$toiminto'>";
  echo "<input type='hidden' name='tilausrivin_tunnus' value='$tilausrivin_tunnus'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus' />";
  echo "<font class='head'>".t("Sopimusriviin %s liitetyt laitteet", '', $tilausrivin_tunnus)."</font>";
  echo "<br><br>";
  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Poista")."</th>";
  echo "<th>".t("Laitetunnus")."</th>";
  echo "<th>".t("Valmistaja")."</th>";
  echo "<th>".t("Malli")."</th>";
  echo "<th>".t("Sarjanumero")."</th>";
  echo "<th>".t("Tuotenumero")."</th>";
  echo "<th>".t("SLA")."</th>";
  echo "<th>".t("SD SLA")."</th>";
  echo "<th>".t("VC")."</th>";
  echo "<th>".t("VC End")."</th>";
  echo "<th>".t("Kommentti")."</th>";
  echo "<th>".t("Lcm info")."</th>";
  echo "<th>".t("IP")."</th>";
  echo "<th>".t("MAC")."</th>";
  echo "</tr>";

  // Linkattujen laitteiden tiedot
  $query = "SELECT
            laite.*,
            avainsana.selitetark valmistaja,
            tuote.tuotemerkki malli
            FROM laite
            LEFT JOIN tuote on tuote.yhtio = laite.yhtio
              AND tuote.tuoteno                           = laite.tuoteno
            LEFT JOIN avainsana on avainsana.yhtio = tuote.yhtio
              AND avainsana.laji                          = 'TRY'
              AND avainsana.selite                        = tuote.try
            JOIN laitteen_sopimukset on laitteen_sopimukset.laitteen_tunnus = laite.tunnus
              AND laitteen_sopimukset.sopimusrivin_tunnus = '{$tilausrivin_tunnus}'
            WHERE laite.yhtio                             = '{$kukarow['yhtio']}'
            GROUP BY laite.sarjanro,laite.tuoteno";

  $res = pupe_query($query);
  while ($vanhalaiterivi = mysql_fetch_assoc($res)) {
    echo "<tr>";
    echo "<td><input type='checkbox' name='poista_laite_sopimusrivilta'  value='{$vanhalaiterivi['tunnus']}' onclick='submit();'/></td>";
    echo "<td nowrap>{$vanhalaiterivi['tunnus']}</td>";
    echo "<td nowrap>{$vanhalaiterivi['valmistaja']}</td>";
    echo "<td nowrap>{$vanhalaiterivi['malli']}</td>";
    echo "<td nowrap>{$vanhalaiterivi['sarjanro']}</td>";
    echo "<td nowrap>{$vanhalaiterivi['tuoteno']}</td>";
    echo "<td>{$vanhalaiterivi['sla']}</td>";
    echo "<td>{$vanhalaiterivi['sd_sla']}</td>";
    echo "<td nowrap>{$vanhalaiterivi['valmistajan_sopimusnumero']}</td>";
    echo "<td nowrap>{$vanhalaiterivi['valmistajan_sopimus_paattymispaiva']}</td>";
    echo "<td style='width:300px;'>{$vanhalaiterivi['kommentti']}</td>";
    echo "<td>{$vanhalaiterivi['lcm_info']}</td>";
    echo "<td nowrap>{$vanhalaiterivi['ip_osoite']}</td>";
    echo "<td nowrap>{$vanhalaiterivi['mac_osoite']}</td>";
    echo "</tr>";
  }
  echo "</table>";
  echo "</form>";
  // Rajataan pois jo linkatut laitteet
  $kveri = "SELECT group_concat(DISTINCT laitteen_tunnus) sopimuksella
            FROM laitteen_sopimukset
            WHERE sopimusrivin_tunnus = '{$tilausrivin_tunnus}'
              AND yhtio               = '{$kukarow['yhtio']}'";
  $resu = pupe_query($kveri);
  $rivi = mysql_fetch_assoc($resu);
  if (!empty($rivi['sopimuksella'])) {
    $laiterajaus = " AND laite.tunnus NOT IN ({$rivi['sopimuksella']})";
  }
}

if (empty($toiminto)) {
  echo "<form>";
  echo "<input type='hidden' name='toiminto' value ='UUSILAITE' />";
  echo "<input type='submit' name='uusi_laite' value='Uusi laite' />";
  echo "</form>";
}

// Ekotellaan headerit
echo "<form id='laiterekisteriformi' name='laiterekisteriformi'>";
echo "<input type='hidden' name='toiminto' value='$toiminto'>";
echo "<input type='hidden' name='tilausrivin_tunnus' value='$tilausrivin_tunnus'>";
echo "<input type='hidden' name='lopetus' value='$lopetus' />";
echo "<table class='display dataTable' id='$pupe_DataTables'>";
echo "<thead>";
echo "<tr>";
foreach ($headerit as $hiid) {
  echo "<th>".t($hiid)."</th>";
}
echo "</tr>";

// Hakukent�t
if (empty($toiminto) or $toiminto == 'LINKKAA') {
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
          if(ifnull(laitteen_sopimukset.sopimusrivin_tunnus, 0),'Kyll�','Ei') sopimusrivi,
          group_concat(laitteen_sopimukset.sopimusrivin_tunnus) sopimusrivin_tunnukset
          FROM laite
          LEFT JOIN tuote on tuote.yhtio = laite.yhtio
            AND tuote.tuoteno    = laite.tuoteno
          LEFT JOIN avainsana on avainsana.yhtio = tuote.yhtio
            AND avainsana.laji   = 'TRY'
            AND avainsana.selite = tuote.try
          LEFT JOIN laitteen_sopimukset on laitteen_sopimukset.laitteen_tunnus = laite.tunnus
          WHERE laite.yhtio      = '{$kukarow['yhtio']}'
          {$laiterajaus}
          GROUP BY laite.sarjanro,laite.tuoteno";

$res = pupe_query($query);

if ($toiminto == 'MUOKKAA') {

  // Halutaan muuttaa laitteen tietoja
  while ($rowi = mysql_fetch_assoc($res)) {

    echo "<tr>";
    echo "<input type='hidden' name='muokattava_laite' value='{$rowi['tunnus']}'>";
    echo "<td nowrap>{$rowi['tunnus']}</td>";
    echo "<td>{$rowi['sopimusrivi']}</td>";
    echo "<td nowrap>{$rowi['valmistaja']}</td>";
    echo "<td nowrap>{$rowi['malli']}</td>";
    echo "<td nowrap>{$rowi['sarjanro']}</td>";
    echo "<td nowrap>{$rowi['tuoteno']}</td>";
    echo "<td></td><td></td>";
    echo "<td><input type='text' name='sla' value='{$rowi['sla']}'/></td>";
    echo "<td><input type='text' name='sd_sla' value='{$rowi['sd_sla']}'/></td>";
    echo "<td><input type='text' name='valmistajan_sopimusnumero' value='{$rowi['valmistajan_sopimusnumero']}'/></td>";
    // Taivutellaan p�iv�m��r�
    list ($vcloppuvv, $vcloppukk, $vcloppupp) = explode("-", $rowi['valmistajan_sopimus_paattymispaiva']);
    list ($vcloppupp) = explode(" ", $vcloppupp);
    echo "<td nowrap><input type='text' name='vcloppupp' maxlength='2' size='2' value='{$vcloppupp}'/>
      <input type='text' name='vcloppukk' maxlength='2' size='2' value='{$vcloppukk}'/>
      <input type='text' name='vcloppuvv' maxlength='4' size='4' value='{$vcloppuvv}'/></td>";
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
  // Halutaan lis�t� uusi laite
  $esiv_tuotenumero = $esiv_valmistaja = $esiv_malli = $esiv_sopimus = '';
  echo "<tr>";
  echo "<input type='hidden' name='toiminto' value='UUSILAITE' />";

  //Jos on selectoitu joku sarjanumero niin t�ytet��n sarakkeet joihin ei voi vaikuttaa
  if (isset($valitse_sarjanumero) and !empty($valitse_sarjanumero)) {
    $kveri = "SELECT
              sarjanumeroseuranta.sarjanumero,
              tuote.*,
              avainsana.selitetark valmistaja,
              tuote.tuotemerkki malli
              FROM sarjanumeroseuranta
              JOIN tuote on tuote.yhtio = sarjanumeroseuranta.yhtio
                AND tuote.tuoteno                 = sarjanumeroseuranta.tuoteno
              JOIN avainsana on avainsana.yhtio = sarjanumeroseuranta.yhtio
                AND avainsana.laji                = 'TRY'
                AND avainsana.selite              = tuote.try
              WHERE sarjanumeroseuranta.yhtio     = '{$kukarow['yhtio']}'
              AND sarjanumeroseuranta.sarjanumero = '{$valitse_sarjanumero}'
              ORDER BY muutospvm desc
              LIMIT 1";
    $resu = pupe_query($kveri);
    $rivikka = mysql_fetch_assoc($resu);

    $esiv_tuotenumero = $rivikka['tuoteno'];
    $esiv_valmistaja = $rivikka['valmistaja'];
    $esiv_malli = $rivikka['malli'];
    $esiv_sopimus = "Ei";
    if (empty($rivikka['sarjanumero'])) $rivikka['sarjanumero'] = $valitse_sarjanumero;
    echo "<input type='hidden' name='uusilaite_myyntirivitunnus' value ='{$rivikka['myyntirivitunnus']}'/>";
    echo "<input type='hidden' name='uusilaite_sarjanumero' value ='{$rivikka['sarjanumero']}'/>";
  }
  echo "<td></td>";
  echo "<td>{$esiv_sopimus}</td>";
  echo "<td>{$esiv_valmistaja}</td>";
  echo "<td>{$esiv_malli}</td>";

  echo "<td>";
  echo livesearch_kentta("laiterekisteriformi", "SARJANUMEROHAKU", "valitse_sarjanumero", 140, $valitse_sarjanumero, '', '', '', 'ei_break_all');
  echo "</td>";

  echo "<td><input type='text' name='uusilaite_tuotenumero' value='{$esiv_tuotenumero}'/></td>";
  echo "<td></td><td></td>";
  echo "<td><input type='text' name='sla'/></td>";
  echo "<td><input type='text' name='sd_sla'/></td>";
  echo "<td><input type='text' name='valmistajan_sopimusnumero'/></td>";

  echo "<td nowrap><input type='text' name='vcloppupp' maxlength='2' size='2'/>
    <input type='text' name='vcloppukk' maxlength='2' size='2'/>
    <input type='text' name='vcloppuvv' maxlength='4' size='4'/></td>";
  echo "<td><textarea name='kommentti' rows='5' columns='30'></textarea></td>";
  echo "<td><textarea name='lcm_info' rows='5' columns='30'></textarea></td>";
  echo "<td><input type='text' name='ip_osoite'/></td>";
  echo "<td><input type='text' name='mac_osoite'/></td>";
  echo "<td class='back'><input type='submit' name='tallenna_uusi_laite' value='Tallenna'/></td>";
  echo "<td class='back'><input type='submit' name='peruuta_uusi'  value='Peruuta'/></td>";
  echo "</form>";
  echo "</tr>";
}
else {

  while ($rowi = mysql_fetch_assoc($res)) {
    $asiakas = '';

    echo "<tr>";
    if ($toiminto == "LINKKAA") {
      echo "<td><input type='checkbox' name='lisaa_laite_sopimusriville'  value='{$rowi['tunnus']}' onclick='submit();'/></td>";
      echo "<td>{$rowi['tunnus']}</td>";
    }
    else {
      echo "<td nowrap><a href='{$palvelin2}/laiterekisteri.php?toiminto=MUOKKAA&laitetunnus=$rowi[tunnus]'>{$rowi['tunnus']}</a></td>";  //&lopetus=$PHP_SELF
    }
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
                WHERE tilausrivi.tunnus IN ({$rowi['sopimusrivin_tunnukset']})
                AND tilausrivi.yhtio    = '{$kukarow['yhtio']}'";
      $ressi = pupe_query($kveri);

      $ed_sop_tun = '';
      $lassurivi = '';
      while ($lelo = mysql_fetch_assoc($ressi)) {

        // Katsotaan onko sopimustunnus tyhj�(eka ajo)tai muuttunut
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
                     AND lasku.yhtio    = '{$kukarow['yhtio']}'";
          $ressukka = pupe_query($kveeri);

          $lassurivi = mysql_fetch_assoc($ressukka);

          $asiakas .= $lassurivi['toim_nimi']."<br>";
          $asiakas .= $lassurivi['toim_postitp']."<br>";
          $asiakas .= $lassurivi['asiakkaan_tilausnumero']."<br>";
          $asiakas .= "<br><br>";
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
                lasku.nimi asiakas,
                lasku.*
                FROM sarjanumeroseuranta
                JOIN tilausrivi ON tilausrivi.yhtio = sarjanumeroseuranta.yhtio
                  AND tilausrivi.tunnus             = sarjanumeroseuranta.myyntirivitunnus
                JOIN lasku ON lasku.yhtio = sarjanumeroseuranta.yhtio
                  AND lasku.tunnus                  = tilausrivi.otunnus
                WHERE sarjanumeroseuranta.yhtio     = '{$kukarow['yhtio']}'
                AND sarjanumeroseuranta.sarjanumero = '{$rowi['sarjanro']}'
                AND sarjanumeroseuranta.tuoteno     = '{$rowi['tuoteno']}'
                ORDER BY sarjanumeroseuranta.luontiaika desc
                LIMIT 1";
      $sarjanumerores = pupe_query($query);
      $sarjanumerorow = mysql_fetch_assoc($sarjanumerores);

      $asiakas = $sarjanumerorow['toim_nimi']."<br>";
      $asiakas .= $sarjanumerorow['toim_postitp']."<br>";
    }
    echo "<td nowrap>{$rowi['valmistaja']}</td>";
    echo "<td nowrap>{$rowi['malli']}</td>";
    echo "<td nowrap>{$rowi['sarjanro']}</td>";
    echo "<td nowrap>{$rowi['tuoteno']}</td>";
    echo "<td>{$puuttuja}</td>";
    echo "<td nowrap>{$asiakas}</td>";
    echo "<td>{$rowi['sla']}</td>";
    echo "<td>{$rowi['sd_sla']}</td>";
    echo "<td nowrap>{$rowi['valmistajan_sopimusnumero']}</td>";
    echo "<td nowrap>{$rowi['valmistajan_sopimus_paattymispaiva']}</td>";
    echo "<td style='width:300px;'>{$rowi['kommentti']}</td>";
    echo "<td>{$rowi['lcm_info']}</td>";
    echo "<td nowrap>{$rowi['ip_osoite']}</td>";
    echo "<td nowrap>{$rowi['mac_osoite']}</td>";
    echo "</tr>";
  }
  echo "<br><br>";
  echo "</table>";
  echo "</form>";
}
