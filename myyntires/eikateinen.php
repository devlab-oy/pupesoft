<?php

require "../inc/parametrit.inc";

if (!isset($toim)) $toim = "";
if (!isset($maksuehto)) $maksuehto = 0;
if (!isset($laskuno)) $laskuno = 0;
if (!isset($tunnus)) $tunnus = 0;

if ($toim == 'KATEISESTAKATEINEN') {
  echo "<font class='head'>", t("Vaihda laskun käteismaksutyyppi"), "</font><hr />";
}
elseif ($toim == 'KATEINEN') {
  echo "<font class='head'>", t("Lasku halutaankin maksaa käteisellä"), "</font><hr />";
}
else {
  echo "<font class='head'>", t("Lasku ei ollutkaan käteistä"), "</font><hr />";
}

if ((int) $maksuehto != 0 and (int) $tunnus != 0) {
  $laskupvmerror      = FALSE;
  $laskumaksettuerror = FALSE;

  if ($toim == 'KATEINEN' or $toim == 'KATEISESTAKATEINEN') {
    $tapahtumapaiva  = date('Y-m-d', mktime(0, 0, 0, $tapahtumapaiva_kk, $tapahtumapaiva_pp, $tapahtumapaiva_vv));
  }
  else {
    $tapahtumapaiva  = date('Y-m-d');
  }

  // Haetaan laskun tiedot
  $laskurow = hae_lasku($tunnus);

  if (strtotime($tapahtumapaiva) < strtotime($laskurow['tapvm']) and $toim != 'KATEISESTAKATEINEN') {
    $laskupvmerror = TRUE;
  }

  if ($toim == 'KATEINEN' and $laskurow['mapvm'] != '0000-00-00') {
    $laskumaksettuerror = TRUE;
  }

  $tilikausi = tarkista_saako_laskua_muuttaa($tapahtumapaiva);
  $tilikausi_lasku = tarkista_saako_laskua_muuttaa($laskurow['tapvm']);

  if (empty($tilikausi) and (empty($tilikausi_lasku) or $toim == 'KATEINEN' or $toim == 'KATEISESTAKATEINEN') and !$laskupvmerror and !$laskumaksettuerror) {
    $mehtorow = hae_maksuehto($maksuehto);
    $konsrow = hae_asiakas($laskurow);
    $kassalipasrow = hae_kassalipas($kassalipas);

    $params = array(
      'konsrow' => $konsrow,
      'mehtorow' => $mehtorow,
      'laskurow' => $laskurow,
      'maksuehto' => $maksuehto,
      'tunnus' => $tunnus,
      'toim' => $toim,
      'tapahtumapaiva' => $tapahtumapaiva,
      'kassalipas' => $kassalipas
    );

    if (($toim == 'KATEINEN' or $toim == 'KATEISESTAKATEINEN') and $kateinen != '') {
      // Lasku oli ennestään käteinen ja nyt päivitetään sille joku toinen käteismaksuehto
      list($myysaatili, $_tmp) = hae_kassalippaan_tiedot($laskurow['kassalipas'], hae_maksuehto($laskurow['maksuehto']), $laskurow);
      $_tmp = korjaa_erapaivat_ja_alet_ja_paivita_lasku($params);
    }
    else {
      $myysaatili = korjaa_erapaivat_ja_alet_ja_paivita_lasku($params);
    }

    list($_kassalipas, $kustp) = hae_kassalippaan_tiedot($kassalipas, $mehtorow, $laskurow);

    $params = array(
      'laskurow'     => $laskurow,
      'tunnus'     => $tunnus,
      'myysaatili'   => $myysaatili,
      'tapahtumapaiva' => $tapahtumapaiva,
      'toim'       => $toim,
      '_kassalipas'   => $_kassalipas,
      'kateinen'     => $kateinen,
      'kustp'       => $kustp
    );

    tee_kirjanpito_muutokset($params);
    yliviivaa_alet_ja_pyoristykset($tunnus);
    tarkista_pyoristys_erotukset($laskurow, $tunnus);

    if ($toim == 'KATEINEN' or $toim == 'KATEISESTAKATEINEN') {
      vapauta_kateistasmaytys($kassalipasrow, $tapahtumapaiva);
    }

    if (empty($mehtorow) and empty($laskurow)) {
      $laskuno   = 0;
      $tunnus   = 0;
      $maksuehto   = 0;
    }

    $laskuno = 0;
    echo "<br>";
  }
  elseif ($laskumaksettuerror) {
    echo "<font class='error'>".t("VIRHE: Lasku on jo maksettu")."!</font>";
  }
  elseif ($laskupvmerror) {
    echo "<font class='error'>".t("VIRHE: Syötetty päivämäärä on pienempi kuin laskun päivämäärä %s", "", $laskurow['tapvm'])."!</font>";
  }
  elseif (!empty($tilikausi_lasku) and $toim != 'KATEINEN' and $toim != 'KATEISESTAKATEINEN') {
    echo "<font class='error'>".t("VIRHE: Tilikausi on päättynyt %s. Et voi merkitä laskua maksetuksi päivälle %s", "", $tilikausi_lasku, $laskurow['tapvm'])."!</font>";
  }
  else {
    echo "<font class='error'>".t("VIRHE: Tilikausi on päättynyt %s. Et voi merkitä laskua maksetuksi päivälle %s", "", $tilikausi, $tapahtumapaiva)."!</font>";
  }
}

