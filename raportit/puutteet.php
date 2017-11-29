<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require '../inc/parametrit.inc';

if ($tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

if (isset($muutparametrit) and $muutparametrit != '') {
  $muut = explode('/', $muutparametrit);

  $vva        = $muut[0];
  $kka        = $muut[1];
  $ppa        = $muut[2];
  $vvl        = $muut[3];
  $kkl       = $muut[4];
  $ppl        = $muut[5];
  $raportointitaso = $muut[6];
  $rivit       = $muut[7];
}

if ($ytunnus != '' or (int) $asiakasid > 0) {

  $muutparametrit = $vva."/".$kka."/".$ppa."/".$vvl."/".$kkl."/".$ppl."/".$raportointitaso."/".$rivit;

  require "inc/asiakashaku.inc";

  if ($ytunnus == '') {
    $tee = "";
  }
}

echo "<font class='head'>".t("Puutelistaus")."</font><hr>";

if ($tee != '') {

  $lisaasiakas   = "";
  $sellisa     = "";
  $rivilisa     = "";

  if ($alatila == "T") {
    $_grouplisa = ",lasku.liitostunnus";
    $_orderlisa = ",asiakas.asiakasnro";
    $_asiakasrajausinfo = $asiakasrow['asiakasnro'];
  }
  else {
    $_grouplisa = ",lasku.ytunnus";
    $_orderlisa = ",lasku.ytunnus";
    $_asiakasrajausinfo = $asiakasrow['ytunnus'];
  }

  if ((int) $asiakasid > 0) {
    echo "<table><tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasrow[nimi] $asiakasrow[nimitark] $_asiakasrajausinfo<input type='hidden' name='asiakasid' value='$asiakasid'></td></tr></table><br>";

    if ($alatila == "T") {
      $lisaasiakas = " and lasku.liitostunnus='$asiakasid' ";
    }
    else {
      $lisaasiakas = " and lasku.ytunnus='{$asiakasrow['ytunnus']}' ";
    }
  }

  if ($raportointitaso == 'tuote') {
    $sellisa = ", tilausrivi.tuoteno, tilausrivi.nimitys ";
  }

  if ($rivit == "puutteet") {
    $rivilisa = " HAVING puutekpl <> 0 ";
  }

  $query_ale_lisa = generoi_alekentta('M');

  if ($try != '') {
    $query = "SELECT tilausrivi.osasto, tilausrivi.try, tilausrivi.tuoteno, tilausrivi.nimitys, lasku.ytunnus, asiakas.asiakasnro,
              round(sum(if (tilausrivi.var='P', tilausrivi.tilkpl, 0)),2) puutekpl,
              round(sum(if (tilausrivi.var='P', tilausrivi.tilkpl*tilausrivi.hinta*{$query_ale_lisa}/(1+(tilausrivi.alv/100)), 0)),2) puuteeur,
              round(sum(if ((tilausrivi.var='' or tilausrivi.var='H'), (tilausrivi.kpl+tilausrivi.varattu)*tilausrivi.hinta*{$query_ale_lisa}/(1+(tilausrivi.alv/100)), 0)),2) myyeur,
              round(sum(if (tilausrivi.var='' or tilausrivi.var='H', tilausrivi.kpl+tilausrivi.varattu, 0)),2) myykpl
              FROM tilausrivi
              LEFT JOIN lasku ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
              LEFT JOIN asiakas ON lasku.yhtio=asiakas.yhtio and lasku.liitostunnus=asiakas.tunnus
              LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
              WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
              and tilausrivi.laadittu >= '$vva-$kka-$ppa 00:00:00'
              and tilausrivi.laadittu <= '$vvl-$kkl-$ppl 23:59:59'
              and tilausrivi.var      in ('P','H','')
              and tilausrivi.osasto   = '$osasto'
              and tilausrivi.try      = '$try'
              and tilausrivi.tyyppi  ='L'
              $lisaasiakas
              and tuote.status        NOT IN ('P','X')
              GROUP BY tilausrivi.osasto, tilausrivi.try, tilausrivi.tuoteno, tilausrivi.nimitys $_grouplisa
              HAVING puutekpl <> 0
              ORDER BY tilausrivi.osasto, tilausrivi.try, tilausrivi.tuoteno, tilausrivi.nimitys $_orderlisa";
  }
  else {
    $query = "SELECT tilausrivi.osasto, tilausrivi.try $sellisa,
              round(sum(if (tilausrivi.var='P', tilausrivi.tilkpl, 0)),2) puutekpl,
              round(sum(if (tilausrivi.var='P', tilausrivi.tilkpl*tilausrivi.hinta*{$query_ale_lisa}/(1+(tilausrivi.alv/100)), 0)),2) puuteeur,
              round(sum(if (tilausrivi.var='' or tilausrivi.var='H', (tilausrivi.kpl+tilausrivi.varattu)*tilausrivi.hinta*{$query_ale_lisa}/(1+(tilausrivi.alv/100)), 0)),2) myyeur,
              round(sum(if (tilausrivi.var='' or tilausrivi.var='H', tilausrivi.kpl+tilausrivi.varattu, 0)),2) myykpl
              FROM tilausrivi
              LEFT JOIN lasku ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
              LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tilausrivi.tuoteno=tuote.tuoteno
              WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
              and tilausrivi.tyyppi  = 'L'
              and tilausrivi.laadittu >='$vva-$kka-$ppa 00:00:00'
              and tilausrivi.laadittu <='$vvl-$kkl-$ppl 23:59:59'
              and tilausrivi.var     in ('P','H','')
              $lisaasiakas
              and tuote.status       NOT IN ('P','X')
              GROUP BY tilausrivi.osasto, tilausrivi.try $sellisa
              $rivilisa
              ORDER BY tilausrivi.osasto, tilausrivi.try $sellisa";
  }

  $result = pupe_query($query);

  include 'inc/pupeExcel.inc';

  $worksheet    = new pupeExcel();
  $format_bold = array("bold" => TRUE);
  $excelrivi    = 0;

  $pvm = date("Ymd");

  $worksheet->writeString($excelrivi, 0, t("Puutelistaus")." $asiakasrow[nimi] "."$asiakasrow[nimitark]".", $_asiakasrajausinfo");
  $worksheet->writeString($excelrivi, 3, $pvm);

  $excelrivi++;
  $excelsarake = 0;

  $worksheet->writeString($excelrivi, $excelsarake, t("Osasto"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Tuoteryhmä"));
  $excelsarake++;

  if ($raportointitaso == 'tuote') {
    $worksheet->writeString($excelrivi, $excelsarake, t("Tuotenumero"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Nimitys"));
    $excelsarake++;
  }

  if ($try != '') {
    $worksheet->writeString($excelrivi, $excelsarake, t("Ytunnus"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Asiakasnro"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Tuotenumero"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Nimitys"));
    $excelsarake++;
  }

  $worksheet->writeString($excelrivi, $excelsarake, t("Puute kpl"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Puute")." $yhtiorow[valkoodi]");
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Myynti Kpl"));
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Myynti")." $yhtiorow[valkoodi]");
  $excelsarake++;
  $worksheet->writeString($excelrivi, $excelsarake, t("Puute")." %");
  $excelsarake++;

  if ($try != '') {
    $worksheet->writeString($excelrivi, $excelsarake, t("Saldo"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Tilkpl"));
    $excelsarake++;

    if (table_exists("yhteensopivuus_tuote")) {
      $worksheet->writeString($excelrivi, $excelsarake, t("Rekisteröidyt"));
      $excelsarake++;
    }

    $worksheet->writeString($excelrivi, $excelsarake, t("Korvaava (saldo)"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Tähtituote"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Hinnastoon"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Status"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Ostoehdotus"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Toimittaja"));
    $excelsarake++;
    $worksheet->writeString($excelrivi, $excelsarake, t("Toimittajan tuoteno"));
    $excelsarake++;
  }

  $excelrivi++;

  echo "<table><tr>
      <th>".t("Osasto")."</th>
      <th>".t("Tuoteryhmä")."</th>";

  if ($raportointitaso == 'tuote') {
    echo "<th>".t("Tuotenumero")."</th>
        <th>".t("Nimitys")."</th>";
  }

  if ($try != '') {
    echo "<th>".t("Ytunnus")."<br>".t("Asiakasnro")."</th>";
    echo "<th nowrap>".t("Tuotenumero")."<br>".t("Nimitys")."</th>";
  }

  echo "  <th nowrap>".t("Puute kpl")."</th>
      <th nowrap>".t("Puute")." $yhtiorow[valkoodi]</th>
      <th nowrap>".t("Myynti Kpl")."</th>
      <th nowrap>".t("Myynti")." $yhtiorow[valkoodi]</th>
      <th nowrap>".t("Puute")." %</th>";

  if ($try != '') {
    echo "<th>".t("Saldo")."</th>";
    echo "<th>".t("Tilkpl")."</th>";

    if (table_exists("yhteensopivuus_tuote")) {
      echo "<th>".t("Rekisteröidyt")."</th>";
    }

    echo "<th>".t("Korvaava (saldo)")."</th>";
    echo "<th>".t("Tähtituote")."</th>";
    echo "<th>".t("Hinnastoon")."</th>";
    echo "<th>".t("Status")."</th>";
    echo "<th>".t("Ostoehdotus")."</th>";
    echo "<th>".t("Toimittaja")."<br>(".t("Toimittajan tuoteno").")</th>";
  }
  echo "</tr>";

  $puuteyht     = 0;
  $puutekplyht  = 0;
  $myyntiyht    = 0;
  $myyntikplyht = 0;
  $puuteprosyht = 0;
  $edosasto     = '';
  $lask         = 1;
  $ospuute      = 0;
  $ospuutekpl   = 0;
  $osmyynti     = 0;
  $osmyyntikpl  = 0;

  while ($row = mysql_fetch_array($result)) {
    $excelsarake = 0;

    if ($raportointitaso == 'tuote') {
      $cspan = 4;
    }
    else {
      $cspan = 2;
    }

    if ($row["osasto"] != $edosasto and $lask > 1) {

      if ($osmyynti > 0) {
        $ospuutepros = round($ospuute/($ospuute+$osmyynti)*100, 2);
      }
      elseif ($ospuute > 0) {
        $ospuutepros = 100;
      }
      else {
        $ospuutepros = 0;
      }

      echo "<tr>
          <th colspan='$cspan'>".t("Osasto")." $edosasto ".t("yhteensä").":</th>
          <th style='text-align:right'>".sprintf("%.2f", $ospuutekpl)."</th>
          <th style='text-align:right'>".sprintf("%.2f", $ospuute)."</th>
          <th style='text-align:right'>".sprintf("%.2f", $osmyyntikpl)."</th>
          <th style='text-align:right'>".sprintf("%.2f", $osmyynti)."</th>
          <th style='text-align:right'>".sprintf("%.2f", $ospuutepros)."</th>
          </tr>";

      $worksheet->writeString($excelrivi, $excelsarake, t("Osasto")." $edosasto ".t("yhteensä").":");
      $excelsarake+=$cspan;
      $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $ospuutekpl));
      $excelsarake++;
      $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $ospuute));
      $excelsarake++;
      $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $osmyyntikpl));
      $excelsarake++;
      $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $osmyynti));
      $excelsarake++;
      $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $ospuutepros));
      $excelsarake = 0;
      $excelrivi++;

      $ospuute     = 0;
      $ospuutekpl  = 0;
      $osmyynti    = 0;
      $osmyyntikpl = 0;
      $ospuutepros = 0;
    }

    $ospuute     +=$row["puuteeur"];
    $osmyynti    +=$row["myyeur"];
    $osmyyntikpl +=$row["myykpl"];
    $ospuutekpl  +=$row["puutekpl"];

    if ($row["myyeur"] > 0) {
      $puutepros = round($row["puuteeur"]/($row["puuteeur"]+$row["myyeur"])*100, 2);
    }
    elseif ($row["puuteeur"] > 0) {
      $puutepros = 100;
    }
    else {
      $puutepros = 0;
    }

    if ($puutepros == 0) {
      $vari = "spec";
    }
    else {
      $vari = "";
    }

    echo "<tr><td class='$vari' style='vertical-align:top'>$row[osasto]</td>";

    $worksheet->writeString($excelrivi, $excelsarake, $row["osasto"]);
    $excelsarake++;

    if ($try == '') {
      echo "<td class='$vari' style='vertical-align:top'>";

      if ($row["puutekpl"] != 0) {
        echo "<a name='N_$lask' href='$PHP_SELF?tee=go&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&osasto=$row[osasto]&try=$row[try]&asiakasid=$asiakasid&lopetus=$PHP_SELF////tee=$tee//ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl//asiakasid=$asiakasid//raportointitaso=$raportointitaso//rivit=$rivit///N_$lask'>$row[try]</a>";
      }
      else {
        echo "$row[try]";
      }

      echo "</td>";

      $worksheet->writeString($excelrivi, $excelsarake, $row["try"]);
      $excelsarake++;
    }
    else {
      echo "<td class='$vari' style='vertical-align:top'>$row[try]</td>";
      echo "<td class='$vari' name='A_$lask' style='vertical-align:top'><a href='asiakasinfo.php?ytunnus=$row[ytunnus]&lopetus=$PHP_SELF////tee=$tee//try=$try//ppa=$ppa//kka=$kka//osasto=$osasto//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl//asiakasid=$asiakasid//raportointitaso=$raportointitaso//rivit=$rivit///A_$lask'>$row[ytunnus]</a><br>$row[asiakasnro]</td>";
      echo "<td class='$vari' name='T_$lask' style='vertical-align:top'><a href='../tuote.php?tuoteno=".urlencode($row["tuoteno"])."&tee=Z&lopetus=$PHP_SELF////tee=$tee//try=$try//osasto=$osasto//ppa=$ppa//kka=$kka//vva=$vva//ppl=$ppl//kkl=$kkl//vvl=$vvl//asiakasid=$asiakasid//raportointitaso=$raportointitaso//rivit=$rivit///T_$lask'>$row[tuoteno]</a><br>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";

      $worksheet->writeString($excelrivi, $excelsarake, $row["try"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row["ytunnus"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row["asiakasnro"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, t_tuotteen_avainsanat($row, 'nimitys'));
      $excelsarake++;
    }

    if ($raportointitaso == 'tuote') {
      echo "<td class='$vari' style='vertical-align:top'>$row[tuoteno]</td><td class='$vari' style='vertical-align:top'>$row[nimitys]</td>";

      $worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $row["nimitys"]);
      $excelsarake++;
    }

    echo "  <td style='text-align:right; vertical-align:top' class='$vari'>". (float) $row['puutekpl']."</td>
        <td style='text-align:right; vertical-align:top' class='$vari'>$row[puuteeur]</td>
        <td style='text-align:right; vertical-align:top' class='$vari'>$row[myykpl]</td>
        <td style='text-align:right; vertical-align:top' class='$vari'>$row[myyeur]</td>
        <td style='text-align:right; vertical-align:top' class='$vari'>".sprintf("%.1f", $puutepros)."</td>";

    $worksheet->writeNumber($excelrivi, $excelsarake, (float) $row['puutekpl']);
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, $row['puuteeur']);
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, $row['myykpl']);
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, $row['myyeur']);
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.1f", $puutepros));
    $excelsarake++;

    if ($try != '') {
      // Tuotteen kokonaissaldo
      $query = "SELECT sum(saldo) kokonaissaldo
                FROM tuotepaikat
                WHERE yhtio = '$kukarow[yhtio]'
                and tuoteno = '$row[tuoteno]'";
      $saldores = pupe_query($query);
      $saldorow = mysql_fetch_assoc($saldores);

      echo "<td style='text-align:right; vertical-align:top' class='$vari'>". (float) $saldorow['kokonaissaldo']."</td>";

      $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $saldorow['kokonaissaldo']));
      $excelsarake++;

      //tilauksessa olevat
      $query = "SELECT sum(varattu) tilattu
                FROM tilausrivi
                WHERE yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]' and varattu>0 and tyyppi='O'";
      $tulresult = pupe_query($query);
      $tulrow = mysql_fetch_array($tulresult);

      echo "<td class='$vari' style='text-align:right; vertical-align:top'>". (float) $tulrow['tilattu']."</td>";

      $worksheet->writeNumber($excelrivi, $excelsarake, (float) $tulrow['tilattu']);
      $excelsarake++;

      $query = "SELECT tahtituote, hinnastoon, status, ostoehdotus,
                group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno,
                group_concat(distinct toimi.ytunnus order by toimi.ytunnus separator '<br>') toimittaja
                FROM tuote
                LEFT JOIN tuotteen_toimittajat USING (yhtio, tuoteno)
                LEFT JOIN toimi ON toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus
                WHERE tuote.yhtio='$kukarow[yhtio]'
                and tuote.tuoteno='$row[tuoteno]'
                GROUP BY 1,2,3";
      $tuoteresult = pupe_query($query);
      $tuoterow = mysql_fetch_array($tuoteresult);

      //Rekisteröidyt kpl
      if (table_exists("yhteensopivuus_tuote")) {
        $query = "SELECT count(yhteensopivuus_rekisteri.tunnus)
                  FROM yhteensopivuus_tuote, yhteensopivuus_rekisteri
                  WHERE yhteensopivuus_tuote.yhtio = yhteensopivuus_rekisteri.yhtio
                  AND yhteensopivuus_tuote.atunnus = yhteensopivuus_rekisteri.autoid
                  AND yhteensopivuus_tuote.yhtio   = '$kukarow[yhtio]'
                  AND yhteensopivuus_tuote.tuoteno = '$row[tuoteno]'";

        $rekresult = pupe_query($query);
        $rekrow = mysql_fetch_array($rekresult);

        echo "<td class='$vari' style='text-align:right; vertical-align:top'>$rekrow[0]</td>";

        $worksheet->writeNumber($excelrivi, $excelsarake, $rekrow[0]);
        $excelsarake++;
      }

      ///* Korvaavat tuotteet *///
      $query  = "SELECT * from korvaavat where tuoteno='$row[tuoteno]' and yhtio='$kukarow[yhtio]'";
      $korvaresult = pupe_query($query);

      if (mysql_num_rows($korvaresult) > 0) {
        // tuote löytyi, joten haetaan sen id...
        $korvarow = mysql_fetch_array($korvaresult);

        $query = "SELECT * from korvaavat where id='$korvarow[id]' and tuoteno<>'$row[tuoteno]' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
        $korva2result = pupe_query($query);

        echo "<td class='$vari' style='vertical-align:top'>";

        $korvaavat_temp = "";

        if (mysql_num_rows($korva2result) > 0) {
          while ($krow2row = mysql_fetch_array($korva2result)) {
            //hateaan vielä korvaaville niiden saldot.
            //saldot per varastopaikka
            $query = "SELECT sum(saldo) alkusaldo from tuotepaikat where tuoteno='$krow2row[tuoteno]' and yhtio='$kukarow[yhtio]'";
            $alkuresult = pupe_query($query);
            $alkurow = mysql_fetch_array($alkuresult);

            //ennakkopoistot
            $query = "SELECT sum(varattu) varattu
                      FROM tilausrivi
                      WHERE tyyppi = 'L' and yhtio = '$kukarow[yhtio]' and tuoteno = '$krow2row[tuoteno]' and varattu>0";
            $varatutresult = pupe_query($query);
            $varatutrow = mysql_fetch_array($varatutresult);

            $vapaana = $alkurow["alkusaldo"] - $varatutrow["varattu"];

            echo "$krow2row[tuoteno] ($vapaana)<br>";

            $korvaavat_temp .= "$krow2row[tuoteno] ($vapaana), ";

          }
        }

        $korvaavat_temp = substr($korvaavat_temp, 0, -2);

        $worksheet->writeString($excelrivi, $excelsarake, $korvaavat_temp);
        $excelsarake++;

        echo "</td>";
      }
      else {
        echo "<td class='$vari' style='vertical-align:top'>".t("Ei korvaavia")."!</td>";

        $worksheet->writeString($excelrivi, $excelsarake, t("Ei korvaavia")."!");
        $excelsarake++;
      }

      // Näytetään ostoehdotus sarakkeessa kyllä / ei
      $ostoehdotus = ($tuoterow['ostoehdotus'] == 'E') ? t("Ei") : t("Kyllä");

      echo "<td class='$vari' style='vertical-align:top'>$tuoterow[tahtituote]</td>";
      echo "<td class='$vari' style='vertical-align:top'>$tuoterow[hinnastoon]</td>";
      echo "<td class='$vari' style='vertical-align:top'>$tuoterow[status]</td>";
      echo "<td class='$vari' style='vertical-align:top'>$ostoehdotus</td>";
      echo "<td class='$vari' style='vertical-align:top'>$tuoterow[toimittaja]";

      if ($tuoterow["toim_tuoteno"]) {
        echo "<br>($tuoterow[toim_tuoteno])</td>";
      }
      else {
        echo "</td>";
      }

      $worksheet->writeString($excelrivi, $excelsarake, $tuoterow["tahtituote"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tuoterow["hinnastoon"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tuoterow["status"]);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $ostoehdotus);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, $tuoterow["toimittaja"]);
      $excelsarake++;

      if ($tuoterow["toim_tuoteno"]) {
        $worksheet->writeString($excelrivi, $excelsarake, $tuoterow["toim_tuoteno"]);
        $excelsarake++;
      }

      $excelsarake = 0;
    }

    echo "</tr>";

    $excelrivi++;

    $lask++;
    $puuteyht     += $row["puuteeur"];
    $puutekplyht  += $row["puutekpl"];
    $myyntikplyht += $row["myykpl"];
    $myyntiyht    += $row["myyeur"];
    $puuteprosyht += $puutepros;
    $edosasto      = $row["osasto"];
  }

  if ($try == '') {
    // vika osasto yhteensä
    if ($osmyynti > 0)
      $ospuutepros = round($ospuute/($ospuute+$osmyynti)*100, 2);
    else
      $ospuutepros = 100;

    echo "<tr>
        <th colspan='$cspan'>".t("Osasto")." $edosasto ".t("yhteensä").":</th>
        <th style='text-align:right'>".sprintf("%.2f", $ospuutekpl)."</th>
        <th style='text-align:right'>".sprintf("%.2f", $ospuute)."</th>
        <th style='text-align:right'>".sprintf("%.2f", $osmyyntikpl)."</th>
        <th style='text-align:right'>".sprintf("%.2f", $osmyynti)."</th>
        <th style='text-align:right'>".sprintf("%.2f", $ospuutepros)."</th>
        </tr>";

    $excelsarake = 0;
    $worksheet->writeString($excelrivi, $excelsarake, t("Osasto")." $edosasto ".t("yhteensä").":");
    $excelsarake++;
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $ospuutekpl));
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $ospuute));
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $osmyyntikpl));
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $osmyynti));
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $ospuutepros));
    $excelsarake = 0;
    $excelrivi++;

    //tähän tullee nyt keskiarvo
    $puuteprosyht = round($puuteyht/($puuteyht+$myyntiyht)*100, 2);

    echo "<tr>
        <th colspan='$cspan'>".t("Kaikki yhteensä").":</th>
        <th style='text-align:right'>".sprintf("%.2f", $puutekplyht)."</th>
        <th style='text-align:right'>".sprintf("%.2f", $puuteyht)."</th>
        <th style='text-align:right'>".sprintf("%.2f", $myyntikplyht)."</th>
        <th style='text-align:right'>".sprintf("%.2f", $myyntiyht)."</th>
        <th style='text-align:right'>".sprintf("%.2f", $puuteprosyht)."</th>
        </tr>";

    $excelsarake = 0;
    $worksheet->writeString($excelrivi, $excelsarake, t("Kaikki yhteensä").":");
    $excelsarake++;
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $puutekplyht));
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $puuteyht));
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $myyntikplyht));
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $myyntiyht));
    $excelsarake++;
    $worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.2f", $puuteprosyht));
    $excelsarake = 0;
    $excelrivi++;
  }

  echo "</table>";

  $excelnimi = $worksheet->close();

  $niminimi = date("Ymd")."-".t("Puutelistaus").".xlsx";

  echo "<br><font class='message'>".t("Luotiin aineisto")." ($niminimi) ".t("joka sisältää")." ".($excelrivi-2)." ".t("riviä")."</font><br>";

  echo "<table>";
  echo "<tr><th>".t("Tallenna excel").":</th>";
  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='kaunisnimi' value='$niminimi'>";
  echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
  echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
  echo "</table>";
}

