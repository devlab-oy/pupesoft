<?php

/*
 * functions.php
 *
 * Sis‰lt‰‰ entist‰ koodia, jota on jaettu pienempiin funktioihin.
 * Tarvitaan, jotta tietyt toiminnot katelaskennan haku osiossa toimii.
 * Varmuutta ei ole mit‰ kaikkea n‰m‰ tekev‰t ja mit‰ ei tarvita.
 * Siistit‰‰n tiedostoa kun tulee tarpeellisesti.
 *
 * Katelaskennan omat funktiot ovat functions.katelaskenta.php -tiedostossa.
 */

/**
 * Funktio valmistelee hakutulokset templatea varten.
 *
 * Palauttaa muokatun hakutulostaulukon.
 *
 * @param type    $tuotteet
 */
function valmistele_hakutulokset($tuotteet) {
  foreach ($tuotteet as $avain => $arvo) { // $rows muuttuja tulee templaten ulkopuolelta
    // Merkit‰‰n nimitykseen "poistuva"
    if (strtoupper($arvo["status"]) == "P") {
      $tuotteet[$avain]["nimitys"] .= "<br> * " . t("Poistuva tuote");
    }

    $tuotteet[$avain]["myyntihinta"] = hintapyoristys($arvo["myyntihinta"], 2);
    $tuotteet[$avain]["myymalahinta"] = hintapyoristys($arvo["myymalahinta"], 2);
    $tuotteet[$avain]["nettohinta"] = hintapyoristys($arvo["nettohinta"], 2);
    $tuotteet[$avain]["kehahin"] = hintapyoristys($arvo["kehahin"], 2);
  }

  return $tuotteet;
}

/**
 * Funktio hakee ja piirt‰‰ tuotteen saldon.
 *
 * Aivan sama kuin tuote_selaus_haku.php tiedostossa. Koska funktio myˆs
 * echottelee html -koodia, on siit‰ vaikea saada yht‰ arvoa takaisin.
 *
 * REFACTOR: Funktio kaipaa refaktorointia, jotta sit‰ voitaisiin k‰ytt‰‰ molemmissa
 * tiedostoissa.
 *
 * @global type $kukarow
 * @global type $yhtiorow
 * @global type $lisatiedot
 * @param type $row
 */