if ((int) $laskuno != 0) {
  $laskurow = hae_lasku2($laskuno, $toim);

  if (empty($laskurow)) {
    $laskuno = 0;
  }
  else {
    echo_lasku_table($laskurow, $toim);
  }
}

if ($laskuno == 0) {
  echo_lasku_search();
}

//kursorinohjausta
$formi = "eikat";
$kentta = "laskuno";

function hae_maksuehto($maksuehto) {
  global $kukarow;

  $query = "SELECT *
            FROM maksuehto
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$maksuehto'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Maksuehto katosi")."!</font><br><br>";
    return null;
  }
  else {
    return mysql_fetch_assoc($result);
  }
}

function hae_lasku($tunnus) {
  global $kukarow;

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Lasku katosi")."!</font><br><br>";
    return null;
  }
  else {
    return mysql_fetch_assoc($result);
  }
}

function hae_asiakas($laskurow) {
  global $kukarow;

  $query = "SELECT konserniyhtio
            FROM asiakas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$laskurow['liitostunnus']}'";
  $konsres = pupe_query($query);

  return mysql_fetch_assoc($konsres);
}

function korjaa_erapaivat_ja_alet_ja_paivita_lasku($params) {
  global $kukarow, $yhtiorow;

  if ($params['toim'] == 'KATEINEN' or $params['toim'] == 'KATEISESTAKATEINEN') {

    $updlisa = "";

    if ($params['toim'] == "KATEISESTAKATEINEN") {
      $updlisa = "tapvm = '{$params['tapahtumapaiva']}',";

      $query = "UPDATE lasku set
                tapvm = '{$params['tapahtumapaiva']}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tila = 'L'
                AND alatila = 'X'
                AND laskunro = {$params['laskurow']['laskunro']}";
      pupe_query($query);

      $query = "UPDATE tiliointi set
                tapvm = '{$params['tapahtumapaiva']}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND korjattu = ''
                AND tapvm = '{$params['laskurow']['tapvm']}'
                AND ltunnus  = '{$params['tunnus']}'";
      pupe_query($query);

      $query = "SELECT tunnus
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND uusiotunnus  = '{$params['tunnus']}'
                AND laskutettuaika > 0";
      $rivires = pupe_query($query);

      while ($rivirow = mysql_fetch_assoc($rivires)) {
        $query = "UPDATE tilausrivi set
                  laskutettuaika = '{$params['tapahtumapaiva']}'
                  WHERE tunnus = {$rivirow['tunnus']}";
        pupe_query($query);

        $query = "UPDATE tapahtuma set
                  laadittu = '{$params['tapahtumapaiva']} 23:59:59'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND laji = 'laskutus'
                  AND rivitunnus = {$rivirow['tunnus']}";
        pupe_query($query);
      }
    }

    $query   = "UPDATE lasku set
                {$updlisa}
                erpcm       = '{$params['tapahtumapaiva']}',
                mapvm       = '{$params['tapahtumapaiva']}',
                maksuehto   = '{$params['maksuehto']}',
                kassalipas  = '{$params['kassalipas']}',
                kasumma     = 0
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$params['tunnus']}'";
    pupe_query($query);

    echo "<font class='message'>".t("Muutettin laskun")." {$params['laskurow']['laskunro']} ".t("maksuehdoksi")." ".t_tunnus_avainsanat($params['mehtorow'], "teksti", "MAKSUEHTOKV")."!</font><br>";
  }
  else {
    // korjaillaan eräpäivät ja kassa-alet
    if ($params['mehtorow']['abs_pvm'] === null) {
      $erapvm = "adddate('{$params['laskurow']['tapvm']}', interval {$params['mehtorow']['rel_pvm']} day)";
    }
    else {
      $erapvm = "'{$params['mehtorow']['abs_pvm']}'";
    }

    if ($params['mehtorow']['kassa_abspvm'] !== null or $params['mehtorow']["kassa_relpvm"] > 0) {
      if ($params['mehtorow']['kassa_abspvm'] === null) {
        $kassa_erapvm = "adddate('{$params['laskurow']['tapvm']}', interval {$params['mehtorow']['kassa_relpvm']} day)";
      }
      else {
        $kassa_erapvm = "'{$params['mehtorow']['kassa_abspvm']}'";
      }
      $kassa_loppusumma = round($params['laskurow']['summa'] * $params['mehtorow']['kassa_alepros'] / 100, 2);
    }
    else {
      $kassa_erapvm = "''";
      $kassa_loppusumma = "";
    }

    // päivitetään lasku
    $query = "UPDATE lasku set
              mapvm       = '',
              maksuehto   = '{$params['maksuehto']}',
              erpcm       = $erapvm,
              kapvm       = $kassa_erapvm,
              kasumma     = '$kassa_loppusumma',
              kassalipas  = 0
              where yhtio = '$kukarow[yhtio]'
              and tunnus  = '{$params['tunnus']}'";
    $result = pupe_query($query);

    if (mysql_affected_rows() > 0) {
      echo "<font class='message'>".t("Muutettin laskun")." {$params['laskurow']['laskunro']} ".t("maksuehdoksi")." ".t_tunnus_avainsanat($params['mehtorow'], "teksti", "MAKSUEHTOKV")." ".t("ja merkattiin maksu avoimeksi")."!</font><br>";
    }
    else {
      echo "<font class='error'>".t("Laskua")." {$params['laskurow']['laskunro']} ".t("ei pystytty muuttamaan")."!</font><br>";
    }
  }

  if (isset($params['mehtorow']["factoring_id"])) {
    $myysaatili = $yhtiorow['factoringsaamiset'];
  }
  elseif ($params['konsrow']["konserniyhtio"] != "") {
    $myysaatili = $yhtiorow['konsernimyyntisaamiset'];
  }
  else {
    $myysaatili = $yhtiorow['myyntisaamiset'];
  }

  return $myysaatili;
}

