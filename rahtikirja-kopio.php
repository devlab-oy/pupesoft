<?php

if (isset($_REQUEST["komento"]) and $_REQUEST["komento"] == "PDF_RUUDULLE") {
  $nayta_pdf = 1;
}

require "inc/parametrit.inc";

$logistiikka_yhtio = '';
$logistiikka_yhtiolisa = '';

if (!isset($real_submit)) $real_submit = '';

if ($real_submit == '') $tee = '';

// js_openFormInNewWindow()-funkkarin juttuja
if (isset($nayta_pdf) and $tee == 'NAYTATILAUS') {
  $tee = "tulosta";
}

if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
  $logistiikka_yhtio = $konsernivarasto_yhtiot;
  $logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

  if ($lasku_yhtio != '') {
    $kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);

    $yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
  }
}
else {
  $logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
}

if (!isset($nayta_pdf)) echo "<font class='head'>".t("Rahtikirjakopio")."</font><hr>";

if ($yhtiorow['kerayserat'] == 'K' and $real_submit != "" and $tee == 'valitse' and $toimitustapa == "" and $lahto == "") {
  echo "<font class='error'>", t("Valitse toimitustapa tai lähtö"), "</font><br />";
  $real_submit = $tee = "";
}

if ($tee == 'erittelykopsu') {

  $query = "SELECT *
            FROM lahdot
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$lahto'";
  $lahtores = pupe_query($query);

  if (mysql_num_rows($lahtores) > 0) {

    $lahtorow = mysql_fetch_assoc($lahtores);

    $query = "SELECT *
              FROM toimitustapa
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$lahtorow[liitostunnus]'";
    $toitares = pupe_query($query);
    $toitarow = mysql_fetch_assoc($toitares);

    $query = "SELECT group_concat(tunnus) tilaukset
              FROM lasku
              WHERE yhtio              = '$kukarow[yhtio]'
              AND tila                 = 'L'
              AND alatila             != ''
              AND toimitustavan_lahto  = '$lahtorow[tunnus]'";
    $laskures = pupe_query($query);
    $laskurow = mysql_fetch_assoc($laskures);

    $otunnukset = $laskurow["tilaukset"];

    if ($otunnukset != "") {
      require "tilauskasittely/rahtikirja_erittely_pdf.inc";

      echo t("Rahtikirjaerittely tulostuu")."...<br><br>";
    }
  }
  else {
    echo t("VIRHE: Lähtöä ei löydy")."!";
  }

  $tee = "";
}

if ($tee == 'tulosta' and (!isset($rtunnukset) or count($rtunnukset) == 0)) {
  if (!isset($nayta_pdf)) echo "<font class='error'>", t("Et valinnut yhtään rahtikirjaa"), "!</font><br>";
  $tee = "";
}

