<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

if (file_exists("../inc/parametrit.inc")) {
  require "../inc/parametrit.inc";
}

// ajaxin kautta haetaan kommentti textareaan
// t‰ytyy olla parametri no_head = yes
if ($_GET['tee'] == 'hae_kommentti') {
  $tun = (int) $_GET['tun'];
  $query = "SELECT comments
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$tun'";
  $comm_res = pupe_query($query);
  $comm_row = mysql_fetch_assoc($comm_res);

  echo $comm_row['comments'];
  exit;
}

if ($_GET['tee'] == 'kommentti') {
  if ($_GET['tilaus'] != '') {

    $useslave = 0;
    require '../inc/connect.inc';

    $tilaus = (int) $_GET['tilaus'];
    $kommentti = mysql_real_escape_string($_GET['kommentti']);

    $query = "UPDATE lasku SET
              comments    = '$kommentti'
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$tilaus'";
    $comm_ins_res = pupe_query($query);

    if ($kommentti == '') {
      echo "<br><font class='message'>", t("Tyhjensit kommentin ostotilaukselta"), " $tilaus</font><br>";
    }
    else {
      echo "<br><font class='message'>", t("Lis‰sit kommentin"), " $kommentti ", t("ostotilaukselle"), " $tilaus</font><br>";
    }
    exit;
  }
}

if ($tee == 'NAYTATILAUS') {
  require "raportit/naytatilaus.inc";
  require "inc/footer.inc";
  die();
}

js_popup();

echo "<font class='head'>".t("Ostotilausten seuranta")."</font><hr>";

if ($toimittajahaku != '') {
  $ytunnus = $toimittajahaku;
  $pvm = array('ppa' => $ppa, 'kka' => $kka, 'vva' => $vva, 'ppl' => $ppl, 'kkl' => $kkl, 'vvl' => $vvl, 'toimittaja' => $toimittajahaku);
  $muutparametrit = urlencode(serialize($pvm));
  require '../inc/kevyt_toimittajahaku.inc';
}

if ($muutparametrit != '') {
  $pvm = unserialize(urldecode($muutparametrit));
  $ppa = $pvm['ppa'];
  $kka = $pvm['kka'];
  $vva = $pvm['vva'];
  $ppl = $pvm['ppl'];
  $kkl = $pvm['kkl'];
  $vvl = $pvm['vvl'];
  $toimittajahaku = $pvm['toimittaja'];
}

if ($toimittajaid == '' and $toimittajahaku != '') {
  $tee = '';
}

if ($toimittajaid != '') {
  $toimittajahaku = $ytunnus;
}

// Tarvittavat p‰iv‰m‰‰r‰t
if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($kkl)) $kkl = date("m");
if (!isset($vvl)) $vvl = date("Y");
if (!isset($ppl)) $ppl = date("d");

echo "<table>";
echo "<form method='post' autocomplete='off' name='hakuform' id='hakuform'>";

echo "<tr><th>", t("Toimittaja"), "</th><td colspan='2' nowrap><input type='text' name='toimittajahaku' value='$toimittajahaku'></td></tr>";

echo "<tr><th>", t("P‰iv‰m‰‰r‰v‰li"), " (", t("pp-kk-vvvv"), ")</th>";

echo "<td><input type='text' name='ppa' value='$ppa' size='3'>
<input type='text' name='kka' value='$kka' size='3'>
<input type='text' name='vva' value='$vva' size='5'></td>
<td><input type='text' name='ppl' value='$ppl' size='3'>
<input type='text' name='kkl' value='$kkl' size='3'>
<input type='text' name='vvl' value='$vvl' size='5'></td>";