function hae_kassalipas($kassalipas_tunnus) {
  global $kukarow;
  $query = "SELECT *
            FROM kassalipas
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$kassalipas_tunnus}'";

  $result = pupe_query($query);

  return mysql_fetch_assoc($result);
}

function tee_kirjanpito_muutokset($params) {
  global $kukarow, $yhtiorow;

  if ($params['toim'] == 'KATEINEN' or $params['toim'] == 'KATEISESTAKATEINEN') {
    $uusitili  = $params['_kassalipas'];
    $vanhatili = '(' . $params['myysaatili'] . ')';
    $tapvmlisa = ", tapvm = '{$params['tapahtumapaiva']}' ";
  }
  else {
    $uusitili  = $params['myysaatili'];
    $vanhatili = '('.implode(',', $params['_kassalipas']).')';
    $tapvmlisa = "";
  }

  $query = "SELECT tunnus, summa
            FROM tiliointi
            WHERE yhtio  = '$kukarow[yhtio]'
            AND ltunnus  = '{$params['tunnus']}'
            AND tilino   IN {$vanhatili}
            AND korjattu = ''
            ORDER BY tapvm DESC, tunnus DESC
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {
    $vanharow = mysql_fetch_assoc($result);

    // Tehdään vastakirjaus alkuperäiselle tiliöinnille
    $tilid = kopioitiliointi($vanharow['tunnus'], "");

    if (($params['toim'] == 'KATEINEN' or $params['toim'] == 'KATEISESTAKATEINEN') and $params['laskurow']['saldo_maksettu'] != 0) {
      $summalisa = $params['laskurow']['summa'] - $params['laskurow']['saldo_maksettu'];
    }
    else {
      $summalisa = "summa";
    }

    $query = "UPDATE tiliointi
              SET summa   = {$summalisa} * -1,
              laatija     = '{$kukarow['kuka']}',
              laadittu    = now()
              {$tapvmlisa}
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '{$tilid}'";
    $result = pupe_query($query);

    // Kopsataan alkuperäinen ja päivitetään siille uudet tiedot
    $tilid = kopioitiliointi($vanharow['tunnus'], "");

    $kustplisa = $params['kustp'] != '' ? ", kustp = '{$params['kustp']}'" : "";

    if (($params['toim'] == 'KATEINEN' or $params['toim'] == 'KATEISESTAKATEINEN') and $params['laskurow']['saldo_maksettu'] != 0) {
      $summalisa = $params['laskurow']['summa'] - $params['laskurow']['saldo_maksettu'];
    }
    else {
      $summalisa = $vanharow['summa'];
    }

    $query = "UPDATE tiliointi
              SET tilino   = '{$uusitili}',
              summa       = '{$summalisa}',
              laatija     = '{$kukarow['kuka']}',
              laadittu    = now()
              {$tapvmlisa}
              {$kustplisa}
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '{$tilid}'";
    $result = pupe_query($query);

    if (mysql_affected_rows() > 0) {
      echo "<font class='message'>".t("Korjattiin kirjanpitoviennit")." (".mysql_affected_rows()." ".t("kpl").").</font><br>";
    }
    else {
      echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehdä! Korjaa kirjanpito käsin")."!</font><br>";
    }
    if ($params['laskurow']['summa'] > 0) {
      $summalisa = (($params['toim'] == 'KATEINEN' or $params['toim'] == 'KATEISESTAKATEINEN') and $params['laskurow']['saldo_maksettu'] != 0) ? 0 : ($params['laskurow']['summa'] - $vanharow['summa']);
    }
    else {
      if (($params['toim'] == 'KATEINEN' or $params['toim'] == 'KATEISESTAKATEINEN') and $params['laskurow']['saldo_maksettu'] != 0) {
        $summalisa = 0;
      }
      else {
        $summalisa = $params['laskurow']['summa'] + abs($vanharow['summa']);
      }
    }

    $query = "UPDATE lasku SET
              saldo_maksettu = {$summalisa}
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND tunnus     = '{$params['laskurow']['tunnus']}'";
    $updres = pupe_query($query);
  }
}