if ($tee == 'tulosta') {

  // rahtikirjojen tulostus vaatii seuraavat muuttujat:
  // $toimitustapa_varasto  toimitustavan selite!!!!varastopaikan tunnus
  // $tee            tässä pitää olla teksti tulosta

  $loopattavat_tunnukset = array();
  $pakkaustieto_rahtikirjanro_array = array();
  $tultiin = '';

  if ($yksittainen == "ON") {

    if (isset($pakkaustieto_rnro[$rtunnukset[0]])) {
      $querylisa = " and otsikkonro in ($rtunnukset[0]) ";
      $pakkaustieto_rahtikirjanro_array[] = $pakkaustieto_rnro[$rtunnukset[0]];
      $tultiin = 'koonti_eratulostus_pakkaustiedot';
    }
    else {
      $querylisa = " and rahtikirjanro  = '$rtunnukset[0]' ";
      unset($tultiin);
    }

    //Tässä on haettava tulostettavan tilauksen tiedot
    $query = "SELECT toimitustapa, tulostuspaikka, group_concat(otsikkonro) otsikkonro
              FROM rahtikirjat
              WHERE yhtio = '$kukarow[yhtio]'
              $querylisa
              GROUP BY 1,2
              LIMIT 1";
    $ores  = pupe_query($query);
    $rrow  = mysql_fetch_array($ores);

    $toimitustapa  = $rrow["toimitustapa"];
    $varasto    = $rrow["tulostuspaikka"];
    $sel_ltun    = explode(",", $rrow["otsikkonro"]);
    $loopattavat_tunnukset[] = $sel_ltun;
  }
  else {

    $osuko = '';

    foreach ($rtunnukset as $null => $val) {
      if (isset($pakkaustieto_rnro[$val])) {
        $osuko = 'jep';
        break;
      }
    }

    if ($osuko == 'jep') {
      $tultiin = 'koonti_eratulostus_pakkaustiedot';

      foreach ($rtunnukset as $null => $val) {
        $sel_ltun = explode(",", $val);
        $pakkaustieto_rahtikirjanro_array[] = $pakkaustieto_rnro[$val];
        $loopattavat_tunnukset[] = $sel_ltun;
      }
    }
    else {
      //Tässä on haettava tulostettavien tilausten tunnukset
      $query = "SELECT group_concat(otsikkonro) otsikkonro
                FROM rahtikirjat
                WHERE yhtio       = '$kukarow[yhtio]'
                and rahtikirjanro in ('".implode("','", $rtunnukset)."')";
      $ores  = pupe_query($query);
      $rrow  = mysql_fetch_array($ores);
      unset($tultiin);

      $sel_ltun = explode(",", $rrow["otsikkonro"]);
      $loopattavat_tunnukset[] = $sel_ltun;
    }
  }

  $toimitustapa_varasto = $toimitustapa."!!!!".$kukarow['yhtio']."!!!!".$varasto;
  $tee          = "tulosta";

  foreach ($loopattavat_tunnukset as $null => $sel_ltun) {
    if (isset($pakkaustieto_rahtikirjanro_array[$null])) {
      $pakkaustieto_rahtikirjanro = $pakkaustieto_rahtikirjanro_array[$null];
    }
    else {
      $pakkaustieto_rahtikirjanro = '';
    }

    require "rahtikirja-tulostus.php";
  }


  if (!isset($nayta_pdf)) {
    echo "<br>";
    $tee = '';
  }
  else {
    $tee = 'SKIPPAA';
  }
}

