<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Ei käytetä pakkausta
$compression = FALSE;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "../inc/parametrit.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

echo "<font class='head'>".t("Inventointiraportti")."</font><hr>";

if (!isset($mul_invaaja)) $mul_invaaja = array();
if (!isset($mul_invenlaji)) $mul_invenlaji = array();
if (!isset($mul_kustp)) $mul_kustp = array();
if (!isset($mul_osasto)) $mul_osasto = array();
if (!isset($mul_tme)) $mul_tme = array();
if (!isset($mul_try)) $mul_try = array();
if (!isset($mul_tuotemyyja)) $mul_tuotemyyja = array();
if (!isset($mul_tuoteostaja)) $mul_tuoteostaja = array();
if (!isset($mul_varastot)) $mul_varastot = array();

if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
  $logistiikka_yhtiolisa = "yhtio in ($konsernivarasto_yhtiot)";
}
else {
  $logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
}

if (count($_POST) > 0) {

  // hehe, näin on helpompi verrata päivämääriä
  $query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
  $result = pupe_query($query);
  $row    = mysql_fetch_array($result);

  if ($row["ero"] > 365 and $ajotapa != 'tilausauki') {
    echo "<font class='error'>".t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentaväli!")."</font><br>";
    $tee = "";
  }

  // jos ei ole mitään yritystä valittuna ei tehdä mitään
  if (count($yhtiot) == 0) {
    $tee = "";
  }
  else {
    $yhtio  = "";
    foreach ($yhtiot as $apukala) {
      $yhtio .= "'$apukala',";
    }
    $yhtio = substr($yhtio, 0, -1);
  }

  // jos joku päiväkenttä on tyhjää ei tehdä mitään
  if ($ppa == "" or $kka == "" or $vva == "" or $ppl == "" or $kkl == "" or $vvl == "") {
    $tee = "";
  }

  if ($tee == 'go') {

    // no hacking, please.
    $lisa   = "";
    $query  = "";
    $group  = "";
    $order  = "";
    $select = "";
    $gluku  = 0;

    // näitä käytetään queryssä
    $sel_osasto      = "";
    $sel_tuoteryhma  = "";
    $sel_tuotemerkki = "";
    $sel_tuotemyyja  = "";
    $sel_tuoteostaja = "";
    $sel_kustp       = "";

    $apu = array();

    if (count($yhtiot) > 1) {
      if ($group!="") $group .= ",tuote.yhtio";
      else $group .= "tuote.yhtio";
      $select .= "tuote.yhtio yhtio, ";
      $order  .= "tuote.yhtio,";
      $gluku++;
    }

    foreach ($jarjestys as $ind => $arvo) {
      if (trim($arvo) != "") $apu[] = $arvo;
    }

    if (count($apu) == 0) {
      ksort($jarjestys);
    }
    else {
      asort($jarjestys);
    }

    $apu = array();

    foreach ($jarjestys as $i => $arvo) {
      if ($ruksit[$i] != "") {
        $apu[$i] = $ruksit[$i];
      }
    }

    foreach ($apu as $i => $mukaan) {

      if ($mukaan == "osasto") {
        if ($group!="") $group .= ",tuote.osasto";
        else $group .= "tuote.osasto";
        $select .= "tuote.osasto 'osasto', ";
        $order  .= "tuote.osasto,";
        $gluku++;
      }

      if ($mukaan == "tuoteryhma") {
        if ($group!="") $group .= ",tuote.try";
        else $group .= "tuote.try";
        $select .= "tuote.try 'tuoteryhmä', ";
        $order  .= "tuote.try,";
        $gluku++;
      }

      if ($mukaan == "tuotemerkki") {
        if ($group!="") $group .= ",tuote.tuotemerkki";
        else $group  .= "tuote.tuotemerkki";
        $select .= "tuote.tuotemerkki 'tuotemerkki', ";
        $order  .= "tuote.tuotemerkki,";
        $gluku++;
      }

      if ($mukaan == "tuotemyyja") {
        if ($group!="") $group .= ",tuote.myyjanro";
        else $group  .= "tuote.myyjanro";
        $select .= "tuote.myyjanro 'tuotemyyjä', ";
        $order  .= "tuote.myyjanro,";
        $gluku++;
      }

      if ($mukaan == "tuoteostaja") {
        if ($group!="") $group .= ",tuote.ostajanro";
        else $group  .= "tuote.ostajanro";
        $select .= "tuote.ostajanro 'ostaja', ";
        $order  .= "tuote.ostajanro,";
        $gluku++;
      }

      if ($mukaan == "kustannuspaikka") {
        if ($group!="") $group .= ",tuote.kustp";
        else $group  .= "tuote.kustp";
        $select .= "tuote.kustp as 'kustannuspaikka', ";
        $order  .= "tuote.kustp,";
        $gluku++;
      }

      if ($mukaan == "varasto") {
        if ($group!="") $group .= ",varastopaikat.tunnus";
        else $group  .= "varastopaikat.tunnus";
        $select .= "varastopaikat.nimitys as 'varasto', ";
        $order  .= "varastopaikat.nimitys,";
        $gluku++;
      }

      if ($mukaan == "invenlaji") {
        if ($group!="") $group .= ",inventointilaji";
        else $group  .= "inventointilaji";
        $select .= "substring(tapahtuma.selite,(length(tapahtuma.selite)-locate('>rb<',reverse(tapahtuma.selite)))+2) as 'inventointilaji', ";
        $order  .= "inventointilaji,";
        $gluku++;
      }

      if ($mukaan == "invaaja") {
        if ($group!="") $group .= ",tapahtuma.laatija";
        else $group  .= "tapahtuma.laatija";
        $select .= "kuka.nimi as 'henkilö', ";
        $order  .= "kuka.nimi,";
        $gluku++;
      }

      if ($mukaan == "tuote") {
        if ($nimitykset == "") {
          if ($group!="") $group .= ",tuote.tuoteno";
          else $group  .= "tuote.tuoteno";
          $select .= "tuote.tuoteno tuoteno, ";
          $order  .= "tuote.tuoteno,";
          $gluku++;
        }
        else {
          if ($group!="") $group .= ", tuote.tuoteno, tuote.nimitys";
          else $group  .= "tuote.tuoteno, tuote.nimitys";
          $select .= "tuote.tuoteno tuoteno, tuote.nimitys nimitys, ";
          $order  .= "tuote.tuoteno,";
          $gluku++;
        }
        if ($sarjanumerot != '') {
          $select .= "group_concat(concat(tilausrivi.tunnus,'#',tilausrivi.kpl)) sarjanumero, ";
        }
        if ($varastonarvo != '') {
          $select .= "0 varastonarvo, 0 kierto, ";
        }

        if ($rajaus[$i] != "") {
          $lisa .= " and tuote.tuoteno='$rajaus[$i]' ";
        }
      }
    }

    if ($kaikkirivit != "") {
      if ($group!="") $group .= ",tapahtuma.tunnus,tapahtuma.laadittu";
      else $group  .= "tapahtuma.tunnus,tapahtuma.laadittu";
      $select .= "tapahtuma.selite 'selite', tapahtuma.laadittu,";
      $order  = "tapahtuma.laadittu,".$order;
      $gluku++;
    }

    if ($order != "") {
      $order = substr($order, 0, -1);
    }
    else {
      $order = "1";
    }

    if (is_array($mul_osasto) and count($mul_osasto) > 0) {
      $sel_osasto = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_osasto))."')";
      $lisa .= " and tuote.osasto in $sel_osasto ";
    }

    if (is_array($mul_try) and count($mul_try) > 0) {
      $sel_tuoteryhma = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_try))."')";
      $lisa .= " and tuote.try in $sel_tuoteryhma ";
    }

    if (is_array($mul_tme) and count($mul_tme) > 0) {
      $sel_tuotemerkki = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_tme))."')";
      $lisa .= " and tuote.tuotemerkki in $sel_tuotemerkki ";
    }

    if (is_array($mul_tuotemyyja) and count($mul_tuotemyyja) > 0) {
      $sel_tuotemyyja = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_tuotemyyja))."')";
      $lisa .= " and tuote.myyjanro in $sel_tuotemyyja ";
    }

    if (is_array($mul_tuoteostaja) and count($mul_tuoteostaja) > 0) {
      $sel_tuoteostaja = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_tuoteostaja))."')";
      $lisa .= " and tuote.ostajanro in $sel_tuoteostaja ";
    }

    if (is_array($mul_kustp) and count($mul_kustp) > 0) {
      $sel_kustp = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_kustp))."')";
      $lisa .= " and tuote.kustp in $sel_kustp ";
    }

    if (is_array($mul_varastot) and count($mul_varastot) > 0) {
      $sel_varasto = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_varastot))."')";
      $lisa .= " and varastopaikat.tunnus in $sel_varasto ";
    }

    if (is_array($mul_invenlaji) and count($mul_invenlaji) > 0) {
      $sel_invenlaji = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_invenlaji))."')";
      $lisa .= " and right(tapahtuma.selite, locate('>rb<', reverse(tapahtuma.selite))-1) in $sel_invenlaji";
    }

    if (is_array($mul_invaaja) and count($mul_invaaja) > 0) {
      $sel_invaaja = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_invaaja))."')";
      $lisa .= " and tapahtuma.laatija in $sel_invaaja";
    }

    if ($kaikkirivit != "" and $vararvoennen != "") {
      $select .= "tapahtuma.hinta 'vararvoennen', ";
    }

    // Jos ei olla valittu mitään
    if ($group == "") {
      $select = "tuote.yhtio, ";
      $group = "tuote.yhtio";
    }

    $query = "  SELECT $select";

    // Katotaan mistä kohtaa queryä alkaa varsinaiset numerosarakkeet (HUOM: toi ', ' pilkkuspace erottaa sarakket toisistaan)
    $data_start_index = substr_count($select, ", ");

    $query .= " sum(tapahtuma.kpl) kpl, round(sum(tapahtuma.kpl*tapahtuma.hinta),2) varastonmuutos ";

    // generoidaan selectit
    $query .= " FROM tuote
                JOIN tapahtuma ON  (tapahtuma.yhtio = tuote.yhtio
                          and tapahtuma.laji = 'inventointi'
                          and tapahtuma.tuoteno = tuote.tuoteno
                          and tapahtuma.laadittu >= '$vva-$kka-$ppa 00:00:00'
                          and tapahtuma.laadittu <= '$vvl-$kkl-$ppl 23:59:59')
                JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio
                  AND varastopaikat.tunnus = tapahtuma.varasto)
                LEFT JOIN kuka ON (tapahtuma.yhtio = kuka.yhtio
                          and tapahtuma.laatija = kuka.kuka)
                WHERE tuote.yhtio in ($yhtio)
                $lisa
                group by $group
                order by $order";

    // ja sitten ajetaan itte query
    if ($query != "") {

      $result = pupe_query($query);
      $elements = mysql_num_rows($result);
      $numfields = mysql_num_fields($result);

      $rivilimitti = 1000;

      if ($elements > $rivilimitti) {
        echo "<br><font class='error'>".t("Hakutulos oli liian suuri")."!</font><br>";
        echo "<font class='error'>".t("Tallenna/avaa tulos excelissä")."!</font><br><br>";
      }
    }

    if ($query != "") {
      include 'inc/pupeExcel.inc';

      $worksheet   = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi   = 0;

      echo "<table>";
      echo "<tr>
        <th>".t("Kausi nyt")."</th>
        <td>$ppa</td>
        <td>$kka</td>
        <td>$vva</td>
        <th>-</th>
        <td>$ppl</td>
        <td>$kkl</td>
        <td>$vvl</td>
        </tr>\n";
      echo "</table><br>";

      if ($elements <= $rivilimitti) echo "<table><tr>";

      // echotaan kenttien nimet
      for ($i=0; $i < $numfields; $i++) {
        if ($elements <= $rivilimitti) echo "<th>".t(mysql_field_name($result, $i))."</th>";
      }

      if (isset($worksheet)) {
        for ($i=0; $i < $numfields; $i++) $worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result, $i))), $format_bold);
        $excelrivi++;
      }

      if ($elements <= $rivilimitti) echo "</tr>\n";

      $edluku     = "x";
      $valisummat = array();
      $totsummat  = array();

      if ($elements > $rivilimitti) {
        require_once 'inc/ProgressBar.class.php';
        $bar = new ProgressBar();
        $bar->initialize($elements); // print the empty bar
      }

      while ($row = mysql_fetch_assoc($result)) {
        if ($elements > $rivilimitti) $bar->increase();
        if ($elements <= $rivilimitti) echo "<tr>";

        // echotaan kenttien sisältö
        for ($i=0; $i < $numfields; $i++) {
          $fieldname = mysql_field_name($result, $i);

          // jos kyseessa on tuote
          if ($fieldname == "tuoteno") {
            $row[$fieldname] = "<a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($row[$fieldname])."'>$row[$fieldname]</a>";
          }

          // jos kyseessa on tuoteosasto, haetaan sen nimi
          if ($fieldname == "osasto") {
            $osre = t_avainsana("OSASTO", "", "and avainsana.selite  = '$row[$fieldname]'", $yhtio);
            $osrow = mysql_fetch_array($osre);

            if ($osrow['selitetark'] != "" and $osrow['selite'] != $osrow['selitetark']) {
              $row[$fieldname] = $row[$fieldname] ." ". $osrow['selitetark'];
            }
          }

          // jos kyseessa on tuoteosasto, haetaan sen nimi
          if ($fieldname == "tuoteryhmä") {
            $osre = t_avainsana("TRY", "", "and avainsana.selite  = '$row[$fieldname]'", $yhtio);
            $osrow = mysql_fetch_array($osre);

            if ($osrow['selitetark'] != "" and $osrow['selite'] != $osrow['selitetark']) {
              $row[$fieldname] = $row[$fieldname] ." ". $osrow['selitetark'];
            }
          }

          // jos kyseessa on myyjä, haetaan sen nimi
          if ($fieldname == "tuotemyyjä") {
            $query = "SELECT nimi
                      FROM kuka
                      WHERE yhtio in ($yhtio)
                      and myyja   = '$row[$fieldname]'
                      AND myyja   > 0
                      limit 1";
            $osre = pupe_query($query);

            if (mysql_num_rows($osre) == 1) {
              $osrow = mysql_fetch_array($osre);
              $row[$fieldname] = $row[$fieldname] ." ". $osrow['nimi'];
            }
          }

          // jos kyseessa on ostaja, haetaan sen nimi
          if ($fieldname == "tuoteostaja") {
            $query = "SELECT nimi
                      FROM kuka
                      WHERE yhtio in ($yhtio)
                      and myyja   = '$row[$fieldname]'
                      AND myyja   > 0
                      limit 1";
            $osre = pupe_query($query);
            if (mysql_num_rows($osre) == 1) {
              $osrow = mysql_fetch_array($osre);
              $row[$fieldname] = $row[$fieldname] ." ". $osrow['nimi'];
            }
          }

          // kustannuspaikka
          if ($fieldname == "kustannuspaikka") {
            // näytetään soveltuvat kustannuspaikka
            $query = "SELECT nimi
                      FROM kustannuspaikka
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$row[$fieldname]'";
            $osre = pupe_query($query);

            if (mysql_num_rows($osre) == 1) {
              $osrow = mysql_fetch_array($osre);
              $row[$fieldname] = $osrow['nimi'];
            }
          }

          // Parseroidaan varastonarvo ennen invausta
          if ($fieldname == "vararvoennen") {
            preg_match("/ \(([0-9\.\-]*?)\) /", $row["selite"], $invkpl);

            $row[$fieldname] = round((float) $invkpl[1] * $row[$fieldname], 2);
          }

          // Jos gruupataan enemmän kuin yksi taso niin tehdään välisumma
          if ($gluku > 1 and $edluku != $row[mysql_field_name($result, 0)] and $edluku != 'x' and strpos($group, ',') !== FALSE and substr($group, 0, 13) != "tuote.tuoteno") {
            $excelsarake = $myyntiind = $kateind = $nettokateind = $myykplind = 0;

            foreach ($valisummat as $vnim => $vsum) {
              if ((string) $vsum != '') {
                $vsum = sprintf("%.2f", $vsum);
              }

              if ($elements <= $rivilimitti) echo "<td class='tumma' align='right'>$vsum</td>";

              if (isset($worksheet)) {
                $worksheet->writeNumber($excelrivi, $excelsarake, $vsum);
              }

              $excelsarake++;

            }
            $excelrivi++;
            if ($elements <= $rivilimitti) echo "</tr><tr>";

            $valisummat = array();
          }

          $edluku = $row[mysql_field_name($result, 0)];

          // hoidetaan pisteet pilkuiksi!!
          if (is_numeric($row[$fieldname]) and (mysql_field_type($result, $i) == 'real' or mysql_field_type($result, $i) == 'int' or substr($fieldname, 0 , 4) == 'kate')) {
            if ($elements <= $rivilimitti) echo "<td valign='top' align='right'>".sprintf("%.02f", $row[$fieldname])."</td>";

            if (isset($worksheet)) {
              $worksheet->writeNumber($excelrivi, $i, sprintf("%.02f", $row[$fieldname]));
            }
          }
          elseif ($fieldname == 'sarjanumero') {
            if ($elements <= $rivilimitti) echo "<td valign='top'>$row[$fieldname]</td>";

            if (isset($worksheet)) {
              $worksheet->writeString($excelrivi, $i, strip_tags(str_replace("<br>", "\n", $row[$fieldname])));
            }
          }
          else {
            if ($elements <= $rivilimitti) echo "<td valign='top'>$row[$fieldname]</td>";

            if (isset($worksheet)) {
              $worksheet->writeString($excelrivi, $i, strip_tags(str_replace("<br>", " / ", $row[$fieldname])));
            }
          }
        }

        if ($elements <= $rivilimitti) echo "</tr>\n";
        $excelrivi++;

        for ($i=0; $i < $numfields; $i++) {

          if ($i < substr_count($select, ", ")) {
            $valisummat[$fieldname] = "";
            $totsummat[$fieldname]  = "";
          }
          else {
            $valisummat[$fieldname] += $row[$fieldname];
            $totsummat[$fieldname]  += $row[$fieldname];
          }
        }
      }

      $apu = $numfields-11;

      // jos gruupataan enemmän kuin yksi taso niin tehdään välisumma
      if ($gluku > 1 and $mukaan != 'tuote') {

        if ($elements <= $rivilimitti) echo "<tr>";

        $excelsarake = $myyntiind = $kateind = $nettokateind = $myykplind = 0;

        foreach ($valisummat as $vnim => $vsum) {
          if ((string) $vsum != '') {
            $vsum = sprintf("%.2f", $vsum);
          }

          if ($elements <= $rivilimitti) echo "<td class='tumma' align='right'>$vsum</td>";

          if (isset($worksheet)) {
            $worksheet->writeNumber($excelrivi, $excelsarake, $vsum);
          }

          $excelsarake++;

        }
        $excelrivi++;
        if ($elements <= $rivilimitti) echo "</tr>";
      }

      if ($elements <= $rivilimitti) echo "<tr>";

      $excelsarake = $myyntiind = $kateind = $nettokateind = $myykplind = 0;

      foreach ($totsummat as $vnim => $vsum) {
        if ((string) $vsum != '') {
          $vsum = sprintf("%.2f", $vsum);
        }

        if ($elements <= $rivilimitti) echo "<td class='tumma' align='right'>$vsum</td>";

        if (isset($worksheet)) {
          $worksheet->writeNumber($excelrivi, $excelsarake, $vsum);
          $excelsarake++;
        }
      }
      $excelrivi++;

      if ($elements <= $rivilimitti) echo "</tr></table>";

      echo "<br>";

      if (isset($worksheet)) {

        $excelnimi = $worksheet->close();

        echo "<table>";
        echo "<tr><th>".t("Tallenna tulos").":</th>";
        echo "<form method='post' class='multisubmit'>";
        echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
        echo "<input type='hidden' name='kaunisnimi' value='Inventointiraportti.xlsx'>";
        echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
        echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
        echo "</table><br>";
      }
    }
    echo "<br><br><hr>";
  }
}