function yliviivaa_alet_ja_pyoristykset($tunnus) {
  global $kukarow, $yhtiorow;

  $query = "UPDATE tiliointi
            SET korjattu = '$kukarow[kuka]',
            korjausaika  = now()
            where yhtio  = '$kukarow[yhtio]'
            and ltunnus  = '$tunnus'
            and tilino   IN ('$yhtiorow[myynninkassaale]', '$yhtiorow[pyoristys]')
            and korjattu = ''";
  $result = pupe_query($query);

  if (mysql_affected_rows() > 0) {
    echo "<font class='message'>".t("Poistettiin pyöristys- ja kassa-alekirjaukset")." (".mysql_affected_rows()." ".t("kpl").").</font><br>";
  }
}

function tarkista_pyoristys_erotukset($laskurow, $tunnus) {
  global $kukarow , $yhtiorow;

  $query = "SELECT sum(summa) summa, sum(summa_valuutassa) summa_valuutassa
            FROM tiliointi
            WHERE yhtio  = '$kukarow[yhtio]'
            AND ltunnus  = '$tunnus'
            AND korjattu = ''";
  $result = pupe_query($query);
  $check1 = mysql_fetch_assoc($result);

  if ($check1['summa'] != 0) {
    $query = "INSERT into tiliointi set
              yhtio            = '$kukarow[yhtio]',
              ltunnus          = '$tunnus',
              tilino           = '$yhtiorow[pyoristys]',
              kustp            = 0,
              kohde            = 0,
              projekti         = 0,
              tapvm            = '$laskurow[tapvm]',
              summa            = -1 * $check1[summa],
              summa_valuutassa = -1 * $check1[summa_valuutassa],
              valkoodi         = '$laskurow[valkoodi]',
              vero             = 0,
              selite           = '".t("Pyöristysero")."',
              lukko            = '',
              laatija          = '$kukarow[kuka]',
              laadittu         = now()";
    $laskutusres = pupe_query($query);
  }
}

