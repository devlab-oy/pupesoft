<?php

// Ei käytetä pakkausta
$compression = FALSE;
$_cli = php_sapi_name() == 'cli' ? true : false;

if ($_cli) {
  // Otetaan tietokanta connect
  require_once "inc/connect.inc";
  require_once "inc/functions.inc";

  if (!isset($argv[1])) {
    echo "Anna yhtio!\n";
    die;
  }

  if (!isset($argv[2])) {
    echo "Anna toiminto!\n";
    die;
  }

  $_permitted = array('CLI_TUOTTEETTOMAT');

  $kukarow['yhtio'] = mysql_real_escape_string($argv[1]);
  $tee = $argv[2];

  if (!in_array($tee, $_permitted)) {
    echo "Toiminto {$tee} ei ole sallittu!\n";
    die;
  }
}
else {
  require "inc/parametrit.inc";
}

if (!function_exists('hae_tuotteettomat')) {
  function hae_tuotteettomat() {
    global $kukarow;

    $query = "SELECT tuotepaikat.tunnus ttun,
              tuotepaikat.tuoteno,
              tuotepaikat.saldo,
              tuotepaikat.oletus,
              concat_ws('-', tuotepaikat.hyllyalue,
                             tuotepaikat.hyllynro,
                             tuotepaikat.hyllyvali,
                             tuotepaikat.hyllytaso) paikka,
              tuote.tunnus
              FROM tuotepaikat
              LEFT JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio
                AND tuote.tuoteno     = tuotepaikat.tuoteno)
              WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
              AND (tuote.tunnus is null or tuote.ei_saldoa != '')
              ORDER BY tuotepaikat.tuoteno";
    $result = pupe_query($query);

    return $result;
  }
}

if (!$_cli) {
  echo "<font class='head'>".t("Varastopaikkojen seuranta")."</font><hr>";
}

if ($tee == 'CLEAN') {

  if (!$_cli) {
    echo t("Poistetaan").": ".count($valittu)." ".t("varastopaikkaa!")."!<br>";
  }

  if (count($valittu) != 0) {
    foreach ($valittu as $rastit) {
      //Otetaan tuotenumero talteen
      $query = "SELECT *
                FROM tuotepaikat
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$rastit'";
      $presult = pupe_query($query);
      $tuoterow = mysql_fetch_array($presult);

      //Poistetaan nollapaikka
      $query = "DELETE FROM tuotepaikat
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$rastit'
                and saldo   = 0";
      $result = pupe_query($query);

      $paikka = "$tuoterow[hyllyalue] $tuoterow[hyllynro] $tuoterow[hyllyvali] $tuoterow[hyllytaso]";

      //Tehdään tapahtuma
      $query = "INSERT into tapahtuma set
                yhtio     = '$kukarow[yhtio]',
                tuoteno   = '$tuoterow[tuoteno]',
                kpl       = '0',
                kplhinta  = '0',
                hinta     = '0',
                hyllyalue = '$tuoterow[hyllyalue]',
                hyllynro  = '$tuoterow[hyllynro]',
                hyllytaso = '$tuoterow[hyllytaso]',
                hyllyvali = '$tuoterow[hyllyvali]',
                laji      = 'poistettupaikka',
                selite    = '".t("Poistettiin tuotepaikka")." $paikka',
                laatija   = '$kukarow[kuka]',
                laadittu  = now()";
      $result = pupe_query($query);

      //Katsotaan onko oletuspaikka ok
      $query = "SELECT sum(1) kaikkipaikat,
                sum(if(oletus != '', 1, 0)) oletuspaikat
                from tuotepaikat
                where yhtio = '$kukarow[yhtio]'
                and tuoteno = '$tuoterow[tuoteno]'";
      $presult = pupe_query($query);

      if (mysql_num_rows($presult) > 0) {
        $prow = mysql_fetch_array($presult);

        if ($prow["kaikkipaikat"] > 0 and $prow["oletuspaikat"] == 0) {
          $query = "UPDATE tuotepaikat
                    SET oletus = 'X',
                    muuttaja    = '$kukarow[kuka]',
                    muutospvm   = now()
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tuoteno = '$tuoterow[tuoteno]'
                    ORDER BY hyllyalue
                    LIMIT 1";
          $bresult = pupe_query($query);
        }
      }
    }

    if (!$_cli) {
      echo t("Valitut tuotepaikat poistettu")."!<br><br>";
    }
  }
  else {
    if (!$_cli) {
      echo t("Et valinnut yhtään paikkaa poistettavaksi")."!<br><br>";
    }
  }

  $tee = "";
}

