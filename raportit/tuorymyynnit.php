<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

require "../inc/parametrit.inc";

if (!isset($try)) $try = '';
else $try = trim($try);

if (!isset($ytunnus)) $ytunnus = '';
else $ytunnus = trim($ytunnus);

if (!isset($osasto)) $osasto = '';
else $osasto = trim($osasto);

echo "<font class='head'>",t("Asiakkaan ostot tuoteryhm‰st‰"),"</font><hr>";

if (empty($try) or empty($ytunnus)) {

  echo "<form name=asiakas method='post' autocomplete='off'>";
  echo "<table><tr>";
  echo "<th>",t("Anna asiakasnumero tai osa nimest‰"),"</th>";
  echo "<td><input type='text' name='ytunnus' value='{$ytunnus}'></td>";
  echo "<td class='back'></td>";
  echo "</tr><tr>";
  echo "<th>",t("Anna osasto"),"</th>";
  echo "<td><input type='text' name='osasto' value='{$osasto}'></td>";
  echo "<td class='back'></td>";
  echo "</tr><tr>";
  echo "<th>",t("Anna tuoteryhm‰"),"</th>";
  echo "<td><input type='text' name='try' value='{$try}'></td>";
  echo "<td class='back'><input type='submit' value='",t("Hae"),"'></td>";
  echo "</tr></table>";
  echo "</form>";
}

if (!empty($ytunnus)) {
  require "inc/asiakashaku.inc";
}

