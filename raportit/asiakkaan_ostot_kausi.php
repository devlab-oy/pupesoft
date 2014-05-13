<?php

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require ("../inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

echo "<font class='head'>".t("Asiakkaan/Osaston ostot annetulta kaudelta")."</font><hr>";

// hehe, näin on helpompi verrata päivämääriä
$query  = "SELECT TO_DAYS('$vvl-$kkl-$ppl')-TO_DAYS('$vva-$kka-$ppa') ero";
$result = pupe_query($query);
$row    = mysql_fetch_array($result);

if ($row["ero"] > 366) {
  echo "<font class='error'>".t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentaväli!")."</font><br>";
  echo t("Annetut rajaukset").": $ytunnus $asosasto $apvm $lpvm<br>";
  $tee = '';
}

if ($tee == 'go') {

  if (isset($muutparametrit)) {
    list($vva,$kka,$ppa,$vvl,$kkl,$ppl) = explode('#', $muutparametrit);
  }

  $muutparametrit = $vva."#".$kka."#".$ppa."#".$vvl."#".$kkl."#".$ppl."#";

  $evva = $vva-1;
  $evvl  = $vvl-1;
  $eapvm = $evva."-".$kka."-".$ppa;
  $elpvm = $evvl."-".$kkl."-".$ppl;

  $apvm = $vva."-".$kka."-".$ppa;
  $lpvm = $vvl."-".$kkl."-".$ppl;

  $ok = '';

  if ($ytunnus != '') {
    if ($asosasto == '') {
      require ("inc/asiakashaku.inc");
    }
    $ok++;
  }

  if ($asosasto != '') {
    $ok++;
  }

  if ($ok != 1) {
    echo "<br><br><font class='error'>".t("VIRHE: Valitse asiakas tai asiakasosasto")."!</font><br><br><br>";
  }
  elseif ($asiakasid != '') {
    $query = "  SELECT t.tuoteno, t.nimitys,
          sum(if(t.laskutettuaika >= '$apvm'  and t.laskutettuaika <= '$lpvm',  t.rivihinta,0)) summa,
          sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm', t.rivihinta,0)) edsumma,
          sum(if(t.laskutettuaika >= '$apvm'  and t.laskutettuaika <= '$lpvm',  t.kate,0)) kate,
          sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm', t.kate,0)) edkate,
          sum(if(t.laskutettuaika >= '$apvm'  and t.laskutettuaika <= '$lpvm',  t.kpl,0)) kpl,
          sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm', t.kpl,0)) edkpl
          FROM lasku l use index (yhtio_tila_liitostunnus_tapvm)
          JOIN tilausrivi t ON ( l.yhtio = t.yhtio and l.tunnus = t.uusiotunnus)
          WHERE l.yhtio = '$kukarow[yhtio]'
          AND l.tila = 'u'
          AND l.alatila = 'x'
          AND l.liitostunnus = '$asiakasid'
          AND ((l.tapvm >= '$apvm' and l.tapvm <= '$lpvm') or (l.tapvm >= '$eapvm' and l.tapvm <= '$elpvm'))
          GROUP BY 1,2
          ORDER BY 1";
  }
  elseif ($asosasto != '') {
    $query = "  SELECT t.tuoteno, t.nimitys,
          sum(if(t.laskutettuaika >= '$apvm'  and t.laskutettuaika <= '$lpvm',  t.rivihinta,0)) summa,
          sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm', t.rivihinta,0)) edsumma,
          sum(if(t.laskutettuaika >= '$apvm'  and t.laskutettuaika <= '$lpvm',  t.kate,0)) kate,
          sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm', t.kate,0)) edkate,
          sum(if(t.laskutettuaika >= '$apvm'  and t.laskutettuaika <= '$lpvm',  t.kpl,0)) kpl,
          sum(if(t.laskutettuaika >= '$eapvm' and t.laskutettuaika <= '$elpvm', t.kpl,0)) edkpl
          FROM asiakas a use index (yhtio_osasto_ryhma)
          JOIN lasku l use index (yhtio_tila_liitostunnus_tapvm) ON (a.yhtio = l.yhtio AND l.liitostunnus = a.tunnus and l.tila = 'u' and l.alatila = 'x' and ((l.tapvm >= '$apvm' and l.tapvm <= '$lpvm') or (l.tapvm >= '$eapvm' and l.tapvm <= '$elpvm')))
          JOIN tilausrivi t ON (l.yhtio = t.yhtio and l.tunnus = t.uusiotunnus)
          WHERE a.yhtio = '$kukarow[yhtio]'
          and a.osasto = '$asosasto'
          group by 1,2
          order by 1";
  }

  if ($ok == 1 and ($asiakasid != '' or $asosasto != '')) {
    $result = pupe_query($query);

    include('inc/pupeExcel.inc');

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi   = 0;

    echo "<table><tr><th colspan='2'>".t("Annetut rajaukset")."</th></tr>";
    echo "<tr><th>".t("Ytunnus")."</th><td>$ytunnus</td></tr>";
    echo "<tr><th>".t("Asiakasosasto")."</th><td>$asosasto</td></tr>";
    echo "<tr><th>".t("Alkupäivämäärä")."</th><td>$apvm</td></tr>";
    echo "<tr><th>".t("Loppupäivämäärä")."</th><td>$lpvm</td></tr>";
    echo "</table><br>";

    echo "<table><tr>";
    echo "<th>".t("Tuoteno")."</th>";
    echo "<th>".t("Nimitys")."</th>";
    echo "<th>".t("Summa")."</th>";
    echo "<th>".t("Kate")."</th>";
    echo "<th>".t("Määrä")."</th>";
    echo "<th>".t("Ed.Summa")."</th>";
    echo "<th>".t("Ed.Kate")."</th>";
    echo "<th>".t("Ed.Määrä")."</th>";
    echo "</tr>";

    $worksheet->write($excelrivi, 0, t("Tuoteno"), $format_bold);
    $worksheet->write($excelrivi, 1, t("Nimitys"), $format_bold);
    $worksheet->write($excelrivi, 2, t("Summa"), $format_bold);
    $worksheet->write($excelrivi, 3, t("Kate"), $format_bold);
    $worksheet->write($excelrivi, 4, t("Määrä"), $format_bold);
    $worksheet->write($excelrivi, 5, t("Ed.Summa"), $format_bold);
    $worksheet->write($excelrivi, 6, t("Ed.Kate"), $format_bold);
    $worksheet->write($excelrivi, 7, t("Ed.Määrä"), $format_bold);
    $excelrivi++;

    while ($row = mysql_fetch_assoc($result)) {
      echo "<tr>";
      echo "<td>$row[tuoteno]</td>";
      echo "<td>".t_tuotteen_avainsanat($row, 'nimitys')."</th>";
      echo "<td>".$row['summa']."</th>";
      echo "<td>".$row['kate']."</th>";
      echo "<td>".$row['kpl']."</th>";
      echo "<td>".$row['edsumma']."</th>";
      echo "<td>".$row['edkate']."</th>";
      echo "<td>".$row['edkpl']."</th>";
      echo "</tr>";

      $worksheet->write($excelrivi, 0, $row['tuoteno']);
      $worksheet->write($excelrivi, 1, t_tuotteen_avainsanat($row, 'nimitys'));
      $worksheet->writeNumber($excelrivi, 2, $row['summa']);
      $worksheet->writeNumber($excelrivi, 3, $row['kate']);
      $worksheet->writeNumber($excelrivi, 4, $row['kpl']);
      $worksheet->writeNumber($excelrivi, 5, $row['edsumma']);
      $worksheet->writeNumber($excelrivi, 6, $row['edkate']);
      $worksheet->writeNumber($excelrivi, 7, $row['edkpl']);
      $excelrivi++;
    }
    echo "</table><br>";

    $excelnimi = $worksheet->close();

    echo "<br><br><table>";
    echo "<tr><th>".t("Tallenna tulos").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Asiakkaan_ostot.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
    echo "</table><br>";
  }
}

echo "<table><form name='piiri' method='post'>";

if (!isset($kka))
  $kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva))
  $vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa))
  $ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