function hae_lasku2($laskuno, $toim) {
  global $kukarow;

  if ($toim == 'KATEINEN'  or $toim == 'KATEISESTAKATEINEN') {
    $query = "SELECT lasku.ytunnus,
              lasku.liitostunnus,
              lasku.*,
              lasku.tunnus ltunnus,
              maksuehto.tunnus,
              maksuehto.teksti,
              maksuehto.kateinen,
              asiakas.ytunnus asiakas_ytunnus,
              asiakas.nimi asiakas_nimi,
              asiakas.nimitark asiakas_nimitark,
              asiakas.osoite asiakas_osoite,
              asiakas.postino asiakas_postino,
              asiakas.postitp asiakas_postitp,
              asiakas.toim_nimi asiakas_toim_nimi,
              asiakas.toim_nimitark asiakas_toim_nimitark,
              asiakas.toim_osoite asiakas_toim_osoite,
              asiakas.toim_postino asiakas_toim_postino,
              asiakas.toim_postitp asiakas_toim_postitp,
              lasku.tapvm
              FROM lasku
              JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio AND lasku.maksuehto = maksuehto.tunnus)
              JOIN asiakas ON asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus
              WHERE lasku.yhtio   = '{$kukarow['yhtio']}'
              AND  lasku.laskunro = '{$laskuno}'
              AND lasku.tila      = 'U'
              AND lasku.alatila   = 'X'";
  }
  else {
    $query = "SELECT lasku.ytunnus,
              lasku.liitostunnus,
              lasku.*,
              lasku.tunnus ltunnus,
              maksuehto.tunnus,
              maksuehto.teksti,
              maksuehto.kateinen,
              asiakas.ytunnus asiakas_ytunnus,
              asiakas.nimi asiakas_nimi,
              asiakas.nimitark asiakas_nimitark,
              asiakas.osoite asiakas_osoite,
              asiakas.postino asiakas_postino,
              asiakas.postitp asiakas_postitp,
              asiakas.toim_nimi asiakas_toim_nimi,
              asiakas.toim_nimitark asiakas_toim_nimitark,
              asiakas.toim_osoite asiakas_toim_osoite,
              asiakas.toim_postino asiakas_toim_postino,
              asiakas.toim_postitp asiakas_toim_postitp,
              lasku.tapvm
              FROM lasku
              JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio AND lasku.maksuehto = maksuehto.tunnus AND maksuehto.kateinen != ''
              JOIN asiakas ON asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus
              WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
              AND lasku.laskunro = '{$laskuno}'
              AND lasku.tila     = 'U'
              AND lasku.alatila  = 'X'";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei löydy sopivaa laskua")."!</font><br><br>";
    return FALSE;
  }

  $row = mysql_fetch_assoc($result);

  $tilikausi = tarkista_saako_laskua_muuttaa($row['tapvm']);

  if ($toim == 'KATEINEN' and $row['kateinen'] != '') {
    echo "<font class='error'>".t("VIRHE: Lasku on jo käteislasku")."!</font><br><br>";
    return FALSE;
  }
  elseif ($toim == 'KATEINEN' and $row['mapvm'] != '0000-00-00') {
    echo "<font class='error'>".t("VIRHE: Lasku on jo maksettu")."!</font><br><br>";
    return FALSE;
  }
  elseif (!empty($tilikausi) and $toim != 'KATEINEN' and $toim != 'KATEISESTAKATEINEN') {
    echo "<font class='error'>".t("VIRHE: Tilikausi on päättynyt %s. Et voi muuttaa käteistä laskuksi %s", "", $tilikausi, $row['tapvm'])."!</font>";
    return FALSE;
  }

  return $row;
}