echo "<td class='back'><input type='submit' name='submit' id='submit' value='", t("Luo raportti"), "'></td></tr>";
echo "<input type='hidden' name='tee' id='tee' value='aja'>";
echo "</form></table>";
echo "<br/>";
echo "<img src='".$palvelin2."pics/lullacons/bot-plain-green.png'/> = ", t("Saapumisen virallinen varastonarvo laskettu"), "<br/>";
echo "<img src='".$palvelin2."pics/lullacons/bot-plain-yellow.png'/> = ", t("Saapumisen tuotteita viety varastoon"), "<br/>";
echo "<img src='".$palvelin2."pics/lullacons/bot-plain-red.png'/> = ", t("Saapumisen tuotteita ei ole viety varastoon tai saapumiseen ei ole liitetty yht‰‰n rivi‰"), "<br/>";
echo "<img src='".$palvelin2."pics/lullacons/bot-plain-white.png'/> = ", t("Ostotilauksen tuotteita ei ole liitetty mihink‰‰n saapumiseen"), "<br/>";
echo "<img src='".$palvelin2."pics/lullacons/bot-plain-blue.png'/> = ", t("Toimittajan vaihto-omaisuuslasku, jota ei ole liitetty saapumiseen"), "<br/>";

if (!is_numeric($ppa) or !is_numeric($kka) or !is_numeric($vva) or !is_numeric($ppl) or !is_numeric($kkl) or !is_numeric($vvl)) {
  echo "<br/><font class='error'>", t("Virheellinen p‰iv‰m‰‰r‰"), "!</font>";
  $tee = '';
}

echo "<div id='tallenna_message'></div>";