if ($tee == 'CLEANOLETUKSET') {

  $query = "SELECT tuoteno,
            sum(if(oletus != '', 1, 0)) oletukset,
            min(tunnus) mintunnus,
            group_concat(tunnus order by tunnus) tunnukset
            FROM tuotepaikat
            WHERE yhtio = '{$kukarow["yhtio"]}'
            GROUP BY tuoteno
            HAVING sum(if(oletus != '', 1, 0)) != 1
            ORDER BY tuoteno";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    if (!$_cli) {
      echo t("Korjataan oletuspaikkoja").":<br>";
    }

    while ($lrow = mysql_fetch_array($result)) {

      if (!$_cli) {
        echo "Korjataan oletuspaikka: $lrow[tuoteno]<br>";
      }

      if ($lrow["oletukset"] == 0) {
        $query = "UPDATE tuotepaikat
                  SET oletus  = 'X',
                  muuttaja    = '$kukarow[kuka]',
                  muutospvm   = now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$lrow[mintunnus]'";
        $presult = pupe_query($query);
      }
      else {
        $query = "UPDATE tuotepaikat
                  SET oletus  = '',
                  muuttaja    = '$kukarow[kuka]',
                  muutospvm   = now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  in ($lrow[tunnukset])";
        $presult = pupe_query($query);

        $query = "UPDATE tuotepaikat
                  SET oletus  = 'X',
                  muuttaja    = '$kukarow[kuka]',
                  muutospvm   = now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$lrow[mintunnus]'";
        $presult = pupe_query($query);
      }
    }
  }

  $tee = "";
}

if ($tee == 'CLI_TUOTTEETTOMAT' and $_cli) {
  $result = hae_tuotteettomat();

  if (mysql_num_rows($result) > 0) {

    $paikkatunnus = array();

    while ($lrow = mysql_fetch_assoc($result)) {
      array_push($paikkatunnus, $lrow['ttun']);
    }

    $tee = 'CLEANTUOTTEETTOMAT';
  }
}

if ($tee == 'CLEANTUOTTEETTOMAT') {

  $poistettu = 0;

  foreach ($paikkatunnus as $tunnus) {
    $query = "DELETE
              FROM tuotepaikat
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$tunnus'";
    $presult = pupe_query($query);
    $poistettu++;
  }

  $tee = "";
}

if ($tee == 'CLEANTUNTEMATTOMAT') {

  foreach ($paikkatunnus as $tunnus) {

    // haetaan muutettava paikka
    $query = "SELECT *
              FROM tuotepaikat
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$tunnus'";
    $presult = pupe_query($query);
    $paikkarow = mysql_fetch_array($presult);

    // katsotaan löytyykö paikka jo ennestään
    $query = "SELECT *
              FROM tuotepaikat
              WHERE yhtio   = '$kukarow[yhtio]'
              and tuoteno   = '$paikkarow[tuoteno]'
              and hyllyalue = '$hyllyalue'
              and hyllynro  = '$hyllynro'
              and hyllyvali = '$hyllyvali'
              and hyllytaso = '$hyllytaso'";
    $presult = pupe_query($query);

    if (mysql_num_rows($presult) == 1) {
      $vanharow = mysql_fetch_array($presult);

      $query = "UPDATE tuotepaikat
                SET saldo = saldo + $paikkarow[saldo],
                saldo_varattu = saldo_varattu + $paikkarow[saldo_varattu],
                saldoaika     = now(),
                muuttaja      = '$kukarow[kuka]',
                muutospvm     = now()
                WHERE yhtio   = '$kukarow[yhtio]'
                and tunnus    = '$vanharow[tunnus]'";
      $presult = pupe_query($query);

      $query = "DELETE
                FROM tuotepaikat
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$tunnus'";
      $presult = pupe_query($query);
    }
    elseif (mysql_num_rows($presult) == 0) {
      $query = "UPDATE tuotepaikat
                SET hyllyalue = '$hyllyalue',
                hyllynro    = '$hyllynro',
                hyllyvali   = '$hyllyvali',
                hyllytaso   = '$hyllytaso',
                muuttaja    = '$kukarow[kuka]',
                muutospvm   = now()
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$tunnus'";
      $presult = pupe_query($query);
    }
    else {
      if (!$_cli) {
        echo "Tuotteella on virheellisiä tuotepaikkoja! Korjaa ne ensin! Tuoteno: $paikkarow[tuoteno]<br>";
      }
    }

  }

  $tee = "";
}

