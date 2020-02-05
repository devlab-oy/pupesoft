<?php

require 'inc/parametrit.inc';
require 'tilauskasittely/luo_myyntitilausotsikko.inc';

enable_ajax();

echo "
  <style type='text/css' media='screen'>
    tr td {
      padding: 0px;
      margin: 0px;
    }
  </style>
  ";

// ajax listaa tuotteet
if ($tee == "listaa_tuotteet") {

  $query = "SELECT tuote.*
            FROM autodata_tuote
            JOIN tuote ON (tuote.yhtio = autodata_tuote.yhtio and tuote.try = autodata_tuote.tuoteryhma)
            JOIN yhteensopivuus_tuote ON (yhteensopivuus_tuote.yhtio = autodata_tuote.yhtio and yhteensopivuus_tuote.tuoteno = tuote.tuoteno and yhteensopivuus_tuote.atunnus = '$_GET[atunnus]')
            WHERE autodata_tuote.yhtio = '$kukarow[yhtio]' AND
            autodata_tuote.op_ref      = '$_GET[group_id]'";
  $res = mysql_query($query) or pupe_error($query);


  if (mysql_num_rows($res) > 0) {
    $yht_i = 0;

    echo "<table>";
    echo "<tr class='aktiivi'>";
    echo "<th>tuoteno</th>";
    echo "<th>nimitys</th>";
    echo "<th>hinta</th>";
    echo "<th>saldo</th>";
    echo "<th>lisaa tilaukselle</th>";
    echo "</tr>";

    while ($row = mysql_fetch_array($res)) {

      list ($saldo, $hyllyssa, $myytavissa, $bool) = saldo_myytavissa($row["tuoteno"]);

      echo "<tr class='aktiivi'>";
      echo "<td>$row[tuoteno]</td>";
      echo "<td>$row[nimitys]</td>";
      echo "<td>$row[myyntihinta]</td>";
      echo "<td>$myytavissa</td>";
      echo "<td>";

      if ($kukarow["kesken"] != 0 or is_numeric($ostoskori) or $kukarow["oletus_asiakas"] != "") {
        echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
        echo "<input type='text' size='3' name='tilkpl[$yht_i]'> kpl";
        $yht_i++;
      }

      echo "</td>";
      echo "</tr>";
    }
    echo "</table>\n\n";

  }
  else {
    echo "Yhtään soveltuvaa tuotetta ei löytynyt";
  }

  exit;
}

// koko formi submitattu, lisätään tuotteet tilaukselle
if ($toiminto == "lisaarivit" and ($kukarow["kesken"] != 0 or is_numeric($ostoskori) or $kukarow["oletus_asiakas"] != 0)) {

  if (is_numeric($ostoskori)) {
    $kori = check_ostoskori($ostoskori, $kukarow["oletus_asiakas"]);
    $kukarow["kesken"] = $kori["tunnus"];
  }

  if ($kukarow["kesken"] == 0 and $kukarow["oletus_asiakas"] != 0) {
    $kukarow["kesken"] = luo_myyntitilausotsikko("EXTRANET", $kukarow["oletus_asiakas"]);
  }

  // haetaan avoimen tilauksen otsikko
  $query    = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
  $laskures = mysql_query($query);

  if (mysql_num_rows($laskures) == 0) {
    echo "<font class='error'>Sinulla ei ole avointa tilausta!</font><br>";
  }
  else {

    // tilauksen tiedot
    $laskurow = mysql_fetch_array($laskures);

    if (is_numeric($ostoskori)) {
      echo "<font class='message'>Lisätään tuotteita ostoskoriin $ostoskori.</font><br>";
    }
    else {
      echo "<font class='message'>Lisätään tuotteita tilaukselle $kukarow[kesken].</font><br>";
    }

    // käydään läpi formin kaikki rivit
    foreach ($tilkpl as $yht_i => $kpl) {

      if ((float) $kpl > 0) {

        // haetaan tuotteen tiedot
        $query    = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
        $tuoteres = mysql_query($query);

        if (mysql_num_rows($tuoteres) == 0) {
          echo "<font class='error'>Tuotetta $tiltuoteno[$yht_i] ei löydy!</font><br>";
        }
        else {

          // tuote löytyi ok, lisätään rivi
          $trow = mysql_fetch_array($tuoteres);

          $ytunnus         = $laskurow["ytunnus"];
          $kpl             = (float) $kpl;
          $tuoteno         = $trow["tuoteno"];
          $toimaika        = $laskurow["toimaika"];
          $kerayspvm       = $laskurow["kerayspvm"];
          $hinta          = "";
          $netto          = "";
          $var         = "";
          $alv         = "";
          $paikka         = "";
          $varasto        = "";
          $rivitunnus     = "";
          $korvaavakielto   = "";
          $jtkielto      = $laskurow['jtkielto'];
          $varataan_saldoa = "";

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            ${'ale'.$alepostfix} = '';
          }

          // jos meillä on ostoskori muuttujassa numero, niin halutaan lisätä tuotteita siihen ostoskoriin
          if (is_numeric($ostoskori)) {
            lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
            $kukarow["kesken"] = "";
          }
          elseif (file_exists("tilauskasittely/lisaarivi.inc")) {
            require "tilauskasittely/lisaarivi.inc";
          }
          else {
            require "lisaarivi.inc";
          }

          echo "<font class='message'>Lisättiin $kpl kpl tuotetta $trow[tuoteno].</font><br>";

        } // tuote ok else

      } // end kpl > 0

    } // end foreach

  } // end tuotelöytyi else

  echo "<br>";
}

