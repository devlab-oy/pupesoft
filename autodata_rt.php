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

// ajax listaa aliluokan työt
if ($tee == "aliluokan_tyot") {

  $query = "SELECT *
            FROM autodata_repair_text
            JOIN autodata_repair_times USE INDEX (yhtio_link_itemid) ON (autodata_repair_text.yhtio = autodata_repair_times.yhtio and autodata_repair_times.link = '$_GET[link]' and autodata_repair_text.item_id = autodata_repair_times.item_id)
            WHERE autodata_repair_text.yhtio = '$kukarow[yhtio]' and
            length(autodata_repair_text.group_id) > 2 and
            left(autodata_repair_text.group_id,2) = '$_GET[group_id]'";
  $res = mysql_query($query) or pupe_error($query);


  if (mysql_num_rows($res) > 0) {

    echo "<table>";

    while ($row = mysql_fetch_array($res)) {

      // katotaan onko työlle jotain lisäinfoa
      $query = "SELECT *
                FROM autodata_repair_incjobs USE INDEX (yhtio_item_id)
                JOIN autodata_repair_text ON (autodata_repair_text.group_id = autodata_repair_incjobs.incjob AND autodata_repair_text.yhtio = autodata_repair_incjobs.yhtio)
                JOIN autodata_repair_times ON (autodata_repair_times.item_id = autodata_repair_text.item_id AND autodata_repair_times.link = autodata_repair_incjobs.link AND autodata_repair_times.yhtio = autodata_repair_text.yhtio)
                WHERE autodata_repair_incjobs.yhtio = '$kukarow[yhtio]' and
                autodata_repair_incjobs.link        = '$_GET[link]' and
                autodata_repair_incjobs.group_id    = '$row[group_id]'";
      $tyores = mysql_query($query) or pupe_error($query);

      echo "<tr class='aktiivi'>";
      echo "<td><a id='$row[group_id]_A' href='javascript:sndReq(\"$row[group_id]_T\", \"autodata_rt.php?tee=listaa_tuotteet&amp;atunnus=$_GET[atunnus]&amp;link=$_GET[link]&amp;group_id=$row[group_id]&amp;ohje=off\", \"$row[group_id]_A\")'>$row[group_id]</a></td>";

      $tyoinfo = "";
      if (mysql_num_rows($tyores) > 0) {

        $tyoinfo .= "<table>";
        while ($tyorow = mysql_fetch_array($tyores)) {
          $tyoinfo .= "<tr>";
          $tyoinfo .= "<td>$tyorow[incjob]</td>";
          $tyoinfo .= "<td>$tyorow[text]</td>";
          $tyoinfo .= "<td>$tyorow[text_right]</td>";
          $tyoinfo .= "<td>$tyorow[time] h</td>";
          $tyoinfo .= "</tr>";
        }
        $tyoinfo .= "</table>";

        echo "<td>$row[text] (<a href='javascript:toggleGroup(\"$row[group_id]_TB\")'>Lisätietoa</a>)</td>";
      }
      else {
        echo "<td>$row[text]</td>";
      }

      echo "<td>$row[text_right]</td>";
      echo "<td>$row[time] h</td>";
      echo "</tr>";
      echo "<tr><td class='back' colspan='4'><div style='padding:10px; display:none;' id='$row[group_id]_TB'>$tyoinfo</div></td></tr>\n\n";
      echo "<tr><td class='back' colspan='4'><div style='padding:10px; display:none;' id='$row[group_id]_T'></div></td></tr>\n\n";


    }

    echo "</table>";
  }
  else {
    echo "Ei töitä!";
  }

  exit;
}