if ($tee == 'CLEANRIVIT') {

  foreach ($rivitunnus as $tunnus) {

    // haetaan tilausrivi
    $query = "SELECT *
              FROM tilausrivi
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND tunnus  = {$tunnus}";
    $presult = pupe_query($query);

    if (mysql_num_rows($presult) != 1) {
      if (!$_cli) {
        echo "Tunnuksella {$tunnus} ei löytynyt riviä!<br>";
      }
      continue;
    }

    $rivirow = mysql_fetch_array($presult);

    // haetaan oletuspaikka
    $query = "SELECT *
              FROM tuotepaikat
              WHERE yhtio  = '{$kukarow["yhtio"]}'
              and tuoteno  = '{$rivirow["tuoteno"]}'
              and oletus  != ''";
    $presult = pupe_query($query);

    if (mysql_num_rows($presult) != 1) {
      if (!$_cli) {
        echo "Tuotteelle {$rivirow["tuoteno"]} ei löytynyt oletuspaikkaa!<br>";
      }
      continue;
    }

    $paikkarow = mysql_fetch_array($presult);

    $query = "UPDATE tilausrivi
              SET hyllyalue = '{$paikkarow["hyllyalue"]}',
              hyllynro    = '{$paikkarow["hyllynro"]}',
              hyllyvali   = '{$paikkarow["hyllyvali"]}',
              hyllytaso   = '{$paikkarow["hyllytaso"]}'
              WHERE yhtio = '{$kukarow["yhtio"]}'
              and tunnus  = {$tunnus}";
    $presult = pupe_query($query);
  }

  $tee = "";
}

if ($tee == 'CLEANTAPAHTUMAT') {

  foreach ($rivitunnus as $tunnus) {

    // haetaan tilausrivi
    $query = "SELECT *
              FROM tapahtuma
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$tunnus'";
    $presult = pupe_query($query);
    $rivirow = mysql_fetch_array($presult);

    // haetaan oletuspaikka
    $query = "SELECT *
              FROM tuotepaikat
              WHERE yhtio  = '$kukarow[yhtio]'
              and tuoteno  = '$rivirow[tuoteno]'
              and oletus  != ''";
    $presult = pupe_query($query);

    if (mysql_num_rows($presult) == 1) {
      $paikkarow = mysql_fetch_array($presult);
      $query = "UPDATE tapahtuma
                SET hyllyalue = '$paikkarow[hyllyalue]',
                hyllynro    = '$paikkarow[hyllynro]',
                hyllyvali   = '$paikkarow[hyllyvali]',
                hyllytaso   = '$paikkarow[hyllytaso]'
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$tunnus'";
      $presult = pupe_query($query);
    }
    else {
      if (!$_cli) {
        echo "Tuotteelle $rivirow[tuoteno] ei löytynyt oletuspaikkaa!<br>";
      }
    }
  }

  $tee = "";
}

if ($tee == 'LISTAAOLETUKSET' and !$_cli) {

  echo "<font class='message'>".t("Tuotteet, joilla on virheellisiä oletuspaikkoja")."</font><hr>";

  $query = "SELECT tuotepaikat.tuoteno,
            sum(if(tuotepaikat.oletus != '', 1, 0)) as oletukset
            FROM tuotepaikat
            WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
            GROUP BY tuotepaikat.tuoteno
            HAVING sum(if(tuotepaikat.oletus != '', 1, 0)) != 1
            ORDER BY tuotepaikat.tuoteno";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<table>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='tee' value='CLEANOLETUKSET'>";

    if (mysql_num_rows($result) < 500) {

      echo "<tr><th>".t("Tuoteno")."</th>";
      echo "<th>".t("Oletuspaikkojen määrä")."</th></tr>";

      $saldolliset = array();

      while ($lrow = mysql_fetch_array($result)) {
        echo "<tr>";
        echo "<td><a href='tuote.php?tee=Z&tuoteno=".urlencode($lrow["tuoteno"])."'>$lrow[tuoteno]</a></td>";
        echo "<td>$lrow[oletukset]</td>";
        echo "</tr>";
      }

      echo "<tr><tdclass='back'><br><br></td></tr>";
    }
    else {
      echo t("Virheellisiä oletuspaikkoja on yli %s. Niitä ei näytetä ruudulla.", "", 500)."<br>";
    }

    echo "</table><br><br>";
    echo "<input type='submit' value='".t("Korjaa oletuspaikat")."'></form>";
    echo "</table><br><br>";
  }
  else {
    echo t("Ei yhtään virheellistä oletuspaikkaa")."!<br><br>";
    $tee = "";
  }
}