echo "<font class='head'>".t('Auton määräaikaishuollot') ."</font><hr />";

$query = "SELECT * FROM autodata WHERE autodataid = '{$_POST['mid']}'";
$res   = mysql_query($query) or pupe_error($query);
$auto  = mysql_fetch_array($res);

echo "<p><b>{$auto['merkki']} {$auto['malli']} {$auto['mallitarkenne']} {$auto['moottoritil']}, {$auto['alkuvuosi']}-{$auto['loppuvuosi']}</b></p>";

echo "<p>Valitse huoltokategoria:</p>";

$query = "SELECT autodata_links.varia, autodata_links.id
          FROM autodata_links
          WHERE autodata_links.mid = '{$_POST['mid']}'
          AND autodata_links.type  = 'SG'
          AND autodata_links.yhtio = '{$kukarow['yhtio']}'";
$res = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($res) == 1) {
  $row = mysql_fetch_array($res);
  $_POST['variant'] = $row["id"];
}
elseif (mysql_num_rows($res) > 1) {
  echo "<table>";
  while ($row = mysql_fetch_array($res)) {

    echo "<tr><td>{$row['varia']}</td><td>";

    if ($row['id'] == $_POST['variant']) {
      echo t("Valittu");
    }
    else {
      echo "<form method='post'>
          <input type='hidden' name='mid' value='{$_POST['mid']}'/>
          <input type='hidden' name='variant' value='{$row['id']}'/>
          <input type='hidden' name='atunnus' value='$_POST[atunnus]'>
          <input type='hidden' name='tyyppi' value='$_POST[tyyppi]'>
          <input type='submit' name='submit' value='Valitse'/></form>";
    }

    echo "</td></tr>";
  }
  echo "</table>";
}