//Käyttöliittymä
echo "<br><form method='post'>";
echo "<table>";

// ehdotetaan 7 päivää taaksepäin
if (!isset($kka))
  $kka = date("m", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
if (!isset($vva))
  $vva = date("Y", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
if (!isset($ppa))
  $ppa = date("d", mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));

if (!isset($kkl))
  $kkl = date("m");
if (!isset($vvl))
  $vvl = date("Y");
if (!isset($ppl))
  $ppl = date("d");

echo "<input type='hidden' name='tee' value='kaikki'>";
echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppa' value='$ppa' size='3'></td>
    <td><input type='text' name='kka' value='$kka' size='3'></td>
    <td><input type='text' name='vva' value='$vva' size='5'></td>
    </tr><tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppl' value='$ppl' size='3'></td>
    <td><input type='text' name='kkl' value='$kkl' size='3'></td>
    <td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

if ((int) $asiakasid > 0) {
  echo "<tr><th>".t("Asiakas")."</th><td colspan='3'>$asiakasrow[nimi] $asiakasrow[nimitark] $asiakasrow[toim_nimi]<input type='hidden' name='asiakasid' value='$asiakasid'></td></tr>";
}
else {
  echo "<tr><th>".t("Valitse asiakas")."</th><td colspan='3'><input type='text' name='ytunnus' value='$ytunnus' size='20'></td></tr>";
}

$sel = (!empty($alatila) and $alatila == 'Y') ? "selected" : "";

echo "<tr><th>", t("Asiakasrajaus"), ":</th>";
echo "<td colspan='3'><select name='alatila'>
      <option value='T'>", t("Asiakkaalla"), "</option>
      <option value='Y' {$sel}>", t("Ytunnuksella"), "</option>
      </select></td></tr>";
//echo "<td class='back'><input type='submit' class='hae_btn' value = '".t("Etsi")."'></td></tr>";


echo "<tr><th>".t("Raportointitaso")."</th><td colspan='3'>";
echo "<select name='raportointitaso'>";

$sel = array();
$sel[$raportointitaso] = "selected";

echo "<option value = 'ostry' $sel[ostry]>".t("Osasto")." / ".t("Tuoteryhmä")."</option>";
echo "<option value = 'tuote' $sel[tuote]>".t("Tuote")."</option>";
echo "</select>";
echo "</td></tr>";


echo "<tr><th>".t("Näytä rivit")."</th><td colspan='3'>";
echo "<select name='rivit'>";

$sel = array();
$sel[$rivit] = "selected";

echo "<option value = 'kaikki' $sel[kaikki]>".t("Kaikki rivit")."</option>";
echo "<option value = 'puutteet' $sel[puutteet]>".t("Vain puuterivit")."</option>";
echo "</select>";
echo "</td></tr>";


echo "</table>";
echo "<br><input type='submit' value='".t("Aja raportti")."'>";
echo "</form>";

require "../inc/footer.inc";