if ($tee == 'LISTAATUNTEMATTOMAT' and !$_cli) {

  echo "<font class='message'>".t("Tuotepaikat, joiden varastopaikka ei kuulu mihinkään varastoon")."</font><hr>";

  $query = "SELECT tuotepaikat.tunnus ttun,
            tuotepaikat.tuoteno,
            tuotepaikat.saldo,
            tuotepaikat.oletus,
            concat_ws('-', tuotepaikat.hyllyalue,
                           tuotepaikat.hyllynro,
                           tuotepaikat.hyllyvali,
                           tuotepaikat.hyllytaso) paikka,
            varastopaikat.tunnus
            FROM tuotepaikat USE INDEX (tuote_index)
            LEFT OUTER JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0'))
                                    <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'), lpad(upper(tuotepaikat.hyllynro), 5, '0'))
              AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0'))
                                    >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'), lpad(upper(tuotepaikat.hyllynro), 5, '0')))
            WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
            AND varastopaikat.tunnus is null";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Tuoteno")."</th>";
    echo "<th>".t("Saldo")."</th>";
    echo "<th>".t("Varastopaikka")."</th>";
    echo "</tr>";

    echo "<form method='POST'>";
    echo "<input type='hidden' name='tee' value='CLEANTUNTEMATTOMAT'>";

    while ($lrow = mysql_fetch_array($result)) {
      echo "<input type='hidden' name='paikkatunnus[$lrow[ttun]]' value='$lrow[ttun]'>";
      echo "<tr>";
      echo "<td><a href='tuote.php?tee=Z&tuoteno=".urlencode($lrow["tuoteno"])."'>$lrow[tuoteno]</a></td>";
      echo "<td>$lrow[saldo]</td>";
      echo "<td>$lrow[paikka]</td>";
      echo "</tr>";
    }

    echo "<tr><td colspan='3' class='back'><br><br></td></tr>";

    echo "</table><br>";

    echo "<table><tr><th>".t("Siirrä paikalle")."</th></tr>
    <tr><td>
    ".t("Alue")." ", hyllyalue("hyllyalue", ''), "
    ".t("Nro")."  <input type = 'text' name = 'hyllynro'  size = '5' maxlength='5' value = ''>
    ".t("Väli")." <input type = 'text' name = 'hyllyvali' size = '5' maxlength='5' value = ''>
    ".t("Taso")." <input type = 'text' name = 'hyllytaso' size = '5' maxlength='5' value = ''>
    </td></tr>";
    echo "</table><br>";

    echo "<input type='submit' value='".t("Korjaa tuotepaikat")."'></form>";
    echo "</table><br><br>";
  }
  else {
    echo t("Kaikkien tuotteiden varastopaikka kuuluu johonkin varastoon")."!<br><br>";
    $tee = "";
  }
}

if ($tee == 'LISTAATUOTTEETTOMAT' and !$_cli) {

  echo "<font class='message'>".t("Tuotepaikat, joiden tuotetta ei löydy")."</font><hr>";

  $result = hae_tuotteettomat();

  if (mysql_num_rows($result) > 0) {

    echo "<table>";
    echo "<tr><th>".t("Tuoteno")."</th>";
    echo "<th>".t("Saldo")."</th>";
    echo "<th>".t("Varastopaikka")."</th></tr>";

    echo "<form method='POST'>";
    echo "<input type='hidden' name='tee' value='CLEANTUOTTEETTOMAT'>";

    while ($lrow = mysql_fetch_array($result)) {
      echo "<input type='hidden' name='paikkatunnus[]' value='$lrow[ttun]'>";
      echo "<tr>";
      echo "<td><a href='tuote.php?tee=Z&tuoteno=".urlencode($lrow["tuoteno"])."'>$lrow[tuoteno]</a></td>";
      echo "<td>$lrow[saldo]</td>";
      echo "<td>$lrow[paikka]</td>";
      echo "</tr>";
    }

    echo "<tr><td colspan='3' class='back'><br><br></td></tr>";
    echo "</table><br>";

    echo "<input type='submit' value='".t("Poista tuotepaikat")."'></form>";

  }
  else {
    echo t("Kaikkien tuotepaikkojen tuotteet löytyvät")."!<br><br>";
    $tee = "";
  }
}