function hae_ja_piirra_saldo($row) {
  global $kukarow, $yhtiorow, $lisatiedot;

  // Saldottomat tuotteet
  if ($row['ei_saldoa'] != '') {
    echo "<td valign='top'><font class='green'>" . t("Saldoton") . "</font></td>";
  }
  // Sarjanumerolliset tuotteet ja sarjanumerolliset is‰t
  elseif ($row["sarjanumeroseuranta"] == "S") {
    echo "<td valign='top'><a onClick=\"javascript:sarjanumeronlisatiedot_popup('$row[sarjatunnus]')\">$row[sarjanumero]</a>";
    echo "</td>";

    if ($lisatiedot != "") {
      echo "<td></td>";
    }
  }
  // Normaalit saldolliset tuotteet (Normi)
  else {
    // K‰yd‰‰n l‰pi tuotepaikat
    if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
      $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                        tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                        sarjanumeroseuranta.sarjanumero era,
                        concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
                        varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                         FROM tuote
                        JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
                        JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                        AND varastopaikat.tunnus                  = tuotepaikat.varasto)
                        JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
                        AND sarjanumeroseuranta.tuoteno           = tuote.tuoteno
                        AND sarjanumeroseuranta.hyllyalue         = tuotepaikat.hyllyalue
                        AND sarjanumeroseuranta.hyllynro          = tuotepaikat.hyllynro
                        AND sarjanumeroseuranta.hyllyvali         = tuotepaikat.hyllyvali
                        AND sarjanumeroseuranta.hyllytaso         = tuotepaikat.hyllytaso
                        AND sarjanumeroseuranta.myyntirivitunnus  = 0
                        AND sarjanumeroseuranta.era_kpl          != 0
                        WHERE tuote.yhtio                         = '$kukarow[yhtio]'
                        and tuote.tuoteno                         = '$row[tuoteno]'
                        GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
                        ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
    }
    else {
      $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                        tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                        concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
                        varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
                        FROM tuote
                        JOIN tuotepaikat ON (tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno)
                        JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                        AND varastopaikat.tunnus = tuotepaikat.varasto)
                        WHERE tuote.yhtio        = '$kukarow[yhtio]'
                        AND tuote.tuoteno        = '$row[tuoteno]'
                        ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
    }
    $varresult = pupe_query($query);

    echo "<td valign='top'>";
    echo "<table style='width:100%;'>";

    $loytyko = false;
    $loytyko_normivarastosta = false;
    $myytavissa_sum = 0;

    if (mysql_num_rows($varresult) > 0) {
      $hyllylisa = "";

      // katotaan jos meill‰ on tuotteita varaamassa saldoa joiden varastopaikkaa ei en‰‰ ole olemassa...
      list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($row["tuoteno"], 'ORVOT', '', '', '', '', '', '', '');
      $orvot *= -1;

      while ($saldorow = mysql_fetch_assoc($varresult)) {

        if (!isset($saldorow["era"]))
          $saldorow["era"] = "";

        list($saldo, $hyllyssa, $myytavissa, $sallittu) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], "", $saldorow["era"]);

        //  Listataan vain varasto jo se ei ole kielletty
        if ($sallittu === true) {
          // hoidetaan pois problematiikka jos meill‰ on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
          if ($orvot > 0) {
            if ($myytavissa >= $orvot and $saldorow["yhtio"] == $kukarow["yhtio"]) {
              // poistaan orpojen varaamat tuotteet t‰lt‰ paikalta
              $myytavissa = $myytavissa - $orvot;
              $orvot = 0;
            }
            elseif ($orvot > $myytavissa and $saldorow["yhtio"] == $kukarow["yhtio"]) {
              // poistetaan niin paljon orpojen saldoa ku voidaan
              $orvot = $orvot - $myytavissa;
              $myytavissa = 0;
            }
          }

          if ($myytavissa != 0 or ( $lisatiedot != "" and $hyllyssa != 0)) {
            $id2 = md5(uniqid());

            echo "<tr>";
            echo "<td nowrap>";
            echo "<a class='tooltip' id='$id2'>$saldorow[nimitys]</a> $saldorow[tyyppi]";
            echo "<div id='div_$id2' class='popup' style='width: 300px'>($saldorow[hyllyalue]-$saldorow[hyllynro]-$saldorow[hyllyvali]-$saldorow[hyllytaso])</div>";
            echo "</td>";

            echo "<td align='right' nowrap>";
            echo sprintf("%.2f", $myytavissa);
            echo "</td></tr>";
          }

          if ($myytavissa > 0) {
            $loytyko = true;
          }

          if ($myytavissa > 0 and $saldorow["varastotyyppi"] != "E") {
            $loytyko_normivarastosta = true;
          }

          if ($lisatiedot != "" and $hyllyssa != 0) {
            $hyllylisa .= "  <tr class='aktiivi'>
                        <td align='right' nowrap>" . sprintf("%.2f", $hyllyssa) . "</td>
                        </tr>";
          }

          if ($saldorow["tyyppi"] != "E") {
            $myytavissa_sum += $myytavissa;
          }
        }
      }
    }

    $tulossalisat = hae_tuotteen_saapumisaika($row['tuoteno'], $row['status'], $myytavissa_sum, $loytyko, $loytyko_normivarastosta);

    foreach ($tulossalisat as $tulossalisa) {
      list($o, $v) = explode("!°!", $tulossalisa);
      echo "<tr><td>$o</td><td>$v</td></tr>";
    }

    echo "</table></td>";

    if ($lisatiedot != "") {
      echo "<td valign='top'>";

      if (mysql_num_rows($varresult) > 0 and $hyllylisa != "") {

        echo "<table width='100%'>";
        echo "$hyllylisa";
        echo "</table></td>";
      }
      echo "</td>";
    }
  }
}