// ajax listaa aliluokat
if ($tee == "listaa_aliluokat") {

  $query = "SELECT *
            FROM autodata_repair_text
            WHERE autodata_repair_text.yhtio = '$kukarow[yhtio]' and
            length(autodata_repair_text.group_id) = 2 and
            left(autodata_repair_text.group_id,1) = '$_GET[group_id]'";
  $res = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($res) > 0) {

    echo "<table>";

    while ($row = mysql_fetch_array($res)) {
      echo "<tr class='aktiivi'>";
      echo "<td>$row[group_id]</td>";
      echo "<td><a id='$row[group_id]_A' href='javascript:sndReq(\"$row[group_id]_T\", \"autodata_rt.php?tee=aliluokan_tyot&amp;atunnus=$_GET[atunnus]&amp;link=$_GET[link]&amp;group_id=$row[group_id]&amp;ohje=off\", \"$row[group_id]_A\")'>$row[text]</a></td>";
      echo "</tr>";
      echo "<tr><td class='back' colspan='2'><div style='padding:10px; display:none;' id='$row[group_id]_T'></div></td></tr>\n\n";
    }

    echo "</table>";
  }
  else {
    echo "Ei aliluokkia!";
  }

  exit;
}

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
    echo "Yhtään soveltuvaa tuotetta ei löytynyt työlle $_GET[group_id]";
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
  $query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
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
        $query    = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
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

// käyttöliittymä
echo "<font class='head'>Muut huollot</font><hr/>";

// haetaan MID-luvulla auton linkki
$query = "SELECT * from autodata_links USE INDEX (yhtio_type_mid)
          WHERE autodata_links.yhtio = '$kukarow[yhtio]' AND
          autodata_links.type        = 'RT' AND
          autodata_links.mid         = '$_POST[mid]'";
$res = mysql_query($query) or pupe_error($query);

if (mysql_num_rows($res) == 1) {
  // jos vain yksi mennään suoraan läpi
  $row = mysql_fetch_array($res);
  $_POST['link'] = $row["link"];
}
elseif (mysql_num_rows($res) > 1) {
  echo "<table>";
  while ($row = mysql_fetch_array($res)) {

    echo "<tr><td>$row[varia]</td><td>";

    if ($row['link'] === $_POST['link']) {
      echo t("Valittu");
    }
    else {
      echo "<form method='post'>
        <input type='hidden' name='atunnus' value='$_POST[atunnus]'>
        <input type='hidden' name='mid'     value='$_POST[mid]'>
        <input type='hidden' name='link'    value='$row[link]'>
        <input type='submit' name='submit'  value='Valitse'>
        </form>";
    }
    echo "</td></tr>";
  }
  echo "</table><br>\n\n";
}
else {
  echo "Autolle ei löydy huoltoja!";
}

if (isset($_POST['link'])) {

  // alotetaan formi
  echo "<form name='lisaa' method='post'>";
  echo "<input type='hidden' name='atunnus'    value='$_POST[atunnus]'>";
  echo "<input type='hidden' name='tyyppi'     value='$_POST[tyyppi]'>";
  echo "<input type='hidden' name='mid'        value='$_POST[mid]'>";
  echo "<input type='hidden' name='link'       value='$_POST[link]'>";
  echo "<input type='hidden' name='toiminto'   value='lisaarivit'>\n\n";

  // haetaan huoltojen pääluokat
  $query = "SELECT *
            FROM autodata_repair_text
            WHERE autodata_repair_text.yhtio = '$kukarow[yhtio]' and
            length(autodata_repair_text.group_id) = 1";
  $res = mysql_query($query) or pupe_error($query);

  echo "<table>\n\n";

  while ($row = mysql_fetch_assoc($res)) {
    echo "<tr class='aktiivi'>";
    echo "<td>$row[group_id]</td>";
    echo "<td><a id='$row[group_id]_A' href='javascript:sndReq(\"$row[group_id]_T\", \"autodata_rt.php?tee=listaa_aliluokat&amp;atunnus=$_POST[atunnus]&amp;link=$_POST[link]&amp;group_id=$row[group_id]&amp;ohje=off\", \"$row[group_id]_A\")'>$row[text]</a></td>";
    echo "</tr>";
    echo "<tr><td class='back' colspan='2'><div style='padding:10px; display:none;' id='$row[group_id]_T'></div></td></tr>\n\n";
  }

  echo "</table>";
  echo "<br>";
  echo "<input type='submit' value = '".t("Lisää valitut tuotteet tilaukselle")."'>";
  echo "</form>\n\n";
}

require 'inc/footer.inc';