if ($tee == 'LISTAA' and !$_cli) {
  $lisaa  = "";

  if ($osasto != '') {
    $lisaa .= " and tuote.osasto = '$osasto' ";
  }

  if ($tuoryh != '') {
    $lisaa .= " and tuote.try = '$tuoryh' ";
  }

  if ($varasto == 'EI') {
    $lisaa .= " and varastopaikat.tunnus is null ";
  }
  elseif ($varasto != '') {
    $lisaa .= " and varastopaikat.tunnus = '$varasto' ";
  }

  if ($vva != '' and $kka != '' and $ppa != '') {
    $lisaa .= "and tuotepaikat.saldoaika <= '$vva-$kka-$ppa'";
  }

  $lisaa2 = "and tuotepaikat.saldo = 0";

  if ($miinus != '' and $plus == '') {
    $lisaa2 = "and tuotepaikat.saldo <= 0";
  }

  if ($plus != '' and $miinus == '') {
    $lisaa2 = "and tuotepaikat.saldo >= 0";
  }

  if ($plus != '' and $miinus != '') {
    $lisaa2 = "and tuotepaikat.saldo <> 0";
  }

  if ($vainmiinus != '') {
    $lisaa2 = "and tuotepaikat.saldo < 0";
  }

  if ($ahyllyalue != '') {
    $apaikka  = strtoupper(sprintf("%-05s", $ahyllyalue));
    $apaikka .= strtoupper(sprintf("%05s", $ahyllynro));
    $apaikka .= strtoupper(sprintf("%05s", $ahyllyvali));
    $apaikka .= strtoupper(sprintf("%05s", $ahyllytaso));

    $lisaa .= " and concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),
                           lpad(upper(tuotepaikat.hyllynro), 5, '0'),
                           lpad(upper(tuotepaikat.hyllyvali), 5, '0'),
                           lpad(upper(tuotepaikat.hyllytaso), 5, '0')) >= '$apaikka' ";
  }

  if ($lhyllyalue != '') {
    $lpaikka  = strtoupper(sprintf("%-05s", $lhyllyalue));
    $lpaikka .= strtoupper(sprintf("%05s", $lhyllynro));
    $lpaikka .= strtoupper(sprintf("%05s", $lhyllyvali));
    $lpaikka .= strtoupper(sprintf("%05s", $lhyllytaso));

    $lisaa .= " and concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),
                           lpad(upper(tuotepaikat.hyllynro), 5, '0'),
                           lpad(upper(tuotepaikat.hyllyvali), 5, '0'),
                           lpad(upper(tuotepaikat.hyllytaso), 5, '0')) <= '$lpaikka' ";
  }

  $query = "SELECT tuotepaikat.*,
            tuote.nimitys,
            concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka,
            varastopaikat.nimitys varasto,
            tuotepaikat.tunnus paikkatun,
            concat(rpad(upper(hyllyalue), 5, '0'),
                   lpad(upper(hyllynro),  5, '0'),
                   lpad(upper(hyllyvali), 5, '0'),
                   lpad(upper(hyllytaso), 5, '0')) sorttauskentta
            FROM tuotepaikat
            LEFT JOIN tuote ON (tuote.yhtio = tuotepaikat.yhtio
              and tuote.tuoteno        = tuotepaikat.tuoteno)
            LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
              AND varastopaikat.tunnus = tuotepaikat.varasto)
            WHERE tuotepaikat.yhtio    = '{$kukarow["yhtio"]}'
            $lisaa
            $lisaa2
            ORDER BY sorttauskentta, tuoteno";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<table>";
    echo "<tr><th>".t("Del")."</th><th>".t("Tuoteno")."</th>";
    echo "<th>".t("Nimitys")."</th>";
    echo "<th>".t("Saldo")."</th>";
    echo "<th>".t("Saldoaika")."</th>";
    echo "<th>".t("Varastopaikka")."</th>";
    echo "<th>".t("Varasto")."</th></tr>";


    echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
      <!--

      function toggleAll(toggleBox) {

        var currForm = toggleBox.form;
        var isChecked = toggleBox.checked;

        for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
          if (currForm.elements[elementIdx].type == 'checkbox') {
            currForm.elements[elementIdx].checked = isChecked;
          }
        }
      }

      //-->
      </script>";


    echo "<form method='POST'>";
    echo "<input type='hidden' name='tee' value='CLEAN'>";

    $saldolliset = array();

    while ($lrow = mysql_fetch_array($result)) {
      echo "<tr>";

      if ($lrow["saldo"] == 0.00) {
        echo "<td><input type='checkbox' value='$lrow[paikkatun]' name='valittu[]'></td>";
      }
      else {
        $saldolliset[] = $lrow["paikkatun"];
        echo "<td></td>";
      }

      echo "<td><a href='tuote.php?tee=Z&tuoteno=".urlencode($lrow["tuoteno"])."'>$lrow[tuoteno]</a></td>";
      echo "<td>".t_tuotteen_avainsanat($lrow, 'nimitys')."</td>";
      echo "<td>$lrow[saldo]</td>";
      echo "<td>".substr($lrow["saldoaika"], 0, 10)."</td>";
      echo "<td>$lrow[paikka]</td>";
      echo "<td>$lrow[varasto]</td>";
      echo "</tr>";
    }

    echo "<tr><td colspan='7' class='back'><br><br></td></tr>";

    echo "<tr>";
    echo "<td><input type='checkbox' name='chbox' onclick='toggleAll(this)'></td>";
    echo "<td colspan='6'>Ruksaa kaikki</td>";
    echo "</tr>";

    echo "</table><br><br>";
    echo "<input type='submit' value='".t("Mitätöi valitut tuotepaikat")."'></form>";
    echo "</table><br><br>";

    echo "<form method='POST' action='inventointi_listat.php'>";
    echo "<input type='hidden' name='tee' value='TULOSTA'>";

    $saldot = "";
    foreach ($saldolliset as $saldo) {
      $saldot .= "$saldo,";
    }
    $saldot = substr($saldot, 0, -1);

    echo "<input type='hidden' name='saldot' value='$saldot'>";
    echo "<input type='hidden' name='tila' value='SIIVOUS'>";
    echo "<input type='submit' value='".t("Luo saldollisista inventointilista")."'></form>";
  }
  else {
    echo t("Yhtään tuotetta ei löytynyt")."!<br><br>";
    $tee = "";
  }
}