if (!isset($kkl))
  $kkl = date("m");
if (!isset($vvl))
  $vvl = date("Y");
if (!isset($ppl))
  $ppl = date("d");

echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
  <td><input type='text' name='ppa' value='$ppa' size='3'></td>
  <td><input type='text' name='kka' value='$kka' size='3'></td>
  <td><input type='text' name='vva' value='$vva' size='5'></td>
  </tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
  <td><input type='text' name='ppl' value='$ppl' size='3'></td>
  <td><input type='text' name='kkl' value='$kkl' size='3'></td>
  <td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

$query = "  SELECT distinct if(osasto = '','TYHJÄ', osasto) osasto
      FROM asiakas
      WHERE yhtio = '$kukarow[yhtio]'
      ORDER BY 1";
$sresult = pupe_query($query);

$ulos2 = "<select name='asosasto'>";
$ulos2 .= "<option value=''>".t("Osasto")."</option>";

while ($srow = mysql_fetch_assoc($sresult)) {
  $sel = '';
  if ($asosasto == $srow['osasto']) {
    $sel = "selected";
  }
  $ulos2 .= "<option value='$srow[osasto]' $sel>$srow[osasto]</option>";
}
$ulos2 .= "</select>";

echo "<tr><th>".t("Valitse osasto")."</th>
    <td colspan='3'>$ulos2</td>
    <tr><th>".t("tai syötä ytunnus").":</th>
    <td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='15'></td></tr>
    <input type='hidden' name='tee' value='go' size='15'>
  </table>";

echo "<br><input type='submit' value='".t("Aja raportti")."'>";
echo "</form>";

require ("inc/footer.inc");