function echo_lasku_table($laskurow, $toim) {
  global $kukarow;

  echo "<form method='post' autocomplete='off'>";
  echo "<input name='tunnus' type='hidden' value='$laskurow[ltunnus]'>";
  echo "<input name='kateinen' type='hidden' value='{$laskurow['kateinen']}'>";

  if (!empty($laskurow['asiakas_toim_osoite'])) {
    $asiakas_string = "<tr><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_nimi] $laskurow[asiakas_nimitark]<br> $laskurow[asiakas_osoite]<br> $laskurow[asiakas_postino] $laskurow[asiakas_postitp]</td><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_toim_nimi] $laskurow[asiakas_toim_nimitark]<br> $laskurow[asiakas_toim_osoite]<br> $laskurow[asiakas_toim_postino] $laskurow[asiakas_toim_postitp]</td></tr>";
  }
  else {
    $asiakas_string = "<tr><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_nimi] $laskurow[asiakas_nimitark]<br> $laskurow[asiakas_osoite]<br> $laskurow[asiakas_postino] $laskurow[asiakas_postitp]</td><td>$laskurow[asiakas_ytunnus]<br> $laskurow[asiakas_nimi] $laskurow[asiakas_nimitark]<br> $laskurow[asiakas_osoite]<br> $laskurow[asiakas_postino] $laskurow[asiakas_postitp]</td></tr>";
  }

  $osasuoritus_string = "";

  if ($laskurow['saldo_maksettu'] != 0) {
    $osasuoritus_string = "<tr><th>".t("Osasuoritukset")."</th><td>{$laskurow['saldo_maksettu']}</td></tr>";
    $osasuoritus_string .= "<tr><th>".t("Laskua maksamatta")."</th><td>".($laskurow['summa'] - $laskurow['saldo_maksettu'])."</td></tr>";
  }

  echo "<table>";
  echo "<tr><th>", t("Laskutusosoite"), "</th><th>", t("Toimitusosoite"), "</th></tr>";
  echo $asiakas_string;
  echo "<tr><th>", t("Laskunumero"), "</th><td>{$laskurow['laskunro']}</td></tr>";
  echo "<tr><th>", t("Laskun summa"), "</th><td>{$laskurow['summa']}</td></tr>";
  echo "<tr><th>", t("Laskun summa (veroton)"), "</th><td>{$laskurow['arvo']}</td></tr>";
  echo $osasuoritus_string;
  echo "<tr><th>", t("Maksuehto"), "</th><td>", t_tunnus_avainsanat($laskurow, "teksti", "MAKSUEHTOKV"), "</td></tr>";

  if ($toim == 'KATEINEN' or $toim == 'KATEISESTAKATEINEN') {
    if ($toim == 'KATEINEN') {
      $now = date('Y-m-d');
      $now = explode('-' , $now);
    }
    else {
      $now = explode('-' , $laskurow['tapvm']);
    }

    // haetaan kaikki käteisen maksuehdot
    $query = "SELECT *
              FROM kassalipas
              WHERE yhtio = '{$kukarow['yhtio']}'";
    $result = pupe_query($query);

    echo '<tr>';
    echo "<th>".t('Kassalipas')."</th>";
    echo '<td>';
    echo '<select name="kassalipas">';

    while ($row = mysql_fetch_assoc($result)) {

      $sel = $laskurow['kassalipas'] == $row['tunnus'] ? " selected" : "";
      if ($sel == '') {
        $sel = $kukarow['kassamyyja'] == $row['tunnus'] ? " selected" : "";
      }
      echo "<option value='{$row['tunnus']}'{$sel}>".t($row['nimi'])."</option>";
    }

    echo '</select>';
    echo '</td>';
    echo '</tr>';

    echo "<tr><th>".t("Tapahtumapäivä (pp-kk-vvvv)")."</th><td><input name='tapahtumapaiva_pp' type='text' size='3' value='".$now[2]."'/>-<input name='tapahtumapaiva_kk' type='text' size='3' value='".$now[1]."'/>-<input name='tapahtumapaiva_vv' type='text' size='5' value='".$now[0]."'/></td></tr>";

    $query = "SELECT *
              FROM maksuehto
              WHERE yhtio   = '$kukarow[yhtio]'
              and kateinen != ''
              and kaytossa  = ''
              ORDER BY jarjestys, teksti";
  }
  else {
    echo "<tr><th>".t("Tapahtumapäivä")."</th><td>$laskurow[tapvm]</td></tr>";

    // haetaan kaikki maksuehdot (paitsi käteinen)
    $query = "SELECT *
              FROM maksuehto
              WHERE yhtio  = '$kukarow[yhtio]'
              and kateinen = ''
              and kaytossa = ''
              ORDER BY jarjestys, teksti";
  }
  $vresult = pupe_query($query);

  echo "<tr><th>".t("Uusi maksuehto")."</th>";
  echo "<td>";
  echo "<select name='maksuehto'>";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel = $laskurow['maksuehto'] == $vrow['tunnus'] ? "SELECTED" : "";
    echo "<option value='$vrow[tunnus]' $sel>".t_tunnus_avainsanat($vrow, "teksti", "MAKSUEHTOKV")."</option>";
  }

  echo "</select>";
  echo "</td></tr></table><br>";
  echo "<input name='subnappi' type='submit' value='".t("Muuta maksuehto")."'></td>";
  echo "</form>";
}