if ($tee == 'valitse' and $real_submit != '') {

  if ($toimitustapa != '') {
    list($toimitustapa, $yhtio) = explode("!!!!", $toimitustapa);
    $kukarow['yhtio'] = $yhtio;
  }

  if ($otunnus == "") {

    $tunnuksetlisa = "";

    if (isset($lahto) and trim($lahto) != "" and $yhtiorow['kerayserat'] == 'K') {

      $query = "SELECT toimitustapa, GROUP_CONCAT(tunnus) tunnukset
                FROM lasku
                WHERE yhtio             = '{$kukarow['yhtio']}'
                AND toimitustavan_lahto = '{$lahto}'
                GROUP BY 1";
      $lahdot_lasku_chk_res = pupe_query($query);
      $lahdot_lasku_chk_row = mysql_fetch_assoc($lahdot_lasku_chk_res);

      if ($lahdot_lasku_chk_row['tunnukset'] != "") {
        $tunnuksetlisa = "AND otsikkonro IN ({$lahdot_lasku_chk_row['tunnukset']})";
        $toimitustapa = $lahdot_lasku_chk_row['toimitustapa'];
      }
    }

    $toimitustapalisa = $toimitustapa != "" ? "and toimitustapa  = '{$toimitustapa}'" : "";

    $query = "SELECT yhtio, rahtikirjanro, sum(kilot) paino
              from rahtikirjat
              where yhtio        = '$kukarow[yhtio]'
              and tulostuspaikka = '$varasto'
              {$toimitustapalisa}
              and tulostettu     > '$vv-$kk-$pp 00:00:00'
              and tulostettu     < '$vv-$kk-$pp 23:59:59'
              {$tunnuksetlisa}
              GROUP BY rahtikirjanro";
  }
  else {
    $query = "SELECT rahtikirjanro
              from rahtikirjat
              where otsikkonro = '$otunnus'
                    and yhtio  = '$kukarow[yhtio]'";
    $res = pupe_query($query);
    $rahtikirjanro = mysql_fetch_array($res);

    $query = "SELECT rahtikirjanro, sum(kilot) paino, yhtio
              from rahtikirjat
              where yhtio        = '$kukarow[yhtio]'
              and rahtikirjanro  = '$rahtikirjanro[rahtikirjanro]'
              and tulostettu    != '0000-00-00 00:00:00'
              GROUP BY rahtikirjanro";
    $toimitustapa   = "";
    $varasto     = "";
  }
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {

    echo "<font class='message'>";

    echo $toimitustapa != "" ? $toimitustapa.": " : "";

    echo "{$vv}-{$kk}-{$pp}<br><br>".t("Yhtään rahtikirjaa ei löytynyt")."!</font><br><br>";
    $tee = "";
  }
  else {
    echo "<form action='rahtikirja-kopio.php' method='post'>";
    echo "<input type='hidden' name='tee' value='tulosta'>";
    echo "<input type='hidden' name='real_submit' value='joo'>";
    echo "<input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>";
    echo "<input type='hidden' name='pp' value='$pp'>";
    echo "<input type='hidden' name='kk' value='$kk'>";
    echo "<input type='hidden' name='vv' value='$vv'>";

    if ($otunnus == "") {
      echo "<font class='message'>";
      echo $toimitustapa != "" ? $toimitustapa.": " : "";
      echo "{$vv}-{$kk}-{$pp}</font><br><br>";
      echo "<input type='hidden' name='varasto' value='$varasto'>";
      echo "<input type='hidden' name='toimitustapa' value='$toimitustapa'>";
    }
    else {
      echo "<input type='hidden' name='yksittainen' value='ON'>";
    }

    echo "<table>";
    echo "<tr>";
    if ($logistiikka_yhtio != '') echo "<th>", t("Yhtiö"), "</th>";
    echo "<th>".t("Rahtikirjanro")."</th>";
    echo "<th>".t("Tilausnumero")."</th>";
    echo "<th>".t("Tulostettu")."</th>";
    echo "<th>".t("Asiakas")."</th>";
    echo "<th>".t("Osoite")."</th>";
    echo "<th>".t("Postino")."</th>";
    echo "<th>".t("Paino KG")."</th>";
    echo "<th>".t("Valitse")."</th>";
    echo "</tr>";

    while ($row = mysql_fetch_array($result)) {
      if ($row['rahtikirjanro'] != '') {

        $query = "SELECT otsikkonro, tulostettu, pakkaustieto_tunnukset from rahtikirjat where yhtio='$kukarow[yhtio]' and rahtikirjanro='$row[rahtikirjanro]' limit 1";
        $ores  = pupe_query($query);
        $rrow  = mysql_fetch_array($ores);

        if (trim($rrow['pakkaustieto_tunnukset']) != '') {
          $wherelisa = " and tunnus in ($rrow[pakkaustieto_tunnukset]) ";
        }
        else {
          $wherelisa = " and tunnus = '$rrow[otsikkonro]' ";
        }

        $query = "SELECT ytunnus, nimi, nimitark, toim_osoite, toim_postino, toim_postitp, tunnus from lasku where yhtio='$kukarow[yhtio]' $wherelisa ";
        $ores  = pupe_query($query);
        $orow  = mysql_fetch_array($ores);

        echo "<tr>";
        if ($logistiikka_yhtio != '') echo "<td>$row[yhtio]</td>";
        echo "<td>$row[rahtikirjanro]</td>";
        echo "<td>$orow[tunnus]</td>";
        echo "<td>$rrow[tulostettu]</td>";
        echo "<td>$orow[nimi] $orow[nimitark]</td>";
        echo "<td>$orow[toim_osoite]</td>";
        echo "<td>$orow[toim_postino] $orow[toim_postitp]</td>";
        echo "<td style='text-align: right;'>" . round($row['paino'], 2) . "</td>";

        if (trim($rrow['pakkaustieto_tunnukset']) != '') {
          echo "<td><input type='checkbox' name='rtunnukset[]' value='$rrow[pakkaustieto_tunnukset]' checked><input type='hidden' name='pakkaustieto_rnro[$rrow[pakkaustieto_tunnukset]]' value='$row[rahtikirjanro]'></td>";
        }
        else {
          echo "<td><input type='checkbox' name='rtunnukset[]' value='$row[rahtikirjanro]' checked></td>";
        }
        echo "</tr>";
      }
    }

    echo "</table><br>";

    $query = "SELECT *
              FROM kirjoittimet
              WHERE yhtio='$kukarow[yhtio]'
              AND komento != 'EDI'
              ORDER BY kirjoitin";
    $kirre = pupe_query($query);

    echo t("Rahtikirjatulostin"), "<br>";
    echo "<select name='komento'>";
    echo "<option value=''>".t("Oletustulostimelle")."</option>";

    while ($kirrow = mysql_fetch_array($kirre)) {
      echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
    }

    echo "<option value='PDF_RUUDULLE'>".t('PDF Ruudulle')."</option>";
    echo "</select><br><br>";

    echo t("Tulosta osoitelaput"), "<br>";

    mysql_data_seek($kirre, 0);

    echo "<td>";
    echo "<select name='valittu_rakiroslapp_tulostin'>";
    echo "<option value=''>", t("Ei tulosteta"), "</option>";

    while ($kirrow = mysql_fetch_array($kirre)) {
      echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
    }

    echo "</select><br><br>";

    echo "<table><th>",t("Tulosta toimitussanomat rahtikirjakopioista"),"</th>";
    echo "<th><input type='checkbox' name='tulosta_desadv' value='JOO' ></th></table>";
    echo "<br>";

    echo "<br><input type='submit' value='".t("Tulosta valitut")."'>";
    echo "</form>";
  }
}