if (isset($_POST['variant']) and !empty($_POST['variant'])) {
  $query = "SELECT autodata_service_intervals.link, mid, seq, kms, months, addserv, stdtime, autodata_service_intervals.tunnus
            FROM autodata_service_intervals
            JOIN autodata_links ON autodata_links.link=autodata_service_intervals.link AND autodata_links.yhtio=autodata_service_intervals.yhtio
            WHERE autodata_links.mid ='{$_POST['mid']}'
            AND autodata_links.yhtio='{$kukarow['yhtio']}'
            AND autodata_links.type='SG'
            AND autodata_service_intervals.addserv = 0
            AND autodata_links.id                  = {$_POST['variant']}
            ORDER BY kms";
  //echo $query;
  $res = mysql_query($query) or pupe_error($query);

  echo "<br><table>
    <tr>
    <th>Huolto</th>
    <th>Kesto</th>
    <th></th>
    </tr>";

  while ($huolto = mysql_fetch_array($res)) {

    echo "<tr><td>{$huolto['kms']} kilometria, {$huolto['months']} kuukautta</td><td>{$huolto['stdtime']}</td>
        <td>";

    if ("{$huolto['tunnus']}|{$huolto['link']}|{$huolto['seq']}" == $_POST['huolto_seq']) {
      echo t('Valittu');
    }
    else {
      echo "<form action='{$_SERVER['REQUEST_URI']}' method='post'>
          <input type='hidden' name='atunnus' value='$_POST[atunnus]'>
          <input type='hidden' name='tyyppi' value='$_POST[tyyppi]'>
          <input type='hidden' name='mid' value='{$_POST['mid']}'/>
          <input type='hidden' name='variant' value='{$_POST['variant']}'/>
          <input type='hidden' name='huolto_seq' value='{$huolto['tunnus']}|{$huolto['link']}|{$huolto['seq']}'>
          <input onClick='submit();' type='submit' name='submit' value='Valitse'></form>";
    }
    echo "</td></tr>";
  }

  echo "</table>";
  echo "<table><tr><th>Yhteensä</th><td></td></tr></table>";
}

if (isset($_POST['huolto_seq'])) {

  echo "<p>Huollon vaiheet eritelty:</p>";

  echo "<form name='lisaa' method='post'>";
  echo "<input type='hidden' name='atunnus' value='$_POST[atunnus]'>";
  echo "<input type='hidden' name='tyyppi' value='$_POST[tyyppi]'>";
  echo "<input type='hidden' name='mid' value='{$_POST['mid']}'/>";
  echo "<input type='hidden' name='variant' value='{$_POST['variant']}'/>";
  echo "<input type='hidden' name='huolto_seq' value='$_POST[huolto_seq]'>";
  echo "<input type='hidden' name='toiminto' value = 'lisaarivit'>";

  echo "<table>
    <tr>
      <th>Koodi</th>
      <th>Toimenpide</th>
    </tr>";

  list($tunnus, $link, $seq) = explode('|', $_POST['huolto_seq']);

  $query = "SELECT operations.op_ref, operations.op_text, operations.part_text, group_concat(autodata_tuote.tuoteryhma separator ', ') ryhmat
            FROM autodata_service_guide as guide
            JOIN autodata_service_operations as operations ON operations.line=guide.line and operations.yhtio=guide.yhtio
            LEFT JOIN autodata_tuote ON operations.op_ref=autodata_tuote.op_ref
            WHERE
            guide.link      = '$link'
            AND guide.seq   = $seq
            AND guide.yhtio = '{$kukarow['yhtio']}'
            GROUP BY operations.op_ref
            ORDER BY operations.line";
  $res = mysql_query($query) or pupe_error($query);

  $last = null;
  while ($row = mysql_fetch_assoc($res)) {

    list($group, $tmp) = explode('.', $row['op_ref']);

    if ($last != $group) {
      $query = "SELECT op_text FROM autodata_service_operations WHERE op_ref='" . $group. ".0000'";
      $resb   = mysql_query($query) or pupe_error($query);
      $text = mysql_fetch_assoc($resb);

      echo "<tr><th>$group</th><th>{$text['op_text']}</th></tr>";
      $last  = $group;
    }

    echo "<tr class='aktiivi'>";

    if ($row["ryhmat"] != "") {
      echo "<td><a id='$row[op_ref]_A' href='javascript:sndReq(\"$row[op_ref]_T\", \"autodata_sg.php?tee=listaa_tuotteet&amp;atunnus=$_POST[atunnus]&amp;group_id=$row[op_ref]&amp;ohje=off\", \"$row[op_ref]_A\")'>$row[op_ref]</a></td>";
    }
    else {
      echo "<td>$row[op_ref]</td>";
    }

    echo "<td>$row[op_text]</td>";
    echo "</tr>";
    echo "<tr><td class='back' colspan='4'><div style='padding:10px; display:none;' id='$row[op_ref]_T'></div></td></tr>\n\n";

  }

  echo "</table>";
}

require 'inc/footer.inc';
