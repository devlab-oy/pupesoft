<?php

require ("../inc/parametrit.inc");

function va_ti_en_jt($tuoteno, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) {
  global $kukarow, $yhtiorow, $lisavarattu;

  //tilauksessa, ennakkopoistot ja jt
  $query = "SELECT
            sum(if(tyyppi in ('W','M'), varattu, 0)) valmistuksessa,
            sum(if(tyyppi = 'O', varattu, 0)) tilattu,
            sum(if(tyyppi = 'E', varattu, 0)) ennakot,
            sum(if(tyyppi in ('L','V') and var not in ('P','J','O','S'), varattu, 0)) ennpois,
            sum(if(tyyppi in ('L','G') and var = 'J', jt $lisavarattu, 0)) jt
            FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
            WHERE yhtio        = '{$kukarow['yhtio']}'
             AND tyyppi        in ('L','V','O','G','E','W','M')
            AND tuoteno        = '{$tuoteno}'
            AND laskutettuaika = '0000-00-00'
            AND hyllyalue      = '{$hyllyalue}'
            AND hyllynro       = '{$hyllynro}'
            AND hyllyvali      = '{$hyllyvali}'
            AND hyllytaso      = '{$hyllytaso}'
            AND (varattu+jt > 0)";
  $result = pupe_query($query);
  $ennp   = mysql_fetch_assoc($result);

  return array($ennp['tilattu'], $ennp['valmistuksessa'], $ennp['ennpois'], $ennp['jt']);
}

if (isset($muutparametrit) and $muutparametrit != '') {
  list($tee, $kohdevarastot, $mul_osasto, $mul_try, $mul_tme, $abcrajaus, $generoi, $ohjausmerkki, $tilaustyyppi, $viesti, $myytavissasummaus, $ed_ytunnus) = explode("!¡!", urldecode($muutparametrit));

  $kohdevarastot = unserialize($kohdevarastot);
  $mul_osasto    = unserialize($mul_osasto);
  $mul_try       = unserialize($mul_try);
  $mul_tme       = unserialize($mul_tme);
}

if ($tee != '' and $ytunnus != '') {

  // Vaihdetaan toimittaja
  if (isset($generoi) and $generoi != "" and $ed_ytunnus != $ytunnus) {
    $toimittajaid = "";
  }

  $ytunnus = mysql_real_escape_string($ytunnus);

  $muutparametrit = urlencode($tee."!¡!".serialize($kohdevarastot)."!¡!".serialize($mul_osasto)."!¡!".serialize($mul_try)."!¡!".serialize($mul_tme)."!¡!".$abcrajaus."!¡!".$generoi."!¡!".$ohjausmerkki."!¡!".$tilaustyyppi."!¡!".$viesti."!¡!".$myytavissasummaus."!¡!".$ed_ytunnus);

  require ("inc/kevyt_toimittajahaku.inc");

  if ($toimittajaid == '') {
    $tee = '';
  }
}

echo "<font class='head'>",t("Luo ostotilaus tuotepaikkojen hälytysrajojen perusteella"),"</font><hr /><br />";

// org_rajausta tarvitaan yhdessä selectissä joka triggeröi taas toisen asian.
$org_rajaus = $abcrajaus;
list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";
if (!isset($keraysvyohyke)) $keraysvyohyke = array();

list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

// Tällä ollaan, jos olemme syöttämässä tiedostoa ja muuta
echo "<form name = 'valinta' method='post'>
    <input type='hidden' name='tee' value='M'>
    <table>";

echo "<tr><th>",t("Varasto johon tilataan"),"</th>";
echo "<td><table>";

$query  = "SELECT tunnus, nimitys, maa
           FROM varastopaikat
           WHERE yhtio  = '{$kukarow['yhtio']}'
           AND tyyppi  != 'P'
           ORDER BY tyyppi, nimitys";
$vares = pupe_query($query);

$kala = 0;

while ($varow = mysql_fetch_assoc($vares)) {
  $sel = '';
  if (is_array($kohdevarastot) and in_array($varow['tunnus'], $kohdevarastot)) $sel = 'checked';

  $varastomaa = '';
  if ($varow['maa'] != "" and strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
    $varastomaa = '(' . maa(strtoupper($varow['maa'])) . ')';
  }

  if ($kala == 0) echo "<tr>";

  echo "<td><input type='checkbox' name='kohdevarastot[]' value='{$varow['tunnus']}' {$sel} />{$varow['nimitys']} {$varastomaa}</td>";

  if ($kala == 3) {
    echo "</tr>";
    $kala = -1;
  }

  $kala++;
}