if ($tee == '') {
  // mitä etsitään
  if (!isset($vv)) $vv = date("Y");
  if (!isset($kk)) $kk = date("m");
  if (!isset($pp)) $pp = date("d");

  $onchange = $yhtiorow['kerayserat'] == 'K' ? "onchange='submit();'" : "";

  echo "<br><form action='rahtikirja-kopio.php' method='post'>";
  echo "<input type='hidden' name='tee' value='valitse'>";

  echo t("Tulosta yksittäinen rahtikirjakopio").":";
  echo "<table><tr>
    <th>".t("Syötä tilausnumero").":</th>
    <td><input type='text' name='otunnus' size='15'></td>
    </tr>";
  echo "</table><br>";

  echo t("Tulosta kopiot eräajosta").":";
  echo "<table><tr>
    <th>".t("Syötä päivämäärä (pp-kk-vvvv)").":</th>
    <td><input type='text' name='pp' value='$pp' size='3'>
    <input type='text' name='kk' value='$kk' size='3'>
    <input type='text' name='vv' value='$vv' size='5'>";

  if ($yhtiorow['kerayserat'] == 'K') echo "<input type='submit' name='dummy_submit' value='", t("Päivitä lähdöt"), "' />";

  echo "</td></tr>";

  $query  = "SELECT * FROM toimitustapa WHERE nouto='' and $logistiikka_yhtiolisa order by jarjestys, selite";
  $result = pupe_query($query);

  echo "<tr><th>".t("Valitse toimitustapa").":</th>";
  echo "<td><select name='toimitustapa' {$onchange}>";

  if ($yhtiorow['kerayserat'] == 'K') echo "<option value=''>", t("Ei valintaa"), "</option>";

  $toimitustapa_tunnus = '';

  while ($row = mysql_fetch_array($result)) {

    $preg_str = "!!!!{$row['yhtio']}";

    if (preg_replace("/{$preg_str}/", "", $toimitustapa) == $row['selite']) {
      $sel = " selected ";
      $toimitustapa_tunnus = $row['tunnus'];
    }
    else {
      $sel = "";
    }

    echo "<option value='$row[selite]!!!!$row[yhtio]' $sel>".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV");
    if ($logistiikka_yhtio != '') {
      echo " ($row[yhtio])";
    }

    echo "</option>";
  }

  echo "</select></td></tr>";

  // haetaan kaikki varastot
  $query  = "SELECT tunnus, nimitys, yhtio
             FROM varastopaikat
             WHERE $logistiikka_yhtiolisa AND tyyppi != 'P'
             ORDER BY yhtio, tyyppi, nimitys";
  $result = pupe_query($query);

  // jos löytyy enemmän kuin yksi, tehdään varasto popup..
  if (mysql_num_rows($result)>1) {
    echo "<tr><th>".t("Valitse varasto").":</th>";
    echo "<td><select name='varasto' {$onchange}>";

    while ($row = mysql_fetch_array($result)) {

      if (!isset($varasto)) $varasto = $row['tunnus'];

      if ($varasto == $row['tunnus']) {
        $sel = " selected ";
      }
      else {
        $sel = "";
      }

      echo "<option value='$row[tunnus]' $sel>$row[nimitys]";

      if ($logistiikka_yhtio != '') {
        echo " ($row[yhtio])";
      }

      echo "</option>";
    }

    echo "</select></td></tr>";
  }
  else {
    $row = mysql_fetch_array($result);
    echo "<input type='hidden' name='varasto' value='$row[tunnus]'>";
    echo "<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>";
  }

  if ($yhtiorow['kerayserat'] == 'K') {

    echo "<tr>";
    echo "<th>", t("Valitse lähtö"), "</th>";
    echo "<td>";
    echo "<select name='lahto'>";
    echo "<option value=''>", t("Kaikki"), "</option>";

    $toimitustapalisa = "";

    if (isset($toimitustapa_tunnus) and trim($toimitustapa_tunnus) != "") {
      $toimitustapalisa = "AND lahdot.liitostunnus = '{$toimitustapa_tunnus}'";
    }

    $query = "SELECT lahdot.tunnus, lahdot.lahdon_kellonaika, tt.selite, count(lasku.tunnus) AS cnt
              FROM lahdot
              JOIN lasku ON (lasku.yhtio = lahdot.yhtio AND lasku.toimitustavan_lahto = lahdot.tunnus)
              JOIN toimitustapa AS tt ON (tt.yhtio = lasku.yhtio
                AND tt.tunnus          = lahdot.liitostunnus
                AND tt.selite          = lasku.toimitustapa
                AND tt.tulostustapa   != 'X'
                AND tt.rahtikirja     != 'rahtikirja_tyhja.inc')
              JOIN rahtikirjat AS r ON (r.yhtio = lasku.yhtio
                AND r.tulostuspaikka   = '{$varasto}'
                AND r.toimitustapa     = lasku.toimitustapa
                AND r.tulostettu       > '{$vv}-{$kk}-{$pp} 00:00:00'
                AND r.tulostettu       < '{$vv}-{$kk}-{$pp} 23:59:59'
                AND r.otsikkonro       = lasku.tunnus)
              WHERE lahdot.yhtio       = '{$kukarow['yhtio']}'
              AND lahdot.pvm           = '{$vv}-{$kk}-{$pp}'
              AND lahdot.varasto       = '{$varasto}'
              AND lahdot.aktiivi       = 'S'
              AND lahdot.liitostunnus != 0
              {$toimitustapalisa}
              GROUP BY 1,2,3
              HAVING cnt > 0
              ORDER BY lahdot.lahdon_kellonaika, tt.selite";
    $lahdot_res = pupe_query($query);

    while ($lahdot_row = mysql_fetch_assoc($lahdot_res)) {

      $sel = (isset($lahto) and $lahto == $lahdot_row['tunnus']) ? "selected" : "";

      echo "<option value='{$lahdot_row['tunnus']}' {$sel}>{$lahdot_row['tunnus']} {$lahdot_row['lahdon_kellonaika']} {$lahdot_row['selite']}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }

  echo "</table><br>";
  echo "<input type='submit' name='real_submit' value='".t("Tulosta")."'>";
  echo "</form><br><br><hr>";

  if ($yhtiorow['kerayserat'] == 'K') {
    echo t("Tulosta kopiot rahtikirjaerittelystä").":";
    echo "<br><form action='rahtikirja-kopio.php' method='post'>";
    echo "<input type='hidden' name='tee' value='erittelykopsu'>";
    echo "<input type='hidden' name='real_submit' value='joo'>";
    echo "<table>";

    $query  = "SELECT lahdot.tunnus, lahdot.lahdon_kellonaika, toimitustapa.selite
               FROM lahdot
               JOIN toimitustapa ON (lahdot.yhtio=toimitustapa.yhtio AND lahdot.liitostunnus=toimitustapa.tunnus AND toimitustapa.erittely != '')
               JOIN lasku ON (lasku.yhtio = lahdot.yhtio AND lasku.toimitustavan_lahto = lahdot.tunnus)
               JOIN rahtikirjat AS r ON (r.yhtio = lasku.yhtio
                 AND r.tulostuspaikka         = '{$varasto}'
                 AND r.toimitustapa           = lasku.toimitustapa
                 AND r.tulostettu             > '{$vv}-{$kk}-{$pp} 00:00:00'
                 AND r.tulostettu             < '{$vv}-{$kk}-{$pp} 23:59:59'
                 AND r.otsikkonro             = lasku.tunnus)
               WHERE lahdot.yhtio             = '$kukarow[yhtio]'
               AND lahdot.varasto             = '{$varasto}'
               AND lahdot.aktiivi             = 'S'
               AND lahdot.pvm                 = '{$vv}-{$kk}-{$pp}'
               AND toimitustapa.tulostustapa != 'X'
               {$toimitustapalisa}
               GROUP BY 1,2,3
               ORDER BY lahdot.lahdon_kellonaika, toimitustapa.selite";
    $result = pupe_query($query);

    echo "<tr><th>".t("Valitse lähto").":</th>";
    echo "<td><select name='lahto'>";

    while ($row = mysql_fetch_array($result)) {
      if ($lahto == $row['tunnus']) $sel = " selected ";
      else $sel = "";

      echo "<option value='$row[tunnus]' $sel>$row[tunnus] $row[lahdon_kellonaika] $row[selite]</option>";
    }

    echo "</select></td></tr>";

    $query = "SELECT *
              FROM kirjoittimet
              WHERE yhtio  = '$kukarow[yhtio]'
              AND komento != 'EDI'
              ORDER BY kirjoitin";
    $kirre = pupe_query($query);

    echo "<tr><th>".t("Tulostin"), "</th>";
    echo "<td><select name='kirjoitin'>";

    while ($kirrow = mysql_fetch_array($kirre)) {
      if ($kirjoitin == $kirrow['komento']) $sel = " selected ";
      else $sel = "";

      echo "<option value='$kirrow[komento]' $sel>$kirrow[kirjoitin]</option>";
    }

    echo "</select></td></tr>";
    echo "</table><br>";
    echo "<input type='submit' value='".t("Tulosta")."'>";
    echo "</form>";
  }
}

require "inc/footer.inc";