if ($lopetus == "") {

  //Käyttöliittymä
  if (!isset($kka)) $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
  if (!isset($vva)) $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
  if (!isset($ppa)) $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
  if (!isset($kkl)) $kkl = date("m");
  if (!isset($vvl)) $vvl = date("Y");
  if (!isset($ppl)) $ppl = date("d");
  if (!isset($yhtio)) $yhtio = "'$kukarow[yhtio]'";

  echo "<br>\n\n\n";
  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='go'>";

  $query = "SELECT *
            FROM yhtio
            WHERE $logistiikka_yhtiolisa";
  $result = pupe_query($query);

  // voidaan valita listaukseen useita konserniyhtiöitä, jos käyttäjällä on "PÄIVITYS" oikeus tähän raporttiin
  if (mysql_num_rows($result) > 0 and $oikeurow['paivitys'] != "") {
    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Valitse yhtiö")."</th>";

    if (!isset($yhtiot)) $yhtiot = array();

    while ($row = mysql_fetch_array($result)) {
      $sel = "";

      if ($kukarow["yhtio"] == $row["yhtio"] and count($yhtiot) == 0) $sel = "CHECKED";
      if (in_array($row["yhtio"], $yhtiot)) $sel = "CHECKED";

      echo "<td><input type='checkbox' name='yhtiot[]' onchange='submit()' value='$row[yhtio]' $sel>$row[nimi]</td>";
    }

    echo "</tr>";
    echo "</table><br>";
  }
  else {
    echo "<input type='hidden' name='yhtiot[]' value='$kukarow[yhtio]'>";
  }

  if ($ruksit[10]  != '')   $ruk10chk    = "CHECKED";
  if ($ruksit[20]  != '')   $ruk20chk    = "CHECKED";
  if ($ruksit[30]  != '')   $ruk30chk    = "CHECKED";
  if ($ruksit[40]  != '')   $ruk40chk    = "CHECKED";
  if ($ruksit[50]  != '')   $ruk50chk   = "CHECKED";
  if ($ruksit[60]  != '')   $ruk60chk   = "CHECKED";
  if ($ruksit[70]  != '')   $ruk70chk   = "CHECKED";
  if ($ruksit[80]  != '')   $ruk80chk   = "CHECKED";
  if ($ruksit[85]  != '')   $ruk85chk   = "CHECKED";

  echo "<table><tr>";
  echo "<th>".t("Valitse tuoteosastot").":</th>";
  echo "<th>".t("Valitse tuoteryhmät").":</th>";
  echo "<th>".t("Valitse tuotemerkit").":</th></tr>";

  echo "<tr>";
  echo "<td valign='top'>";

  // tehdään avainsana query
  $res2 = t_avainsana("OSASTO");

  echo "<select name='mul_osasto[]' multiple='TRUE' size='10' style='width:100%;'>";

  $mul_check = '';
  if ($mul_osasto!="") {
    if (in_array("PUPEKAIKKIMUUT", $mul_osasto)) {
      $mul_check = 'SELECTED';
    }
  }
  echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoteosastoa")."</option>";

  while ($rivi = mysql_fetch_array($res2)) {
    $mul_check = '';
    if ($mul_osasto!="") {
      if (in_array($rivi['selite'], $mul_osasto)) {
        $mul_check = 'SELECTED';
      }
    }

    echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
  }

  echo "</select>";

  echo "</td>";
  echo "<td valign='top'>";

  // tehdään avainsana query
  $res2 = t_avainsana("TRY");

  echo "<select name='mul_try[]' multiple='TRUE' size='10' style='width:100%;'>";

  $mul_check = '';
  if ($mul_try!="") {
    if (in_array("PUPEKAIKKIMUUT", $mul_try)) {
      $mul_check = 'SELECTED';
    }
  }
  echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuoteryhmää")."</option>";

  while ($rivi = mysql_fetch_array($res2)) {
    $mul_check = '';
    if ($mul_try!="") {
      if (in_array($rivi['selite'], $mul_try)) {
        $mul_check = 'SELECTED';
      }
    }

    echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
  }

  echo "</select>";
  echo "</td>";

  echo "<td valign='top'>";

  // tehdään avainsana query
  $res2 = t_avainsana("TUOTEMERKKI");

  echo "<select name='mul_tme[]' multiple='TRUE' size='10' style='width:100%;'>";

  $mul_check = '';
  if ($mul_tme!="") {
    if (in_array("PUPEKAIKKIMUUT", $mul_tme)) {
      $mul_check = 'SELECTED';
    }
  }
  echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei tuotemerkkiä")."</option>";

  while ($rivi = mysql_fetch_array($res2)) {
    $mul_check = '';
    if ($mul_tme!="") {
      if (in_array($rivi['selite'], $mul_tme)) {
        $mul_check = 'SELECTED';
      }
    }

    echo "<option value='$rivi[selite]' $mul_check>$rivi[selite]</option>";
  }

  echo "</select>";
  echo "</td>";


  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Prio").": <input type='text' name='jarjestys[10]' size='2' value='$jarjestys[10]'> ".t("Osastoittain")." <input type='checkbox' name='ruksit[10]' value='osasto' $ruk10chk></th>";
  echo "<th>".t("Prio").": <input type='text' name='jarjestys[20]' size='2' value='$jarjestys[20]'> ".t("Tuoteryhmittäin")." <input type='checkbox' name='ruksit[20]' value='tuoteryhma' $ruk20chk></th>";
  echo "<th>".t("Prio").": <input type='text' name='jarjestys[30]' size='2' value='$jarjestys[30]'> ".t("Tuotemerkeittäin")." <input type='checkbox' name='ruksit[30]' value='tuotemerkki' $ruk30chk></th></tr>";

  echo "</table><br>\n";


  echo "<table><tr>";
  echo "<th>".t("Valitse tuotemyyjät").":</th>";
  echo "<th>".t("Valitse tuoteostajat").":</th>";
  echo "<th>".t("Valitse kustannuspaikat").":</th></tr>";

  echo "<tr>";
  echo "<td valign='top'>";

  // tehdään query
  $query = "SELECT DISTINCT myyja, nimi
            FROM kuka
            WHERE yhtio = '$kukarow[yhtio]'
            AND myyja>0
            ORDER BY myyja";
  $sresult = pupe_query($query);

  echo "<select name='mul_tuotemyyja[]' multiple='TRUE' size='10' style='width:100%;'>";

  $mul_check = '';
  if ($mul_tuotemyyja!="") {
    if (in_array("PUPEKAIKKIMUUT", $mul_tuotemyyja)) {
      $mul_check = 'SELECTED';
    }
  }
  echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei valintaa")."</option>";

  while ($sxrow = mysql_fetch_array($sresult)) {
    $mul_check = '';

    if (count($mul_tuotemyyja) > 0) {
      if (in_array(trim($sxrow['myyja']), $mul_tuotemyyja)) {
        $mul_check = 'SELECTED';
      }
    }

    echo "<option value='$sxrow[myyja]' $mul_check>$sxrow[myyja] $sxrow[nimi]</option>";
  }
  echo "</select>";
  echo "</td>";

  echo "<td valign='top'>";

  $query = "SELECT distinct myyja, nimi
            FROM kuka
            WHERE yhtio='$kukarow[yhtio]'
            AND myyja>0
            ORDER BY myyja";
  $sresult = pupe_query($query);

  echo "<select name='mul_tuoteostaja[]' multiple='TRUE' size='10' style='width:100%;'>";

  $mul_check = '';
  if ($mul_tuoteostaja!="") {
    if (in_array("PUPEKAIKKIMUUT", $mul_tuoteostaja)) {
      $mul_check = 'SELECTED';
    }
  }
  echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei valintaa")."</option>";

  while ($sxrow = mysql_fetch_array($sresult)) {
    $mul_check = '';

    if (count($mul_tuoteostaja) > 0) {
      if (in_array(trim($sxrow['myyja']), $mul_tuoteostaja)) {
        $mul_check = 'SELECTED';
      }
    }

    echo "<option value='$sxrow[myyja]' $mul_check>$sxrow[myyja] $sxrow[nimi]</option>";
  }
  echo "</select>";
  echo "</td>";

  echo "<td valign='top'>";

  $query = "SELECT tunnus selite, nimi selitetark
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and kaytossa != 'E'
            and tyyppi    = 'K'
            ORDER BY koodi+0, koodi, nimi";
  $res2  = pupe_query($query);

  echo "<select name='mul_kustp[]' multiple='TRUE' size='10' style='width:100%;'>";

  $mul_check = '';
  if ($mul_kustp!="") {
    if (in_array("PUPEKAIKKIMUUT", $mul_kustp)) {
      $mul_check = 'SELECTED';
    }
  }
  echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei kustannuspaikkaa")."</option>";

  while ($rivi = mysql_fetch_array($res2)) {
    $mul_check = '';
    if ($mul_kustp!="") {
      if (in_array($rivi['selite'], $mul_kustp)) {
        $mul_check = 'SELECTED';
      }
    }

    echo "<option value='$rivi[selite]' $mul_check>$rivi[selitetark]</option>";
  }

  echo "</select>";

  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Prio").": <input type='text' name='jarjestys[40]' size='2' value='$jarjestys[40]'> ".t("Tuotemyyjittäin")." <input type='checkbox' name='ruksit[40]' value='tuotemyyja' $ruk40chk></th>";
  echo "<th>".t("Prio").": <input type='text' name='jarjestys[50]' size='2' value='$jarjestys[50]'> ".t("Tuoteostajittain")." <input type='checkbox' name='ruksit[50]' value='tuoteostaja' $ruk50chk></th>";
  echo "<th>".t("Prio").": <input type='text' name='jarjestys[60]' size='2' value='$jarjestys[60]'> ".t("Kustannuspaikoittain")." <input type='checkbox' name='ruksit[60]' value='kustannuspaikka' $ruk60chk></th></tr>";
  echo "</table><br>\n";

  echo "<table><tr>";
  echo "<th>".t("Valitse varastot").":</th>";
  echo "<th>".t("Valitse lajit").":</th>";
  echo "<th>".t("Valitse henkilöt").":</th></tr>";

  echo "<tr>";
  echo "<td valign='top'>";

  $query  = "SELECT tunnus, nimitys
             FROM varastopaikat
             WHERE yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
             ORDER BY tyyppi, nimitys";
  $vares = pupe_query($query);

  echo "<select name='mul_varastot[]' multiple='TRUE' size='10' style='width:100%;'>";

  while ($varow = mysql_fetch_array($vares)) {
    $mul_check = '';
    if ($mul_varastot!="") {
      if (in_array($varow['tunnus'], $mul_varastot)) {
        $mul_check = 'SELECTED';
      }
    }

    echo "<option value='$varow[tunnus]' $mul_check>$varow[nimitys]</option>";
  }

  echo "</select>";
  echo "</td>";

  echo "<td valign='top'>";

  // tehdään avainsana query
  $res2 = t_avainsana("INVEN_LAJI");

  echo "<select name='mul_invenlaji[]' multiple='TRUE' size='10' style='width:100%;'>";

  $mul_check = '';
  if ($mul_invenlaji!="") {
    if (in_array("PUPEKAIKKIMUUT", $mul_invenlaji)) {
      $mul_check = 'SELECTED';
    }
  }
  echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei inventointlajia")."</option>";

  while ($rivi = mysql_fetch_array($res2)) {
    $mul_check = '';
    if ($mul_invenlaji!="") {
      if (in_array($rivi['selite'], $mul_invenlaji)) {
        $mul_check = 'SELECTED';
      }
    }

    echo "<option value='$rivi[selite]' $mul_check>$rivi[selite]</option>";
  }

  echo "</select>";
  echo "</td>";

  echo "<td valign='top'>";

  $query = "SELECT kuka, nimi
            FROM kuka
            WHERE yhtio    = '$kukarow[yhtio]'
            AND aktiivinen = 1
            and extranet   = ''
            ORDER BY nimi";
  $sresult = pupe_query($query);

  echo "<select name='mul_invaaja[]' multiple='TRUE' size='10' style='width:100%;'>";

  $mul_check = '';
  if ($mul_invaaja != "") {
    if (in_array("PUPEKAIKKIMUUT", $mul_invaaja)) {
      $mul_check = 'SELECTED';
    }
  }
  echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei valintaa")."</option>";

  while ($sxrow = mysql_fetch_array($sresult)) {
    $mul_check = '';

    if (count($mul_invaaja) > 0) {
      if (in_array(trim($sxrow['myyja']), $mul_invaaja)) {
        $mul_check = 'SELECTED';
      }
    }

    echo "<option value='$sxrow[kuka]' $mul_check>$sxrow[nimi]</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Prio").": <input type='text' name='jarjestys[70]' size='2' value='$jarjestys[70]'> ".t("Varastoittain")." <input type='checkbox' name='ruksit[70]' value='varasto' $ruk70chk></th>";
  echo "<th>".t("Prio").": <input type='text' name='jarjestys[80]' size='2' value='$jarjestys[80]'> ".t("Lajeittain")." <input type='checkbox' name='ruksit[80]' value='invenlaji' $ruk80chk></th>";
  echo "<th>".t("Prio").": <input type='text' name='jarjestys[85]' size='2' value='$jarjestys[85]'> ".t("Henkilöittäin")." <input type='checkbox' name='ruksit[85]' value='invaaja' $ruk85chk></th></tr>";

  echo "</table><br>\n";

  // lisärajaukset näkymä..
  if ($ruksit[90]  != '') $ruk90chk       = "CHECKED";
  if ($nimitykset != '')  $nimchk         = "CHECKED";
  if ($kaikkirivit != '')  $kaikkirivitchk  = "CHECKED";
  if ($vararvoennen != '')  $vararvoennenchk  = "CHECKED";

  echo "<table>
    <tr>
    <th>".t("Lisärajaus")."</th>
    <th>".t("Prio")."</th>
    <th> x</th>
    <th>".t("Rajaus")."</th>
    </tr>
    <tr>
    <th>".t("Listaa tuotteittain")."</th>
    <td><input type='text' name='jarjestys[90]' size='2' value='$jarjestys[90]'></td>
    <td><input type='checkbox' name='ruksit[90]' value='tuote' $ruk90chk></td>
    <td><input type='text' name='rajaus[90]' value='$rajaus[90]'></td>
    </tr>
    <tr>
    <td class='back'><br></td>
    </tr>
    <tr>
    <th>".t("Näytä tuotteiden nimitykset")."</th>
    <td><input type='checkbox' name='nimitykset' $nimchk></td>
    <td></td>
    <td class='back'>".t("(Toimii vain jos listaat tuotteittain)")."</td>
    </tr>
    <tr>
    <th>".t("Näytä jokainen inventointi ja sen selite")."</th>
    <td><input type='checkbox' name='kaikkirivit' $kaikkirivitchk></td>
    <td></td>
    <td class='back'></td>
    </tr>
    <tr>
    <th>".t("Näytä varastonarvo ennen inventointia")."</th>
    <td><input type='checkbox' name='vararvoennen' $vararvoennenchk></td>
    <td></td>
    <td class='back'>".t("(Toimii vain kun listataan jokainen inventointi ja sen selite)")."</td>
    </tr>";
  echo "</table><br>";

  // päivämäärärajaus
  echo "<table>";
  echo "<tr>
    <th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppa' value='$ppa' size='3'></td>
    <td><input type='text' name='kka' value='$kka' size='3'></td>
    <td><input type='text' name='vva' value='$vva' size='5'></td>
    </tr>\n
    <tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppl' value='$ppl' size='3'></td>
    <td><input type='text' name='kkl' value='$kkl' size='3'></td>
    <td><input type='text' name='vvl' value='$vvl' size='5'></td>
    </tr>\n";
  echo "</table><br>";

  echo "<br>";
  echo "<input type='submit' value='".t("Aja raportti")."'>";
  echo "</form>";
}

require "../inc/footer.inc";