function echo_lasku_search() {
  echo "<form name='eikat' method='post' autocomplete='off'>";
  echo "<table><tr>";
  echo "<th>".t("Syötä laskunumero")."</th>";
  echo "<td><input type='text' name='laskuno'></td>";
  echo "<td class='back'><input name='subnappi' type='submit' value='".t("Etsi")."'></td>";
  echo "</tr></table>";
  echo "</form>";
}

function hae_kassalippaan_tiedot($kassalipas, $mehtorow, $laskurow) {
  global $yhtiorow, $kukarow;

  $kustp = "";

  if ($mehtorow['kateinen'] != '') {

    $query = "SELECT *
              FROM kassalipas
              WHERE yhtio = '{$kukarow['yhtio']}'
              and tunnus  = '{$kassalipas}'";
    $kateisresult = pupe_query($query);
    $kateisrow = mysql_fetch_assoc($kateisresult);

    if ($mehtorow['kateinen'] == "n") {
      if ($kateisrow["pankkikortti"] != "") {
        $kustp     = $kateisrow['kustp'];
        $myysaatili = $kateisrow['pankkikortti'];
      }
      else {
        $myysaatili = $yhtiorow['pankkikortti'];
      }
    }

    if ($mehtorow['kateinen'] == "o") {
      if ($kateisrow["luottokortti"] != "") {
        $kustp     = $kateisrow['kustp'];
        $myysaatili = $kateisrow['luottokortti'];
      }
      else {
        $myysaatili = $yhtiorow['luottokortti'];
      }
    }

    if ($mehtorow['kateinen'] == 'p') {
      if ($kateisrow['kassa'] != '') {
        $kustp     = $kateisrow['kustp'];
        $myysaatili = $kateisrow['kassa'];
      }
      else {
        $myysaatili = $yhtiorow['kassa'];
      }
    }

    if ($myysaatili == "") {
      if ($kateisrow["kassa"] != "") {
        $kustp     = $kateisrow['kustp'];
        $myysaatili = $kateisrow['kassa'];
      }
      else {
        $myysaatili = $yhtiorow['kassa'];
      }
    }
  }
  else {
    if ($laskurow['kassalipas'] != '') {
      //haetaan kassalippaan tilit kassalippaan takaa
      $kassalipas_query = "SELECT kassa,
                           pankkikortti,
                           luottokortti
                           FROM kassalipas
                           WHERE yhtio = '{$kukarow['yhtio']}'
                           AND tunnus  = '{$laskurow['kassalipas']}'";
      $kassalipas_result = pupe_query($kassalipas_query);

      $kassalippaat = mysql_fetch_assoc($kassalipas_result);

      if (!empty($kassalippaat)) {
        $myysaatili = $kassalippaat;
      }
      else {
        $myysaatili = array(
          'kassa' => $yhtiorow['kassa'],
          'pankkikortti' => $yhtiorow['pankkikortti'],
          'luottokortti' => $yhtiorow['luottokortti']
        );
      }
    }
    else {
      $myysaatili = array(
        'kassa' => $yhtiorow['kassa'],
        'pankkikortti' => $yhtiorow['pankkikortti'],
        'luottokortti' => $yhtiorow['luottokortti']
      );
    }
  }

  return array($myysaatili, $kustp);
}

function tarkista_saako_laskua_muuttaa($tapahtumapaiva) {
  global $kukarow, $yhtiorow;

  if (strtotime($yhtiorow['tilikausi_alku']) <= strtotime($tapahtumapaiva) and strtotime($yhtiorow['tilikausi_loppu']) >= strtotime($tapahtumapaiva)) {
    return false;
  }
  else {
    return $yhtiorow['tilikausi_alku'];
  }

}

require "inc/footer.inc";