if ($tee == "LISTAAVIRHEELLISETRIVIT" and !$_cli) {

  echo "<font class='message'>".t("Tilausrivit, joiden tuotepaikkoja ei löydy")."</font><hr>";

  $laskuri = 0;
  $naytetaan_rivit = (isset($ei_nayteta_riveja) and $ei_nayteta_riveja != '') ? false : true;

  // haetaan kaikki tilausrivit, jotka ei kuulu mihinkään varastoon
  $query = "SELECT tilausrivi.tuoteno,
            tilausrivi.tunnus,
            tilausrivi.tyyppi,
            tilausrivi.jt + tilausrivi.varattu varattu,
            tilausrivi.var,
            concat_ws('-', tilausrivi.hyllyalue,
                           tilausrivi.hyllynro,
                           tilausrivi.hyllyvali,
                           tilausrivi.hyllytaso) paikka
            FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
            INNER JOIN tuote on (tuote.yhtio = tilausrivi.yhtio
              and tuote.tuoteno    = tilausrivi.tuoteno
              and tuote.ei_saldoa  = '')
            LEFT OUTER JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
              AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'), lpad(upper(varastopaikat.alkuhyllynro),  5, '0'))
                                   <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'), lpad(upper(tilausrivi.hyllynro), 5, '0'))
              AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0'))
                                   >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'), lpad(upper(tilausrivi.hyllynro), 5, '0')))
            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
            AND tilausrivi.tyyppi  in ('L','G','V','W','M')
            AND tilausrivi.jt + tilausrivi.varattu != 0
            AND (varastopaikat.tunnus is null or tilausrivi.hyllyalue='')
            ORDER BY tilausrivi.tuoteno";
  $tilrivires = pupe_query($query);

  echo "<form method='POST'>";
  echo "<input type='hidden' name='tee' value='CLEANRIVIT'>";

  if ($naytetaan_rivit) {
    echo "<table>";
    echo "<tr><th>".t("Tuoteno")."</th>";
    echo "<th>".t("Varattu")."</th>";
    echo "<th>".t("Tyyppi")."</th>";
    echo "<th>".t("Varastopaikka")."</th></tr>";
  }

  while ($tuoterow = mysql_fetch_array($tilrivires)) {
    echo "<input type='hidden' name='rivitunnus[]' value='$tuoterow[tunnus]'>";

    if ($naytetaan_rivit) {
      echo "<tr>";
      echo "<td><a href='tuote.php?tee=Z&tuoteno=".urlencode($tuoterow["tuoteno"])."'>$tuoterow[tuoteno]</a></td>";
      echo "<td>$tuoterow[varattu]</td>";
      echo "<td>$tuoterow[var]</td>";
      echo "<td>$tuoterow[paikka]</td>";
      echo "</tr>";
    }
    $laskuri++;
  }

  if ($naytetaan_rivit) {
    echo "</table>";
  }

  if ($laskuri > 0) {
    echo "<br>$laskuri ", t("riviä").".<br>";
    echo "<br><input type='submit' value='".t("Päivitä tilausriveille oletuspaikka")."'>";
  }
  else {
    echo t("Kaikki tilausrivien tuotepaikat löytyvät")."!<br><br>";
    $tee = "";
  }
  echo "</form>";

}