if ($kala != 0) {
  echo "</tr>";
}

echo "</table></td></tr>";

echo "<tr><th>",t("Lisärajaukset"),"</th><td>";

$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI");
$monivalintalaatikot_normaali = array();

require ("tilauskasittely/monivalintalaatikot.inc");

echo "</td></tr>";

if ($yhtiorow['kerayserat'] == 'K') {

  $query = "SELECT nimitys, tunnus
            FROM keraysvyohyke
            WHERE yhtio = '{$kukarow['yhtio']}'";
  $keraysvyohyke_res = pupe_query($query);

  if (mysql_num_rows($keraysvyohyke_res) > 0) {

    echo "<tr><th>",t("Keräysvyöhyke"),"</th>";
    echo "<td>";
    echo "<table>";

    $kala = 0;

    while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_res)) {

      $chk = in_array($keraysvyohyke_row['tunnus'], $keraysvyohyke) ? " checked" : "";

      if ($kala == 0) echo "<tr>";

      echo "<td><input type='checkbox' name='keraysvyohyke[]' value='{$keraysvyohyke_row['tunnus']}'{$chk} /> {$keraysvyohyke_row['nimitys']}</td>";

      if ($kala == 3) {
        echo "</tr>";
        $kala = -1;
      }

      $kala++;
    }

    if ($kala != 0) {
      echo "</tr>";
    }

    echo "</table></td></tr>";
  }
}

echo "<tr><th>",t("Toimittaja"),"</th><td><input type='text' size='20' name='ytunnus' value='{$ytunnus}'>";

if ($toimittajaid > 0) {
  echo "  <input type='hidden' name='ed_ytunnus' value='{$ytunnus}'>
      <input type='hidden' name='toimittajaid' value='{$toimittajaid}'>
      ".t("Valittu toimittaja").":  {$toimittajarow['nimi']} {$toimittajarow['nimitark']}";
}

echo "</td></tr>";

echo "<tr><th>",t("ABC-luokkarajaus ja rajausperuste"),"</th><td>";

echo "<select name='abcrajaus' onchange='submit()'>";
echo "<option  value=''>",t("Valitse"),"</option>";

$teksti = "";
for ($i = 0; $i < count($ryhmaprossat); $i++) {
  $selabc = "";

  if ($i > 0) $teksti = t("ja paremmat");
  if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

  echo "<option  value='{$i}##TM' {$selabc}>",t("Myynti"),": {$ryhmanimet[$i]} {$teksti}</option>";
}

$teksti = "";
for ($i = 0; $i < count($ryhmaprossat); $i++) {
  $selabc = "";

  if ($i > 0) $teksti = t("ja paremmat");
  if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

  echo "<option  value='{$i}##TK' {$selabc}>",t("Myyntikate"),": {$ryhmanimet[$i]} {$teksti}</option>";
}

$teksti = "";
for ($i = 0; $i < count($ryhmaprossat); $i++) {
  $selabc = "";

  if ($i > 0) $teksti = t("ja paremmat");
  if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

  echo "<option  value='{$i}##TR' {$selabc}>",t("Myyntirivit"),": {$ryhmanimet[$i]} {$teksti}</option>";
}

$teksti = "";
for ($i = 0; $i < count($ryhmaprossat); $i++) {
  $selabc = "";

  if ($i > 0) $teksti = t("ja paremmat");
  if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

  echo "<option  value='{$i}##TP' {$selabc}>",t("Myyntikappaleet"),": {$ryhmanimet[$i]} {$teksti}</option>";
}

echo "</select>";
echo "</td></tr>";

echo "<tr><th>",t("Vastaavat"),"</th>";
echo "<td><select name='myytavissasummaus'>";

$sel = array($myytavissasummaus => "selected") + array("T" => '', "V" => '');

echo "<option value='T' {$sel['T']}>",t("Ostetaan tuotteittain"),"</option>";
echo "<option value='V' {$sel['V']}>",t("Ostetaan vastaavuusketjuittain"),"</option>";
echo "</select></td></tr>";

echo "<tr><td class='back'><br></td></tr>";