if ($tee == 'aja') {

  $ppa = sprintf('%02d', (int) $ppa);
  $kka = sprintf('%02d', (int) $kka);
  $vva = sprintf('%04d', (int) $vva);
  $ppl = sprintf('%02d', (int) $ppl);
  $kkl = sprintf('%02d', (int) $kkl);
  $vvl = sprintf('%04d', (int) $vvl);

  if ($toimittajaid != '') {
    $toimittajaid = mysql_real_escape_string($toimittajaid);
    $toimittajalisa =  " and toimi.tunnus = '$toimittajaid' ";
  }

  $query = "SELECT nimi, ytunnus, tunnus
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            $toimittajalisa";
  $res = pupe_query($query);

  echo "<table>";

  $vaihtuuko_toimittaja = '';
  $tunnukset = array();

  $query_ale_lisa = generoi_alekentta('O');

  while ($toimittajarow = mysql_fetch_assoc($res)) {

    $query = "SELECT count(*) riveja,
              sum(if(tilausrivi.kpl != 0, 1, 0)) riveja_varastossa,
              sum(tilausrivi.kpl+tilausrivi.varattu) kpl,
              sum(tilausrivi.kpl) kpl_varastossa,
              sum((tilausrivi.kpl+tilausrivi.varattu)*tilausrivi.hinta*{$query_ale_lisa}) arvo,
              lasku.tunnus ltunnus,
              lasku.h1time,
              sum(tuote.tuotemassa*(tilausrivi.varattu+tilausrivi.kpl)) massa,
              sum(if(tuotemassa!=0, varattu+kpl, 0)) kplok,
              lasku.comments,
              lasku.valkoodi
              FROM lasku
              JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'O')
              JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno)
              WHERE lasku.yhtio      = '{$kukarow['yhtio']}'
              AND lasku.tila         = 'O'
              AND lasku.liitostunnus = '{$toimittajarow['tunnus']}'
              and lasku.h1time >='{$vva}-{$kka}-{$ppa} 00:00:00'
              and lasku.h1time <='{$vvl}-{$kkl}-{$ppl} 23:59:59'
              GROUP BY lasku.tunnus
              ORDER BY lasku.ytunnus, lasku.h1time";
    $tilrivi_res = pupe_query($query);

    if (mysql_num_rows($tilrivi_res) > 0) {

      $ed_tunn              = "";
      $tilrivi_laskuri      = 0;
      $yht_arvo             = 0;
      $yht_eturahti         = array();
      $yht_kpl              = 0;
      $yht_kulu_summa       = array();
      $yht_paino            = 0;
      $yht_rivit            = 0;
      $yht_tavara_summa     = array();
      $yht_varastossa_kpl   = 0;
      $yht_varastossa_rivit = 0;

      while ($tilrivi_row = mysql_fetch_assoc($tilrivi_res)) {

        $tilrivi_laskuri++;

        // keikka
        $query = "SELECT distinct lasku.laskunro, lasku.rahti_etu, lasku.tunnus, lasku.mapvm
                  FROM tilausrivi
                  JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus AND lasku.tila = 'K' AND lasku.vanhatunnus = 0 AND lasku.liitostunnus = '$toimittajarow[tunnus]')
                  WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                  and tilausrivi.tyyppi  = 'O'
                  and tilausrivi.otunnus = '$tilrivi_row[ltunnus]'";
        $result = pupe_query($query);

        $i = 1;
        $x = mysql_num_rows($result) != 0 ? mysql_num_rows($result) : 1;

        $rows = array();

        while ($keikkarow = mysql_fetch_assoc($result)) {
          $rows[] = $keikkarow;
        }

        if ($tilrivi_laskuri == mysql_num_rows($tilrivi_res)) {
          $query = "SELECT distinct lasku.laskunro, lasku.rahti_etu, lasku.tunnus
                    FROM lasku
                    LEFT JOIN tilausrivi USE INDEX (uusiotunnus_index) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O')
                    WHERE lasku.yhtio  = '$kukarow[yhtio]' and
                    lasku.tila         = 'K' and
                    lasku.vanhatunnus  = 0 and
                    lasku.liitostunnus = '$toimittajarow[tunnus]' and
                    tilausrivi.tunnus IS NULL
                    ORDER BY laskunro";
          $ei_tilriveja_res = pupe_query($query);

          $xx += mysql_num_rows($ei_tilriveja_res);

          while ($ei_tilriveja_row = mysql_fetch_assoc($ei_tilriveja_res)) {
            $rows[] = $ei_tilriveja_row;
          }
        }

        if ($vaihtuuko_toimittaja != $toimittajarow['tunnus']) {
          echo "<tr>";
          echo "<td class='back' colspan='17' style='vertical-align: top;' nowrap><br/><font class='head'>{$toimittajarow['ytunnus']} {$toimittajarow['nimi']}</font><br/></td>";
          echo "</tr>";

          echo "<tr>";
          echo "<th>", t("Tilno"), "</th>";
          echo "<th>", t("Tilvko"), "</th>";
          echo "<th>", t("Tilpvm"), "</th>";
          echo "<th>", t("Paino"), "</th>";
          echo "<th>", t("M‰‰r‰"), "</th>";
          echo "<th>", t("Rivim‰‰r‰"), "</th>";
          echo "<th>", t("Tilauksen"), "<br/>", t("arvo"), "<br/>$tilrivi_row[valkoodi]</th>";
          echo "<th>", t("Saapuminen"), "</th>";
          echo "<th>", t("Tavaralaskun"), "<br/>", t("luontiaika"), "</th>";
          echo "<th>", t("Summa"), "<br/>$yhtiorow[valkoodi]</th>";
          echo "<th>", t("Viesti"), "</th>";
          echo "<th>", t("Kululaskun"), "<br/>", t("luontiaika"), "</th>";
          echo "<th>", t("Summa"), "<br/>$yhtiorow[valkoodi]</th>";
          echo "<th>", t("Viesti"), "</th>";
          echo "<th>", t("Eturahti"), "<br/>$yhtiorow[valkoodi]</th>";
          echo "<th>", t("Kulu %"), "</th>";
          echo "<th>", t("Saldopvm"), "</th>";
          echo "<th>", t("Valmispvm"), "</th>";
          echo "<th>&nbsp;</th>";
          echo "</tr>";
        }

        echo "<tr class='aktiivi'>";

        echo "<td rowspan='$x' style='vertical-align: top;'>";

        $varastossa_riveja = '';
        $varastossa_kpl = '';

        if ($ed_tunn != $tilrivi_row['ltunnus']) {

          foreach ($rows as $keikkarow) {
            if ($keikkarow['mapvm'] == '0000-00-00') {
              $varastossa_riveja = $tilrivi_row['riveja_varastossa']."/";
              $varastossa_kpl = (float) $tilrivi_row['kpl_varastossa']."/";
            }
          }

          echo "<div id='div_$tilrivi_row[ltunnus]' class='popup'>";
          echo $tilrivi_row['comments'];
          echo "</div>";
          echo "<a id='$tilrivi_row[ltunnus]' class='tooltip' href='asiakkaantilaukset.php?tee=NAYTATILAUS&toim=OSTO&tunnus=$tilrivi_row[ltunnus]&lopetus=".$palvelin2."raportit/ostotilausten_seuranta.php////tee=aja//kka=$kka//vva=$vva//ppa=$ppa//kkl=$kkl//vvl=$vvl//ppl=$ppl//toimittajahaku=$toimittajahaku'>$tilrivi_row[ltunnus]</a>";

          $tunnukset[] = $tilrivi_row['ltunnus'];
        }
        else {
          echo "&nbsp;";
        }
        echo "</td>";

        echo "<td style='vertical-align: top;' rowspan='$x'>".date("W", strtotime($tilrivi_row['h1time']))."/".substr($tilrivi_row['h1time'], 2, 2)."</td>";
        echo "<td style='vertical-align: top;' rowspan='$x'>".tv1dateconv($tilrivi_row['h1time'])."</td>";

        $osumapros = '';

        if (round($tilrivi_row["kplok"] / $tilrivi_row["kpl"] * 100, 2) != 100) {
          $osumapros = "~";
        }

        echo "<td style='vertical-align: top; text-align: right;' rowspan='$x'>";
        if ($tilrivi_row['massa'] != 0) {
          echo "$osumapros".sprintf('%.02f', $tilrivi_row['massa']);
          $yht_paino += $tilrivi_row['massa'];
        }
        echo "</td>";

        echo "<td style='vertical-align: top; text-align: right;' rowspan='$x' nowrap>$varastossa_kpl".(float) $tilrivi_row['kpl']."</td>";
        echo "<td style='vertical-align: top; text-align: right;' rowspan='$x' nowrap>$varastossa_riveja$tilrivi_row[riveja]</td>";
        echo "<td style='vertical-align: top; text-align: right;' nowrap rowspan='$x'>".sprintf('%.02f', $tilrivi_row['arvo'])."</td>";

        $yht_varastossa_kpl += substr($varastossa_kpl, 0, -1);
        $yht_kpl += $tilrivi_row['kpl'];
        $yht_varastossa_rivit += substr($varastossa_riveja, 0, -1);
        $yht_rivit += $tilrivi_row['riveja'];
        $yht_arvo += $tilrivi_row['arvo'];

        foreach ($rows as $keikkarow) {

          $kululaskusummat = 0;
          $tavaralaskusummat = 0;

          if ($i > 1 and $i > $x) {
            echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
          }

          echo "<td style='vertical-align: top;'><a href='asiakkaantilaukset.php?tee=NAYTATILAUS&toim=OSTO&tunnus=$keikkarow[tunnus]&lopetus=".$palvelin2."raportit/ostotilausten_seuranta.php////tee=aja//kka=$kka//vva=$vva//ppa=$ppa//kkl=$kkl//vvl=$vvl//ppl=$ppl//toimittajahaku=$toimittajahaku'>$keikkarow[laskunro]</a></td>";

          $query  = "SELECT liitosotsikko.arvo * if(ostoreskontran_lasku.maksu_kurssi <> 0, ostoreskontran_lasku.maksu_kurssi, ostoreskontran_lasku.vienti_kurssi) summa_euroissa,
                     ostoreskontran_lasku.luontiaika,
                     concat(ostoreskontran_lasku.laskunro, ' ', ostoreskontran_lasku.viesti) numero,
                     ostoreskontran_lasku.tunnus,
                     liitosotsikko.tunnus litunn
                     FROM lasku liitosotsikko
                     JOIN lasku ostoreskontran_lasku ON (ostoreskontran_lasku.yhtio=liitosotsikko.yhtio and ostoreskontran_lasku.tunnus=liitosotsikko.vanhatunnus)
                     WHERE liitosotsikko.yhtio       = '$kukarow[yhtio]'
                     AND liitosotsikko.laskunro      = '$keikkarow[laskunro]'
                     AND liitosotsikko.vanhatunnus   <> 0
                     AND liitosotsikko.tila          = 'K'
                     AND ostoreskontran_lasku.vienti in ('C', 'J', 'F', 'K', 'I', 'L')"; // tavaralaskut
          $ostolaskures = pupe_query($query);

          if (mysql_num_rows($ostolaskures) > 0) {
            echo "<td style='vertical-align: top;' nowrap>";
            while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
              echo tv1dateconv($ostolaskurow['luontiaika'])."<br>";
            }
            echo "</td>";

            mysql_data_seek($ostolaskures, 0);

            echo "<td style='vertical-align: top; text-align: right;' nowrap>";
            while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
              echo "<a href='".$palvelin2."muutosite.php?tee=E&tunnus=$ostolaskurow[tunnus]&lopetus=".$palvelin2."raportit/ostotilausten_seuranta.php////tee=aja//kka=$kka//vva=$vva//ppa=$ppa//kkl=$kkl//vvl=$vvl//ppl=$ppl//toimittajahaku=$toimittajahaku'>".sprintf('%.02f', $ostolaskurow['summa_euroissa'])."</a><br>";
              $yht_tavara_summa[$ostolaskurow['litunn']] = $ostolaskurow['summa_euroissa'];
              $tavaralaskusummat += $ostolaskurow['summa_euroissa'];
            }
            echo "</td>";

            mysql_data_seek($ostolaskures, 0);

            echo "<td style='vertical-align: top;' nowrap>";
            while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
              if (trim($ostolaskurow['numero']) != '') {
                echo "<div id='div_$ostolaskurow[tunnus]' class='popup'>";
                echo $ostolaskurow['numero'];
                echo "</div>";
                echo " <a id='$ostolaskurow[tunnus]' class='tooltip'><img src='$palvelin2/pics/lullacons/info.png'></a>";
              }
              echo "<br>";
            }
            echo "</td>";
          }
          else {
            echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
          }

          $query  = "SELECT liitosotsikko.arvo * if(ostoreskontran_lasku.maksu_kurssi <> 0, ostoreskontran_lasku.maksu_kurssi, ostoreskontran_lasku.vienti_kurssi) summa_euroissa,
                     ostoreskontran_lasku.luontiaika,
                     concat(ostoreskontran_lasku.laskunro, ' ', ostoreskontran_lasku.viesti) numero,
                     ostoreskontran_lasku.tunnus,
                     liitosotsikko.tunnus litunn
                     FROM lasku liitosotsikko
                     JOIN lasku ostoreskontran_lasku ON (ostoreskontran_lasku.yhtio=liitosotsikko.yhtio and ostoreskontran_lasku.tunnus=liitosotsikko.vanhatunnus)
                     WHERE liitosotsikko.yhtio       = '$kukarow[yhtio]'
                     AND liitosotsikko.laskunro      = '$keikkarow[laskunro]'
                     AND liitosotsikko.vanhatunnus   <> 0
                     AND liitosotsikko.tila          = 'K'
                     AND ostoreskontran_lasku.vienti in ('B', 'E', 'H')"; // rahtilaskut
          $ostolaskures = pupe_query($query);

          if (mysql_num_rows($ostolaskures) > 0) {

            echo "<td style='vertical-align: top; text-align: right;' nowrap>";
            while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
              echo tv1dateconv($ostolaskurow['luontiaika'])."<br>";
            }
            echo "</td>";

            mysql_data_seek($ostolaskures, 0);

            echo "<td style='vertical-align: top; text-align: right;' nowrap>";
            while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
              echo "<a href='".$palvelin2."muutosite.php?tee=E&tunnus=$ostolaskurow[tunnus]&lopetus=".$palvelin2."raportit/ostotilausten_seuranta.php////tee=aja//kka=$kka//vva=$vva//ppa=$ppa//kkl=$kkl//vvl=$vvl//ppl=$ppl//toimittajahaku=$toimittajahaku'>".sprintf('%.02f', $ostolaskurow['summa_euroissa'])."</a><br>";
              $yht_kulu_summa[$ostolaskurow['litunn']] = $ostolaskurow['summa_euroissa'];
              $kululaskusummat += $ostolaskurow['summa_euroissa'];
            }
            echo "</td>";

            mysql_data_seek($ostolaskures, 0);

            echo "<td style='vertical-align: top;' nowrap>";

            while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
              if (trim($ostolaskurow['numero']) != '') {
                echo "<div id='div_$ostolaskurow[tunnus]' class='popup'>";
                echo $ostolaskurow['numero'];
                echo "</div>";
                echo " <a id='$ostolaskurow[tunnus]' class='tooltip'><img src='$palvelin2/pics/lullacons/info.png'></a>";
              }
              echo "<br>";
            }
            echo "</td>";
          }
          else {
            echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
          }

          if ($keikkarow['rahti_etu'] == 0) {
            $keikkarow['rahti_etu'] = '';
          }
          else {
            $yht_eturahti[$keikkarow['laskunro']] = $keikkarow['rahti_etu'];
          }

          echo "<td style='vertical-align: top; text-align: right;' nowrap>$keikkarow[rahti_etu]</td>";

          echo "<td style='vertical-align: top; text-align: right;' nowrap>";

          if ($tavaralaskusummat != 0) {
            $kuluprosentti = sprintf('%.02f', ($kululaskusummat + $keikkarow['rahti_etu'])  / $tavaralaskusummat * 100);

            if ($kuluprosentti != 0) {
              echo $kuluprosentti;
            }
          }

          echo "</td>";

          $query = "SELECT group_concat(DISTINCT laskutettuaika separator '<br/>') laskettuaika
                    FROM tilausrivi
                    WHERE yhtio     = '$kukarow[yhtio]'
                    AND otunnus     = $tilrivi_row[ltunnus]
                    AND uusiotunnus = $keikkarow[tunnus]
                    AND tyyppi      = 'O'";
          $saldoille_res = pupe_query($query);
          $saldoille_row = mysql_fetch_assoc($saldoille_res);

          echo "<td style='vertical-align: top;' nowrap>".tv1dateconv($saldoille_row['laskettuaika'])."</td>";
          echo "<td style='vertical-align: top;' nowrap>".tv1dateconv($keikkarow['mapvm'])."</td>";

          echo "<td style='vertical-align: top;'>";
          if ($keikkarow['mapvm'] == '0000-00-00' and $saldoille_row['laskettuaika'] == '0000-00-00') {
            echo "<img src='".$palvelin2."pics/lullacons/bot-plain-red.png'/>";
          }
          elseif ($keikkarow['mapvm'] == '0000-00-00') {
            echo "<img src='".$palvelin2."pics/lullacons/bot-plain-yellow.png'/>";
          }
          else {
            echo "<img src='".$palvelin2."pics/lullacons/bot-plain-green.png'/>";
          }
          echo "</td>";

          // and (mysql_num_rows($result) > 1 or count($rows) == end($rows))
          if ($i < ($x + $xx)) {
            echo "</tr><tr class='aktiivi'>";
          }

          $i++;
        }

        if (count($rows) == 0) {
          echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td><img src='".$palvelin2."pics/lullacons/bot-plain-white.png'/></td>";
        }

        echo "</tr><tr class='aktiivi'>";

        $ed_tunn = $tilrivi_row['ltunnus'];
        $vaihtuuko_toimittaja = $toimittajarow['tunnus'];

      }

      // LASKUT JOITA EI OLE LIITETTY KEIKKOIHIN
      $query = "SELECT lasku.summa, lasku.maksu_kurssi, lasku.vienti_kurssi, lasku.valkoodi,
                lasku.luontiaika,
                concat(lasku.laskunro, ' ', lasku.viesti) numero,
                lasku.tunnus
                FROM lasku
                LEFT JOIN lasku AS lasku2 on (lasku2.yhtio = lasku.yhtio and lasku2.vanhatunnus = lasku.tunnus and lasku2.tila = 'K')
                WHERE lasku.yhtio  = '$kukarow[yhtio]' and
                lasku.tila         in ('H','Y','M','P','Q') and
                lasku.vienti       in ('B','C','J','E','F','K','H','I','L') and
                lasku.liitostunnus = '$toimittajarow[tunnus]' and
                lasku2.tunnus IS NULL
                ORDER BY lasku.luontiaika, lasku.summa";
      $ei_liitetyt_res = pupe_query($query);

      while ($ei_liitetyt_row = mysql_fetch_assoc($ei_liitetyt_res)) {
        // jos meill‰ on hyvityslasku niin haetaan vaan negatiivisi‰ alvikirjauksia (en tied‰ ollenkaan onko t‰m‰ futureprooof) :(
        if ($ei_liitetyt_row["summa"] < 0) {
          $alvilisa = "and summa < 0";
        }
        else {
          $alvilisa = "and summa > 0";
        }

        // Haetaan kululaskun kaikki verotiliˆinnit jotta voidaan tallentaa myˆs veroton summa
        $query = "SELECT sum(summa) summa
                  from tiliointi
                  where yhtio  = '$kukarow[yhtio]'
                  and ltunnus  = '$ei_liitetyt_row[tunnus]'
                  and tilino   = '$yhtiorow[alv]'
                  $alvilisa
                  and korjattu = ''";
        $alvires = pupe_query($query);
        $alvirow = mysql_fetch_assoc($alvires);

        // Ostoreskontralaskun veron m‰‰r‰
        $alvisumma = $alvirow["summa"];

        if (strtoupper($ei_liitetyt_row["valkoodi"]) != strtoupper($yhtiorow["valkoodi"])) {
          if ($ei_liitetyt_row["maksu_kurssi"] != 0) {
            $alvisumma = $alvirow["summa"] / $ei_liitetyt_row["maksu_kurssi"];
          }
          else {
            $alvisumma = $alvirow["summa"] / $ei_liitetyt_row["vienti_kurssi"];
          }
        }

        $ei_liitetyt_row["arvo"] = round((float) $ei_liitetyt_row["summa"] - (float) $alvisumma, 2);

        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'>".tv1dateconv($ei_liitetyt_row['luontiaika'])."</td>";
        echo "<td style='vertical-align: top; text-align: right;'><a href='".$palvelin2."muutosite.php?tee=E&tunnus=$ei_liitetyt_row[tunnus]&lopetus=".$palvelin2."raportit/ostotilausten_seuranta.php////tee=aja//kka=$kka//vva=$vva//ppa=$ppa//kkl=$kkl//vvl=$vvl//ppl=$ppl//toimittajahaku=$toimittajahaku'>".sprintf('%.02f', $ei_liitetyt_row['arvo'])."</a></td>";
        $yht_tavara_summa[$ei_liitetyt_row['tunnus']] = $ei_liitetyt_row['arvo'];
        echo "<td style='vertical-align: top;'>";
        if (trim($ei_liitetyt_row['numero']) != '') {
          echo "<div id='div_$ei_liitetyt_row[tunnus]' class='popup'>";
          echo $ei_liitetyt_row['numero'];
          echo "</div>";
          echo " <a id='$ei_liitetyt_row[tunnus]' class='tooltip'><img src='$palvelin2/pics/lullacons/info.png'></a>";
        }
        echo "</td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'></td>";
        echo "<td style='vertical-align: top;'><img src='".$palvelin2."pics/lullacons/bot-plain-blue.png'/></td>";
        echo "</tr><tr class='aktiivi'>";
      }

      $yht_varastossa_kpl = $yht_varastossa_kpl != 0 ? (float) $yht_varastossa_kpl.'/' : '';
      $yht_varastossa_rivit = $yht_varastossa_rivit != 0 ? (float) $yht_varastossa_rivit.'/' : '';
      $yht_paino = $yht_paino != 0 ? $yht_paino : '';
      $yht_arvo = $yht_arvo != 0 ? sprintf('%.02f', $yht_arvo) : '';
      $yht_tavara_summa = array_sum($yht_tavara_summa) != 0 ? sprintf('%.02f', array_sum($yht_tavara_summa)) : '';
      $yht_kulu_summa = array_sum($yht_kulu_summa) != 0 ? sprintf('%.02f', array_sum($yht_kulu_summa)) : '';
      $yht_eturahti = array_sum($yht_eturahti) != 0 ? sprintf('%.02f', array_sum($yht_eturahti)) : '';
      $yht_kuluprosentti = $yht_tavara_summa != 0 ? sprintf('%.02f', ($yht_kulu_summa + $yht_eturahti) / $yht_tavara_summa * 100) : 0;
      $yht_kuluprosentti = $yht_kuluprosentti != 0 ? $yht_kuluprosentti : '';

      echo "<td class='spec'>", t("Yhteens‰"), "</td>";
      echo "<td class='spec'>&nbsp;</td>";
      echo "<td class='spec'>&nbsp;</td>";
      echo "<td class='spec' style='text-align:right;'>$yht_paino</td>";
      echo "<td class='spec' style='text-align:right;'>$yht_varastossa_kpl", (float) $yht_kpl, "</td>";
      echo "<td class='spec' style='text-align:right;'>$yht_varastossa_rivit", (float) $yht_rivit, "</td>";
      echo "<td class='spec' style='text-align:right;'>$yht_arvo</td>";
      echo "<td class='spec'>&nbsp;</td>";
      echo "<td class='spec'>&nbsp;</td>";
      echo "<td class='spec' style='text-align: right;'>$yht_tavara_summa</td>";
      echo "<td class='spec'>&nbsp;</td>";
      echo "<td class='spec'>&nbsp;</td>";
      echo "<td class='spec' style='text-align: right;'>$yht_kulu_summa</td>";
      echo "<td class='spec'>&nbsp;</td>";
      echo "<td class='spec' style='text-align: right;'>$yht_eturahti</td>";
      echo "<td class='spec' style='text-align: right;'>$yht_kuluprosentti</td>";
      echo "<td class='spec'>&nbsp;</td>";
      echo "<td class='spec'>&nbsp;</td>";
      echo "<td class='spec'>&nbsp;</td>";
      echo "</tr>";
    }
  }
  echo "</table>";

  echo "  <script type='text/javascript'>
        $(function(){

          $('#tilaus').change(function(){
            if (this.value != '') {
              $.get('$_SERVER[SCRIPT_NAME]', { tee: 'hae_kommentti', tun: this.value, no_head: 'yes', ohje: 'off' }, function(data){
                $('#message:font').text('Lis‰‰ kommentti');
                $('#kommentti').val(data);
              });
            }
            else {
              $('#kommentti').val('');
              $('#message:font').text('Valitse tilausnumero ja syˆt‰ kommentti');
            }
          });

          $('#tallenna_button').click(function(){
            if ($('#tilaus').val() == '') {
              $('#message:font').text('Et valinnut tilausnumeroa!');
              return false;
            }
            else {
              var tilausno = $('#tilaus').val();
              var komm = $('#kommentti').val();
              $.get('$_SERVER[SCRIPT_NAME]', { tee: 'kommentti', tilaus: tilausno, kommentti: komm, no_head: 'yes', ohje: 'off' }, function(data){
                $('#tallenna_message').html(data);

                $('#div_'+tilausno).html(komm);
              });
            }
          });
        });
      </script>";
  if (count($tunnukset) > 0) {
    echo "<br/><br/>";

    sort($tunnukset);

    echo "<form method='post' autocomplete='off' id='kommentti_form'>";
    echo "<input type='hidden' name='tee' value='kommentti'>";
    echo "<input type='hidden' name='toimittajahaku' value='$toimittajaid'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='ppa' value='$ppa'>";
    echo "<input type='hidden' name='kka' value='$kka'>";
    echo "<input type='hidden' name='vva' value='$vva'>";
    echo "<input type='hidden' name='ppl' value='$ppl'>";
    echo "<input type='hidden' name='kkl' value='$kkl'>";
    echo "<input type='hidden' name='vvl' value='$vvl'>";

    echo "<table>";

    echo "<tr><td colspan='2'><div id='message'>", t("Valitse tilausnumero ja syˆt‰ kommentti"), "</div></td></tr>";
    echo "<tr>";
    echo "<td><select name='tilaus' id='tilaus'>";
    echo "<option value=''>", t("Valitse tilausnumero"), "</option>";

    foreach ($tunnukset as $tun) {
      echo "<option value='$tun'>$tun</option>";
    }

    echo "</select></td><td><textarea name='kommentti' id='kommentti' rows='5' cols='50' value='$kommentti'></textarea></td></tr><tr><td class='back'><input type='button' id='tallenna_button' value='", t("Tallenna"), "'>";
    echo "</td></tr></table>";
    echo "</form>";
  }
}

if (file_exists("../inc/footer.inc")) {
  require "../inc/footer.inc";
}