if ($tee == "LISTAATAPAHTUMATILMANPAIKKAA" and !$_cli) {

  echo "<font class='message'>".t("Tapahtumat, joiden tuotepaikkoja ei löydy")."</font><hr>";

  $laskuri = 0;

  // haetaan kaikki tapahtumat, joilla ei ole tuotepaikkaa tai se ei kuulu mihinkään varastoon
  $query = "SELECT tapahtuma.tuoteno,
            varastopaikat.alkuhyllyalue,
            tapahtuma.tunnus,
            tapahtuma.laadittu,
            tapahtuma.laji,
            concat_ws('-', tapahtuma.hyllyalue,
                           tapahtuma.hyllynro,
                           tapahtuma.hyllyvali,
                           tapahtuma.hyllytaso) paikka
            FROM tapahtuma
            INNER JOIN tuote on (tuote.yhtio = tapahtuma.yhtio
              AND tuote.tuoteno   = tapahtuma.tuoteno
              AND tuote.ei_saldoa = '')
            LEFT OUTER JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio
              AND concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'), lpad(upper(varastopaikat.alkuhyllynro),  5, '0'))
                                  <= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'), lpad(upper(tapahtuma.hyllynro), 5, '0'))
              AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0'))
                                  >= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'), lpad(upper(tapahtuma.hyllynro), 5, '0')))
            WHERE tapahtuma.yhtio = '{$kukarow["yhtio"]}'
            AND tapahtuma.laji    not in ('epäkurantti')
            AND varastopaikat.tunnus IS NULL
            ORDER BY tapahtuma.laadittu";
  $tapahtumares = pupe_query($query);

  if (!isset($ei_nayteta_riveja) or trim($ei_nayteta_riveja) == '') {
    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Tuoteno")."</th>";
    echo "<th>".t("Laadittu")."</th>";
    echo "<th>".t("Laji")."</th>";
    echo "<th>".t("Varastopaikka")."</th>";
    echo "</tr>";
  }

  echo "<form method='POST'>";
  echo "<input type='hidden' name='tee' value='CLEANTAPAHTUMAT'>";

  while ($tapahtumarow = mysql_fetch_array($tapahtumares)) {
    echo "<input type='hidden' name='rivitunnus[]' value='$tapahtumarow[tunnus]'>";

    if (!isset($ei_nayteta_riveja) or trim($ei_nayteta_riveja) == '') {
      echo "<tr>";
      echo "<td><a href='tuote.php?tee=Z&tuoteno=".urlencode($tapahtumarow["tuoteno"])."'>$tapahtumarow[tuoteno]</a></td>";
      echo "<td>$tapahtumarow[laadittu]</td>";
      echo "<td>$tapahtumarow[laji]</td>";
      echo "<td>$tapahtumarow[paikka]</td>";
      echo "</tr>";
    }

    $laskuri++;
  }

  if (!isset($ei_nayteta_riveja) or trim($ei_nayteta_riveja) == '') {
    echo "</table>";
  }

  if ($laskuri > 0) {
    echo "<br>$laskuri ", t("riviä").".<br>";
    echo "<br><input type='submit' value='".t("Päivitä tapahtumille oletuspaikka")."'>";
  }
  else {
    echo t("Kaikki tapahtumien tuotepaikat löytyvät")."!<br><br>";
    $tee = "";
  }
  echo "</form>";

}