echo "<tr><th>".t("Viite")."</th><td><input type='text' size='61' name='viesti' value='$viesti'></td></tr>";
echo "<tr><th>".t("Ohjausmerkki")."</th><td><input type='text' size='61' name='ohjausmerkki' value='$ohjausmerkki'></td></tr>";
echo "<tr><th>".t("Tilaustyyppi")."</th>";

echo "<td><select name='tilaustyyppi'>";

$ostotil_tiltyyp_res = t_avainsana("OSTOTIL_TILTYYP");

if (mysql_num_rows($ostotil_tiltyyp_res) > 0) {

  while ($ostotil_tiltyyp_row = mysql_fetch_assoc($ostotil_tiltyyp_res)) {
    $sel = $tilaustyyppi == $ostotil_tiltyyp_row['selite'] ? " selected" : "";
    echo "<option value='{$ostotil_tiltyyp_row['selite']}'{$sel}>{$ostotil_tiltyyp_row['selitetark']}</option>";
  }
}
else {
  $sel = array($tilaustyyppi => "selected") + array(1 => '', 2 => '');

  echo "<option value='2' {$sel[2]}>",t("Normaalitilaus"),"</option>";
  echo "<option value='1' {$sel[1]}>",t("Pikalähetys"),"</option>";
}

echo "</select></td>";
echo "</tr>";

echo "</table><br><input type = 'submit' name = 'generoi' value = '",t("Generoi ostotilaus"),"'></form><br><br>";