// jos meill‰ on onnistuneesti valittu asiakas
if (!empty($ytunnus) or !empty($try) or !empty($osasto)) {

  echo "<br />";
  echo "<table><tr>";
  echo "<th>",t("ytunnus"),"</th>";
  echo "<th>",t("asnro"),"</th>";
  echo "<th>",t("nimi"),"</th>";
  echo "<th colspan='3'>",t("osoite"),"</th>";
  echo "</tr><tr>";
  echo "<td>{$asiakasrow['ytunnus']}</td>";
  echo "<td>{$asiakasrow['asiakasnro']}</td>";
  echo "<td>{$asiakasrow['nimi']}<br />{$asiakasrow['toim_nimi']}</td>";
  echo "<td>{$asiakasrow['osoite']}<br />{$asiakasrow['toim_osoite']}</td>";
  echo "<td>{$asiakasrow['postino']}<br />{$asiakasrow['toim_postino']}</td>";
  echo "<td>{$asiakasrow['postitp']}<br />{$asiakasrow['toim_postitp']}</td>";
  echo "</tr></table>";

  // hardcoodataan v‰rej‰
  $cmyynti = "#ccccff";
  $ckate   = "#ff9955";
  $ckatepr = "#00dd00";
  $maxcol  = 12; // montako columnia n‰yttˆ on

  $_katteet_tilauksella = $kukarow["naytetaan_katteet_tilauksella"];
  $katteet_naytetaan = (in_array($_katteet_tilauksella, array('Y',''))) ? true : false;

  // tehd‰‰n asiakkaan ostot tuoteryhm‰st‰
  echo "<br />";
  echo "<font class='message'>";

  if (!empty($osasto)) {
    echo t("Osasto")," {$osasto} ";
  }

  if (!empty($try)) {
    echo t("tuoteryhm‰")," {$try} ";
  }

  echo t("myynti kausittain viimeiset 24 kk")," (<font color='{$cmyynti}'>",t("myynti"),"</font>";

  if ($katteet_naytetaan) echo "/<font color='{$ckate}'>",t("kate"),"</font>";
  if ($katteet_naytetaan) echo "/<font color='{$ckatepr}'>",t("kateprosentti"),"</font>";

  echo ")</font><hr>";

  // alkukuukauden tiedot 24 kk sitten
  $ayy = date("Y-m-01", mktime(0, 0, 0, date("m")-24, date("d"), date("Y")));

  $wherelisa = "";

  if (!empty($osasto)) {
    $wherelisa .= "AND tilausrivi.osasto = '{$osasto}'";
  }

  if (!empty($try)) {
    $wherelisa .= "AND tilausrivi.try = '{$try}'";
  }

  if (!empty($asiakasid)) {
    $wherelisa .= "AND lasku.liitostunnus = '{$asiakasid}'";
  }

  $query  = "SELECT
             date_format(lasku.tapvm,'%Y/%m') kausi,
             round(sum(rivihinta),0) myynti,
             round(sum(tilausrivi.kate),0) kate,
             round(sum(tilausrivi.kpl),0) kpl,
             round(sum(tilausrivi.kate)/sum(rivihinta)*100,1) katepro
             FROM lasku use index (yhtio_tila_liitostunnus_tapvm), tilausrivi use index (uusiotunnus_index)
             WHERE lasku.yhtio          = '{$kukarow['yhtio']}'
             AND lasku.tila             = 'U'
             AND lasku.alatila          = 'X'
             AND lasku.tapvm            >= '{$ayy}'
             AND tilausrivi.uusiotunnus = lasku.tunnus
             AND tilausrivi.yhtio       = lasku.yhtio
             {$wherelisa}
             GROUP BY 1
             HAVING myynti <> 0 OR kate <> 0";
  $result = pupe_query($query);

  // otetaan suurin myynti talteen
  $maxeur=0;

  while ($sumrow = mysql_fetch_assoc($result)) {
    if ($sumrow['myynti'] > $maxeur) $maxeur = $sumrow['myynti'];
    if ($katteet_naytetaan and $sumrow['kate'] > $maxeur) $maxeur = $sumrow['kate'];
  }

  // ja kelataan resultti alkuun
  if (mysql_num_rows($result) > 0) mysql_data_seek($result, 0);

  $col = 1;

  echo "<table>\n";

  while ($sumrow = mysql_fetch_assoc($result)) {

    if ($col == 1) echo "<tr>\n";

    // lasketaan pylv‰iden korkeus
    if ($maxeur > 0) {

      $hmyynti  = round(50 * $sumrow['myynti'] / $maxeur, 0);
      $hkate    = round(50 * $sumrow['kate'] / $maxeur, 0);
      $hkatepro = round($sumrow['katepro'] / 2, 0);

      if ($hkatepro > 60) $hkatepro = 60;
    }
    else {
      $hmyynti = $hkate = $hkatepro = 0;
    }

    $_img = array(
      'src' => '../pics/blue.png',
      'height' => $hmyynti,
      'width' => 12,
      'alt' => t("myynti")." {$sumrow['myynti']} {$yhtiorow['valkoodi']}",
    );

    $_style = "padding:0px;margin:0px;vertical-align:bottom;";

    $pylvaat  = "<table style='padding:0px;margin:0px;'>";
    $pylvaat .= "<tr>";
    $pylvaat .= "<td style='{$_style}'>";

    $pylvaat .= "<img ";

    foreach ($_img as $_key => $_value) {
      $pylvaat .= "{$_key}='{$_value}'";
    }

    $pylvaat .= "/>";

    $pylvaat .= "</td>";

    if ($katteet_naytetaan) {
      $pylvaat .= "<td style='{$_style}'>";

      $_img['src'] = '../pics/orange.png';
      $_img['height'] = $hkate;
      $_img['alt'] = t("kate")." {$sumrow['kate']} {$yhtiorow['valkoodi']}";

      $pylvaat .= "<img ";

      foreach ($_img as $_key => $_value) {
        $pylvaat .= "{$_key}='{$_value}'";
      }

      $pylvaat .= "/>";
      $pylvaat .= "</td>";
    }

    if ($katteet_naytetaan) {
      $pylvaat .= "<td style='{$_style}'>";

      $_img['src'] = '../pics/green.png';
      $_img['height'] = $hkatepro;
      $_img['alt'] = t("kateprosentti")." {$sumrow['katepro']} %";

      $pylvaat .= "<img ";

      foreach ($_img as $_key => $_value) {
        $pylvaat .= "{$_key}='{$_value}'";
      }

      $pylvaat .= "/>";
      $pylvaat .= "</td>";
    }

    $pylvaat .= "</tr></table>";

    if ($sumrow['katepro'] == '') $sumrow['katepro'] = '0.0';

    echo "<td valign='bottom' class='back'>";

    $_style = "padding:0px;margin:0px;vertical-align:bottom;height:55px;";

    echo "<table width='60'>";
    echo "<tr><td nowrap align='center' style='{$_style}'>{$pylvaat}</td></tr>";
    echo "<tr><td nowrap align='right'><font class='info'>{$sumrow['kausi']}</font></td></tr>";

    $_myynti = "{$sumrow['myynti']} {$yhtiorow['valkoodi']}";
    echo "<tr>";
    echo "<td nowrap align='right'><font class='info'>{$_myynti}</font></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td nowrap align='right'><font class='info'>{$sumrow['kpl']} ",t("kpl"),"</font></td>";
    echo "</tr>";

    if ($katteet_naytetaan) {
      echo "<tr>";
      echo "<td nowrap align='right'>";
      echo "<font class='info'>{$sumrow['kate']} {$yhtiorow['valkoodi']}</font>";
      echo "</td>";
      echo "</tr>";
    }

    if ($katteet_naytetaan) {
      echo "<tr>";
      echo "<td nowrap align='right'>";
      echo "<font class='info'>{$sumrow['katepro']} %</font>";
      echo "</td>";
      echo "</tr>";
    }

    echo "</table>";

    echo "</td>\n";

    if ($col == $maxcol) {
      echo "</tr>\n";
      $col = 0;
    }

    $col++;
  }

  // teh‰‰n validia htmll‰‰ ja t‰ytet‰‰n tyhj‰t solut..
  $ero = $maxcol + 1 - $col;

  if ($ero <> $maxcol) echo "<td colspan='{$ero}' class='back'></td></tr>\n";

  echo "</table>";

  $lopetus  = "{$palvelin2}raportit/tuorymyynnit.php////";
  $lopetus .= "ytunnus={$ytunnus}//";
  $lopetus .= "osasto={$osasto}//";
  $lopetus .= "try={$try}";

  echo "<br />";
  echo "<form action='asiakasinfo.php?lopetus={$lopetus}' method='post'>";
  echo "<input type='hidden' name='ytunnus' value='{$ytunnus}'>";
  echo "<input type='hidden' name='asiakasid' value='{$asiakasid}'>";
  echo "<input type='submit' value='",t("Asiakkaan perustiedot"),"'>";
}

// kursorinohjausta
$formi  = "asiakas";
$kentta = "ytunnus";

require "inc/footer.inc";