if ($tee == "" and !$_cli) {
  //Käyttöliittymä

  echo "<table><form name='piiri' method='post'>";
  echo "<input type='hidden' name='tee' value='LISTAA'>";

  echo "<tr><th>".t("Alkuvarastopaikka:")."</th>
      <td>", hyllyalue("ahyllyalue", ''), "
      <input type='text' size='6' name='ahyllynro'>
      <input type='text' size='6' name='ahyllyvali'>
      <input type='text' size='6' name='ahyllytaso'>
      </td></tr>";

  echo "<tr><th>".t("Loppuvarastopaikka:")."</th>
      <td>", hyllyalue("lhyllyalue", ''), "
      <input type='text' size='6' name='lhyllynro'>
      <input type='text' size='6' name='lhyllyvali'>
      <input type='text' size='6' name='lhyllytaso'>
      </td></tr>";

  echo "<tr>";
  echo "<th>".t("Näytä vain paikat joiden saldo on muuttunut ennen päivämäärää (pp-kk-vvvv)")."</th>";
  echo "<td>
          <input type='text' name='ppa' value='$ppa' size='3'>-
          <input type='text' name='kka' value='$kka' size='3'>-
          <input type='text' name='vva' value='$vva' size='6'>
        </td>";
  echo "</tr>";

  echo "<tr><th>".t("Valitse varasto").":</th>";
  echo "<td><select name='varasto'>
    <option value=''>".t("Näytä kaikki")."</option>
    <option value='EI'>".t("Näytä paikat jotka ei kuulu mihinkään varastoon")."</option>";

  $query  = "SELECT *
             FROM varastopaikat
             WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
             ORDER BY tyyppi, nimitys";
  $vares = pupe_query($query);

  while ($varow = mysql_fetch_array($vares)) {
    $sel='';
    if ($varow['tunnus']==$varasto) $sel = 'selected';

    echo "<option value='$varow[tunnus]' $sel>$varow[nimitys]</option>";
  }

  echo "</select></td></tr>";

  echo "<tr><th>".t("Näytä vain miinus-saldolliset")."</th><td><input type='checkbox' name='vainmiinus'></td>";
  echo "<tr><th>".t("Näytä myös miinus-saldolliset")."</th><td><input type='checkbox' name='miinus'></td>";
  echo "<tr><th>".t("Näytä myös plus-saldolliset")."</th><td><input type='checkbox' name='plus'></td>";

  echo "<tr><th>".t("Osasto")."</th><td>";

  // tehdään avainsana query
  $sresult = t_avainsana("OSASTO");

  echo "<select name='osasto'>";
  echo "<option value=''>".t("Näytä kaikki")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($osasto == $srow["selite"]) {
      $sel = "selected";
    }
    echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
  }
  echo "</select>";


  echo "</td></tr>
      <tr><th>".t("Tuoteryhmä")."</th><td>";

  //Tehdään osasto & tuoteryhmä pop-upit
  // tehdään avainsana query
  $sresult = t_avainsana("TRY");

  echo "<select name='tuoryh'>";
  echo "<option value=''>".t("Näytä kaikki")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($tuoryh == $srow["selite"]) {
      $sel = "selected";
    }
    echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
  }
  echo "</select></td><td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td></tr>";
  echo "</form>";

  echo "<form method='post'>";
  echo "<tr><td class='back'><br></tr>";
  echo "<input type='hidden' name='tee' value='LISTAAOLETUKSET'>";
  echo "<tr><th>".t("Listaa tuotteet, joilla on virheellisiä oletuspaikkoja")."</th>";
  echo "<td></td>";
  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td></tr>";
  echo "</form>";

  echo "<form method='post'>";
  echo "<tr><td class='back'><br></tr>";
  echo "<input type='hidden' name='tee' value='LISTAATUNTEMATTOMAT'>";
  echo "<tr><th>".t("Listaa tuotepaikat, joiden varastopaikka ei kuulu mihinkään varastoon")."</th>";
  echo "<td></td>";
  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td></tr>";
  echo "</form>";

  echo "<form method='post'>";
  echo "<tr><td class='back'><br></tr>";
  echo "<input type='hidden' name='tee' value='LISTAATUOTTEETTOMAT'>";
  echo "<tr><th>".t("Listaa tuotepaikat, joiden tuotetta ei löydy")."</th>";
  echo "<td></td>";
  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td></tr>";
  echo "</form>";

  echo "<form method='post'>";
  echo "<tr><td class='back'><br></tr>";
  echo "<input type='hidden' name='tee' value='LISTAAVIRHEELLISETRIVIT'>";
  echo "<tr><th>".t("Listaa tilausrivit, joiden tuotepaikkoja ei löydy")."</th>";
  echo "<td><input type='checkbox' name='ei_nayteta_riveja'> ", t("Älä näytä tilausrivejä ruudulla"), "</td>";
  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td></tr>";
  echo "</form>";

  echo "<form method'post'>";
  echo "<tr><td class='back'><br></tr>";
  echo "<input type='hidden' name='tee' value='LISTAATAPAHTUMATILMANPAIKKAA'>";
  echo "<tr><th>".t("Listaa tapahtumat, joiden tuotepaikkoja ei löydy")."</th>";
  echo "<td><input type='checkbox' name='ei_nayteta_riveja'> ", t("Älä näytä tapahtumia ruudulla"), "</td>";
  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td></tr>";
  echo "</form>";

  echo "</table>";
}

if (!$_cli) {
  require "inc/footer.inc";
}