if (isset($generoi) and $generoi != "" and $tee == 'M' and $toimittajaid > 0 and count($kohdevarastot) > 0) {

  require ("vastaavat.class.php");
  require ("inc/luo_ostotilausotsikko.inc");

  $abcjoin = "";

  if ($abcrajaus != "") {
    // joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
    $abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio AND
          abc_aputaulu.tuoteno = tuote.tuoteno AND
          abc_aputaulu.tyyppi = '{$abcrajaustapa}' AND
          (abc_aputaulu.luokka <= '{$abcrajaus}' OR abc_aputaulu.luokka_osasto <= '{$abcrajaus}' OR abc_aputaulu.luokka_try <= '{$abcrajaus}'))";
  }

  $keraysvyohykelisa = "";

  if (count($keraysvyohyke) > 1) {

    // ensimmäinen alkio on 'default' ja se otetaan pois
    array_shift($keraysvyohyke);

    $keraysvyohykelisa = "  JOIN varaston_hyllypaikat AS vh ON (
                  vh.yhtio = tuotepaikat.yhtio AND
                  vh.hyllyalue = tuotepaikat.hyllyalue AND
                  vh.hyllynro = tuotepaikat.hyllynro AND
                  vh.hyllytaso = tuotepaikat.hyllytaso AND
                  vh.hyllyvali = tuotepaikat.hyllyvali AND
                  vh.keraysvyohyke IN (".implode(",", $keraysvyohyke)."))
                JOIN keraysvyohyke ON (keraysvyohyke.yhtio = vh.yhtio AND keraysvyohyke.tunnus = vh.keraysvyohyke)";
  }

  // Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
  if ($yhtiorow["varaako_jt_saldoa"] != "") {
    $lisavarattu = " + tilausrivi.varattu";
  }
  else {
    $lisavarattu = "";
  }

  // Otetaan luodut otsikot talteen
  $otsikot  = array();

  // tehdään jokaiselle valitulle kohdevarastolle erikseen
  foreach ($kohdevarastot as $kohdevarasto) {

    $query = "SELECT *
              FROM varastopaikat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$kohdevarasto}'";
    $result = pupe_query($query);
    $varow = mysql_fetch_assoc($result);

    // Katotaan kohdepaikkojen tarvetta
    $query = "SELECT tuotepaikat.*,
              if (tuotepaikat.tilausmaara = 0, 1, tuotepaikat.tilausmaara) tilausmaara,
              if (tuotteen_toimittajat.osto_era = 0, 1, tuotteen_toimittajat.osto_era) osto_era
              FROM tuotepaikat
              JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno AND tuote.ostoehdotus != 'E' {$lisa})
              JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = tuote.tuoteno AND tuotteen_toimittajat.liitostunnus = '{$toimittajaid}')
              {$abcjoin}
              {$keraysvyohykelisa}
              WHERE tuotepaikat.yhtio     = '{$kukarow['yhtio']}'
              AND CONCAT(RPAD(UPPER('{$varow['alkuhyllyalue']}'),  5, '0'),LPAD(UPPER('{$varow['alkuhyllynro']}'),  5, '0')) <= CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),LPAD(UPPER(tuotepaikat.hyllynro), 5, '0'))
              AND CONCAT(RPAD(UPPER('{$varow['loppuhyllyalue']}'), 5, '0'),LPAD(UPPER('{$varow['loppuhyllynro']}'), 5, '0')) >= CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),LPAD(UPPER(tuotepaikat.hyllynro), 5, '0'))
              AND tuotepaikat.halytysraja > 0
              ORDER BY tuotepaikat.tuoteno";
    $resultti = pupe_query($query);

    //  Varmistetaan että aloitetaan aina uusi otsikko uudelle varastolle
    $tehtyriveja     = 0;
    $tuotteet        = array();
    $kasitellyt_ketjut  = array();

    while ($pairow = mysql_fetch_assoc($resultti)) {
      //tilauksessa, ennakkopoistot ja jt
      list($pairow['tilattu'], $pairow['valmistuksessa'], $pairow['ennpois'], $pairow['jt']) = va_ti_en_jt($pairow['tuoteno'], $pairow['hyllyalue'], $pairow['hyllynro'], $pairow['hyllyvali'], $pairow['hyllytaso']);

      $tuotteet[$pairow['tuoteno']][] = $pairow;
    }

    if ($myytavissasummaus == "V") {
      foreach ($tuotteet as $_tuotepaikka) {
        foreach ($_tuotepaikka as $_indeksi => $pairow) {

          $vastaavat = new Vastaavat($pairow['tuoteno']);

          $vastaavat_tuotteet = array();

          if ($vastaavat->onkovastaavia()) {

            // Loopataan kaikki tuotteen vastaavuusketjut
            foreach (explode(",", $vastaavat->getIDt()) as $ketju) {

              if (!isset($kasitellyt_ketjut[$ketju])) {

                $kasitellyt_ketjut[$ketju] = $ketju;

                // Haetaan tuotteet ketjukohtaisesti
                $_vastaavat_tuotteet = $vastaavat->tuotteet($ketju);

                $paras_vastaava = "";

                foreach ($_vastaavat_tuotteet as $_tuote) {
                  // Otetaan päätuote, tai jos se ei oo setattu, niin otetaan se tuote joka on lähimpänä päätuotetta
                  if (isset($tuotteet[$_tuote["tuoteno"]])) {
                    $paras_vastaava = $_tuote["tuoteno"];
                    break;
                  }
                }

                // Lisätään löydetyt vastaavat mahdollisten myytävien joukkoon
                foreach ($_vastaavat_tuotteet as $_tuote) {
                  if (strtoupper($paras_vastaava) != strtoupper($_tuote['tuoteno'])) {

                    $_vashalytysraja  = 0;
                    $_vassaldo      = 0;
                    $_vastilattu    = 0;
                    $_vasvalmistuksessa = 0;
                    $_vasennpois    = 0;
                    $_vasjt        = 0;

                    // Jos tuote ei osunut pääqueryyn, niin haetaan sen tiedot tässä
                    if (!isset($tuotteet[$_tuote["tuoteno"]])) {
                      $query = "SELECT tuotepaikat.tuoteno,
                                tuotepaikat.saldo,
                                tuotepaikat.halytysraja,
                                tuotepaikat.hyllyalue,
                                tuotepaikat.hyllynro,
                                tuotepaikat.hyllyvali,
                                tuotepaikat.hyllytaso
                                FROM tuotepaikat
                                JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio AND tuote.tuoteno = tuotepaikat.tuoteno AND tuote.ostoehdotus != 'E' {$lisa})
                                {$abcjoin}
                                {$keraysvyohykelisa}
                                WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
                                AND CONCAT(RPAD(UPPER('{$varow['alkuhyllyalue']}'),  5, '0'),LPAD(UPPER('{$varow['alkuhyllynro']}'),  5, '0')) <= CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),LPAD(UPPER(tuotepaikat.hyllynro), 5, '0'))
                                AND CONCAT(RPAD(UPPER('{$varow['loppuhyllyalue']}'), 5, '0'),LPAD(UPPER('{$varow['loppuhyllynro']}'), 5, '0')) >= CONCAT(RPAD(UPPER(tuotepaikat.hyllyalue), 5, '0'),LPAD(UPPER(tuotepaikat.hyllynro), 5, '0'))
                                 AND tuote.tuoteno      = '{$_tuote['tuoteno']}'";
                      $vasres = pupe_query($query);

                      while ($vasrow = mysql_fetch_assoc($vasres)) {
                        list($vasrow["tilattu"], $vasrow["valmistuksessa"], $vasrow["ennpois"], $vasrow["jt"]) = va_ti_en_jt($vasrow['tuoteno'], $vasrow['hyllyalue'], $vasrow['hyllynro'], $vasrow['hyllyvali'], $vasrow['hyllytaso']);

                        $_vashalytysraja  += $vasrow["halytysraja"];
                        $_vassaldo      += $vasrow["saldo"];
                        $_vastilattu    += $vasrow["tilattu"];
                        $_vasvalmistuksessa += $vasrow["valmistuksessa"];
                        $_vasennpois    += $vasrow["ennpois"];
                        $_vasjt        += $vasrow["jt"];
                      }
                    }
                    else {
                      foreach ($tuotteet[$_tuote["tuoteno"]] as $_ttiedot) {
                        $_vashalytysraja  += $_ttiedot["halytysraja"];
                        $_vassaldo      += $_ttiedot["saldo"];
                        $_vastilattu    += $_ttiedot["tilattu"];
                        $_vasvalmistuksessa += $_ttiedot["valmistuksessa"];
                        $_vasennpois    += $_ttiedot["ennpois"];
                        $_vasjt        += $_ttiedot["jt"];
                      }
                    }

                    // Siirretään kaikki luvut "päätuotteelle"
                    $tuotteet[$paras_vastaava][$_indeksi]["halytysraja"]  += $_vashalytysraja;
                    $tuotteet[$paras_vastaava][$_indeksi]["saldo"]        += $_vassaldo;
                    $tuotteet[$paras_vastaava][$_indeksi]["tilattu"]     += $_vastilattu;
                    $tuotteet[$paras_vastaava][$_indeksi]["valmistuksessa"]  += $_vasvalmistuksessa;
                    $tuotteet[$paras_vastaava][$_indeksi]["ennpois"]     += $_vasennpois;
                    $tuotteet[$paras_vastaava][$_indeksi]["jt"]       += $_vasjt;

                    unset($tuotteet[$_tuote["tuoteno"]][$_indeksi]);
                  }
                }
              }
            }
          }
        }
      }
    }

    foreach ($tuotteet as $_tuotepaikka) {
      foreach ($_tuotepaikka as $pairow) {

        $ostettavahaly = ($pairow['halytysraja'] - ($pairow['saldo'] + $pairow['tilattu'] + $pairow['valmistuksessa'] - $pairow['ennpois'] - $pairow['jt'])) / $pairow['osto_era'];

        if ($ostettavahaly > 0)  $ostettavahaly = ceil($ostettavahaly) * $pairow['osto_era'];
        else $ostettavahaly = 0;

        if ($ostettavahaly > 0 and $ostettavahaly <= $pairow['tilausmaara']) {
          $ostettavahaly = $pairow['tilausmaara'];
        }

        if ($ostettavahaly <= 0) {
          continue;
        }

        /*
        echo "<br>Tuoteno: $pairow[tuoteno]<br>";
        echo "Hälytysraja: {$pairow['halytysraja']}<br>";
        echo "Saldo: {$pairow['saldo']}<br>";
        echo "Tilattu: {$pairow['tilattu']}<br>";
        echo "Valmistuksessa: {$pairow['valmistuksessa']}<br>";
        echo "Varattu: {$pairow['ennpois']}<br>";
        echo "Jt: {$pairow['jt']}<br>";
        echo "Osto_erä: {$pairow['osto_era']}<br>";
        echo "Tilausmäärä: {$pairow['tilausmaara']}<br>";
        echo "Tarve: $ostettavahaly<br>";
        */

        //  Onko meillä jo otsikko vai pitääkö tehdä uusi?
        if ($tehtyriveja == 0) {

          // Nollataan kun tehdään uusi otsikko
          $tehtyriveja = 0;

          $query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' and kuka = '{$kukarow['kuka']}'";
          $delresult = pupe_query($query);

          $kukarow["kesken"] = 0;

          // Otetaan osoite toimipaikalta jos varaston tiedoissa sitä ei oo
          if ($varow['osoite'] == "" and $varow['toimipaikka'] > 0) {
            $query = "SELECT nimi, osoite, postino, postitp, maa
                      FROM yhtion_toimipaikat
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '{$varow['toimipaikka']}'";
            $result = pupe_query($query);

            if (mysql_num_rows($result) == 1) {
              $yhtion_toimipaikkarow = mysql_fetch_assoc($result);

              $varow["nimi"]     = $yhtion_toimipaikkarow["nimi"];
              $varow['nimitark']  = "";
              $varow["osoite"]   = $yhtion_toimipaikkarow["osoite"];
              $varow["postino"]   = $yhtion_toimipaikkarow["postino"];
              $varow["postitp"]   = $yhtion_toimipaikkarow["postitp"];
              $varow["maa"]     = $yhtion_toimipaikkarow["maa"];
            }
          }

          $params = array(
            'liitostunnus'         => $toimittajaid,
            'nimi'             => $varow['nimi'],
            'nimitark'           => $varow['nimitark'],
            'osoite'           => $varow['osoite'],
            'postino'           => $varow['postino'],
            'postitp'           => $varow['postitp'],
            'maa'             => $varow['maa'],
            'myytil_toimaika'      => date("Y-m-d"),
            'toimipaikka'         => $varow['toimipaikka'],
            'varasto'           => $kohdevarasto,
            'ohjausmerkki'         => $ohjausmerkki,
            'tilaustyyppi'         => $tilaustyyppi,
            'myytil_viesti'        => $viesti,
            'ostotilauksen_kasittely'  => "GEN", # tällä erotellaan generoidut ja käsin tehdyt ostotilaukset
          );

          $laskurow = luo_ostotilausotsikko($params);

          $query = "UPDATE kuka SET kesken = {$laskurow['tunnus']} WHERE yhtio = '{$kukarow['yhtio']}' and kuka = '{$kukarow['kuka']}'";
          $delresult = pupe_query($query);

          $kukarow['kesken'] = $laskurow['tunnus'];

          echo "<br /><font class='message'>",t("Tehtiin ostotilaus otsikko %s kohdevarasto on %s", $kieli, $kukarow["kesken"], $varow["nimitys"]),"</font><br />";

          //  Otetaan luotu otsikko talteen
          $otsikot[] = $kukarow["kesken"];
        }

        $query = "SELECT *
                  FROM tuote
                  WHERE tuoteno = '{$pairow['tuoteno']}'
                  AND yhtio     = '{$kukarow['yhtio']}'";
        $rarresult = pupe_query($query);

        if (mysql_num_rows($rarresult) == 1) {
          $trow = mysql_fetch_assoc($rarresult);
          $toimaika       = $laskurow["toimaika"];
          $kerayspvm      = $laskurow["kerayspvm"];
          $tuoteno      = $pairow["tuoteno"];
          $kpl        = $ostettavahaly;
          $varasto      = $kohdevarasto;
          $hinta         = "";
          $netto         = "";
          $var        = "";
          $paikka       = "$pairow[hyllyalue]#!¡!#$pairow[hyllynro]#!¡!#$pairow[hyllyvali]#!¡!#$pairow[hyllytaso]";

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            ${'ale'.$alepostfix} = "";
          }

        require ('lisaarivi.inc');

          $tuoteno  = '';
          $kpl    = '';
          $hinta    = '';
          $alv    = 'X';
          $var    = '';
          $toimaika  = '';
          $kerayspvm  = '';
          $kommentti  = '';

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            ${'ale'.$alepostfix} = '';
          }

          $tehtyriveja++;

          echo "<font class='info'>",t("Ostotilaukselle lisättiin %s tuotetta %s", "", $ostettavahaly." ".$trow["yksikko"], $trow["tuoteno"]),"</font><br />";
        }
        else {
          echo t("VIRHE: Tuotetta ei löydy"),"!<br />";
        }
      }
    }
  }

  if (count($otsikot) == 0) {
    echo "<br><font class='error'>",t("Yhtään ostotilausta ei luotu"),"!</font><br />";
  }
  else {
    echo "<font class='message'>",t("Luotiin %s ostotilausta", $kieli, count($otsikot)),"</font><br /><br /><br />";
  }

  $query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' and kuka = '{$kukarow['kuka']}'";
  $delresult = pupe_query($query);
}
elseif ($tee == 'M' and isset($generoi) and $generoi != "") {
  echo "<br><br><font class='error'>".t("VIRHE: Valitse toimittaja ja varasto johon tilataan")."!</font>";
}

require ("inc/footer.inc");
