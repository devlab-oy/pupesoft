<?php

$riviok       = 0;
$saako_hyvaksya   = 0;
$varaosavirhe     = "";
$vastaavattuotteet  = 0;
$vastaavat_html    = "";

$query = "SELECT *
          FROM tuote
          LEFT JOIN tuotteen_toimittajat ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$laskurow[liitostunnus]'
          WHERE tuote.tuoteno = '$prow[tuoteno]'
          and tuote.yhtio     = '$kukarow[yhtio]'";
$rarresult = pupe_query($query);
$trow = mysql_fetch_assoc($rarresult);

//
// Tarkistetaan, että tuotetta ei ole vielä lisätty tilaukselle, jos niin valittu yhtiön
// parametreissä

if ($yhtiorow["ostotilaus_saman_tuotteen_lisays"] != "") {
  $params = array(
    "tilausnumero" => $laskurow["tunnus"],
    "tuoteno"      => $trow["tuoteno"]
  );

  list($ensimmainen_tilausrivi, $viimeinen_tilausrivi) = hae_eka_ja_vika_tilausrivi($params);

  if ($ensimmainen_tilausrivi != $viimeinen_tilausrivi and $prow['tunnus'] == $viimeinen_tilausrivi) {
    $varaosavirhe .= t("HUOM: Tuote löytyy jo tilaukselta") . "!<br>";
    $varaosavirhe .= "<form method='post'>
                        <input type='hidden' name='lopetus' value='{$lopetus}'>
                        <input type='hidden' name='tilausnumero' value='{$tilausnumero}'>
                        <input type='hidden' name='lisattava[tuoteno]' value='{$trow['tuoteno']}'>
                        <input type='hidden' name='lisattava[kpl]' value='{$prow['tilattu']}'>
                        <input type='hidden' name='tee' value='lisaa_aiemmalle_riville'>
                        <input type='submit' value='" . t("Lisää") . " {$prow['tilattu']} {$prow['toim_yksikko']}'>
                      </form>";

    if ($yhtiorow["ostotilaus_saman_tuotteen_lisays"] == "E") {
      $riviok++;
      $tilausok++;
    }
  }
}

if (mysql_num_rows($rarresult) == 0) {
  $varaosavirhe .= t("VIRHE: Tuotetta ei löydy")."!<br>";
  $riviok++;
  $tilausok++;
}

if ($trow["liitostunnus"] == 0 and $toim_tarkistus != "EI" and $riviok == 0) {
  $varaosavirhe .= t("VIRHE: Tuotetta ei voi tilata tältä toimittajalta")."!<br>";
  $riviok++;
  $tilausok++;
}

// katsotaan, ettei tilata epäkuranttia tuotetta
if (($trow["epakurantti25pvm"] != '0000-00-00' or $trow["epakurantti50pvm"] != '0000-00-00' or $trow["epakurantti75pvm"] != '0000-00-00' or $trow["epakurantti100pvm"] != '0000-00-00') and $riviok == 0) {
  $varaosavirhe .= t("VIRHE: Tuote on epäkurantti, sitä ei voi tilata!")."<br>1/4: $trow[epakurantti25pvm]<br>1/2: $trow[epakurantti50pvm]<br>3/4: $trow[epakurantti75pvm]<br>1/1: $trow[epakurantti100pvm]";
  $riviok++;
  $tilausok++;
}

//  Pakkokommentoitava tuote
$_kommentoitava = ($trow["kommentoitava"] == "p");

if ($_kommentoitava and $prow["kommentti"] == "") {
  $varaosavirhe .= t("VIRHE: Tuotetta on kommentoitava!")."<br>";
  $riviok++;
  $tilausok++;
}

//poistotuotekäsittely
if (in_array($trow["status"], array('P', 'X')) and $riviok == 0) {
  $varaosavirhe .= t("VIRHE: Tämä on poistotuote, sitä ei voi tilata")."!<br>";
  $riviok++;
  $tilausok++;
}

if ($trow['ei_saldoa'] != '' and $riviok == 0) {
  $varaosavirhe .= t("HUOM: Tuote ei ole varastoitava, sitä ei voida saavuttaa varastoon")."!<br>";
  $riviok++;
}

if (!empty($toimittajarow['tehdas_saldo_tarkistus']) and isset($trow['tehdas_saldo']) and $trow['tehdas_saldo'] < $prow['varattukpl']) {
  $prow["kommentti"] .= "<br>".t("HUOM: Toimittajan tehtaan saldo on")." {$trow['tehdas_saldo']}.<br>";
}

if ($trow['ostoehdotus'] == 'E' and strpos($_SERVER['SCRIPT_NAME'], "tilaus_osto.php") !== FALSE) {
  $varaosavirhe .= t("HUOM: Tuotetta ei ehdoteta ostoehdotus ohjelmissa tilattavaksi")."!<br>";
}

if ($yhtiorow['ostoera_pyoristys'] == 'K' and isset($ostoerahuom) and is_array($ostoerahuom) and in_array($prow['tunnus'], $ostoerahuom) and $kpl_st != $org_kpl) {
  $varaosavirhe .= "<font class='message'>".t("HUOM: Rivin määrä on pyöristetty täyteen ostoerään!")."<br></font>";
}

if ($trow["myyntihinta_maara"] != 0 and $prow["hinta"] != 0) {
  $prow["hinta"] = round($prow["hinta"] * $trow["myyntihinta_maara"], $yhtiorow["hintapyoristys"]);
}

if ($oikeusostohintapaiv !== FALSE and (float) $prow["hinta"] != (float) $trow['ostohinta'] and ($prow["var"] == "" or $toiminto == 'kohdista') and $trow["liitostunnus"] != 0 and $riviok == 0) {

  $paiv_hinta = (float) $paiv_hinta;

  if ($paivitaostohinta == 'ON' and ($ALPA != '' or $JOPA != '') and $prow["tuoteno"] == $paiv_tuoteno and $prow["tunnus"] == $paiv_rivitunnus) {
    $paok = '';
    if ($toiminto != 'kohdista') {
      $oshinq = "UPDATE tilausrivi
                 set var    = 'O'
                 where yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$prow[tunnus]'
                 and otunnus = '$kukarow[kesken]'";
      $ishinr = pupe_query($oshinq);

      $prow['var'] = "O";
    }
    else {
      $paok = 'ok';
    }

    $ALPA = '';

    if ($JOPA == '') {
      $paivitaostohinta = '';
    }
  }

  if ($paivitaostohinta == 'ON' and $JOPA != '' and $prow["tuoteno"] == $paiv_tuoteno and ($prow["tunnus"] == $paiv_rivitunnus or $toiminto == 'kohdista')) {
    $paok ='';
    $oshinq = "UPDATE tuotteen_toimittajat
               SET ostohinta    = '$paiv_hinta'
               WHERE yhtio      = '$kukarow[yhtio]'
               AND tuoteno      = '$prow[tuoteno]'
               AND liitostunnus = '$laskurow[liitostunnus]'";
    $ishinr = pupe_query($oshinq);

    $paok = 'ok';

    $JOPA = '';
    $paivitaostohinta = '';

    if ($toiminto == 'kohdista') {
      echo "<font class='message'>".t("Päivitettiin tuotteen")." $trow[tuoteno] ".t("ostohinta").": $trow[ostohinta] -> $paiv_hinta</font><br>";
    }
    else {
      $varaosavirhe .= "<font class='message'>".t("Päivitettiin tuotteen")." $trow[tuoteno] ".t("ostohinta").": $trow[ostohinta] -> $paiv_hinta</font>";
    }

  }

  if (($toiminto != 'kohdista' and $prow['var'] != 'O' and ($prow["tunnus"] != $paiv_rivitunnus or $paiv_rivitunnus == '')) or (($toiminto == 'kohdista' and $paok == ''))) {
    $varaosavirhe .= "  <form name='tilaus' action='$PHP_SELF#$rivitunnus' method='post' autocomplete='off'>
              <input type='hidden' name='toim'         value = '$toim'>
              <input type='hidden' name='lopetus'       value = '$lopetus'>
              <input type='hidden' name='tilausnumero'     value = '$tilausnumero'>
              <input type='hidden' name='toim_nimitykset'   value = '$toim_nimitykset'>
              <input type='hidden' name='toim_tuoteno'    value = '$toim_tuoteno'>
              <input type='hidden' name='naytetaankolukitut'   value = '$naytetaankolukitut'>
              <input type='hidden' name='toiminto'       value = '$toiminto'>
              <input type='hidden' name='tee'         value = 'Y'>
              <input type='hidden' name='muut_siirrettavat'   value = '$muut_siirrettavat'>
              <input type='hidden' name='rivinotunnus'     value = '$rivinotunnus'>
              <input type='hidden' name='paivitaostohinta'   value = 'ON'>
              <input type='hidden' name='paiv_hinta'       value = '$prow[hinta]'>
              <input type='hidden' name='paiv_tuoteno'     value = '$prow[tuoteno]'>
              <input type='hidden' name='paiv_rivitunnus'   value = '$prow[tunnus]'>";

    if ($toiminto == "kohdista") {
      $varaosavirhe .= "<input type='hidden' name='tuoteno'   value = '$tuoteno'>";
      $varaosavirhe .= "<input type='hidden' name='kpl'     value = '$kpl'>";
      $varaosavirhe .= "<input type='hidden' name='var'     value = '$var'>";
      $varaosavirhe .= "<input type='hidden' name='hinta'   value = '$hinta'>";
      $varaosavirhe .= "<input type='hidden' name='toimaika'   value = '$toimaika'>";
      $varaosavirhe .= "<input type='hidden' name='alv'     value = '$alv'>";
      $varaosavirhe .= "<input type='hidden' name='kommentti' value = '$kommentti'>";
    }

    $varaosavirhe .= "  <font class='message'>".t("Päivitä tuotteen ostohinta").":<br> ".hintapyoristys($trow["ostohinta"])." $trow[valuutta] -> ".hintapyoristys($prow["hinta"])." $trow[valuutta]</font><br>
              <input type='submit' name='JOPA' value='".t("Päivitä")."'>
              <input type='submit' name='ALPA' value='".t("Älä päivitä")."'>
              </form>";
  }
}

if (!empty($prow["tuoteno"]) and (!isset($rivienmaara) or $rivienmaara < 100)) {

  // Haetaan vastaavuusketjut korvaavat ketjun päätuotteella
  require_once 'vastaavat.class.php';

  $vastaavat = new Vastaavat($prow["tuoteno"]);

  // Loopataan kaikki tuotteen vastaavuusketjut
  foreach (explode(",", $vastaavat->getIDt()) as $ketju) {

    $vastaavat_table = "";

    // Haetaan tuotteet ketjukohtaisesti
    $_tuotteet = $vastaavat->tuotteet($ketju);

    foreach ($_tuotteet as $_tuote) {

      $vaihtoehtoinen = ($_tuote['vaihtoehtoinen'] == 'K') ? "<font color=#F00>".t("Vaihtoehtoinen, tarkista sopivuus")."!</font>" : "";

      $tvrow = hae_tuote($_tuote['tuoteno']);

      $tuotteen_toimittaja_query = "SELECT tuotteen_toimittajat.tehdas_saldo,
                                    tuotteen_toimittajat.toim_tuoteno,
                                    tuotteen_toimittajat.ostohinta,
                                    tuotteen_toimittajat.tunnus AS tt_tunnus,
                                    toimi.nimi as toimittajan_nimi,
                                    toimi.tunnus as toimi_tunnus,
                                    tuote.myyntihinta,
                                    avainsana.selitetark_2,
                                    avainsana.selitetark_3,
                                    avainsana.selitetark_4,
                                    avainsana.selitetark_5
                                    FROM tuotteen_toimittajat
                                    JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                                    LEFT JOIN avainsana ON (toimi.yhtio = avainsana.yhtio AND avainsana.laji = 'SAHKTILTUN' AND avainsana.selite = '{$laskurow['vanhatunnus']}' AND toimi.tunnus = avainsana.selitetark)
                                    JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno)
                                    WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
                                    AND tuotteen_toimittajat.tuoteno = '{$_tuote['tuoteno']}'
                                    ORDER BY if(tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys), tuotteen_toimittajat.tunnus
                                    LIMIT 1";
      $tuotteen_toimittaja_result = pupe_query($tuotteen_toimittaja_query);
      $tuotteen_toimittaja_row = mysql_fetch_assoc($tuotteen_toimittaja_result);

      if ($tuotteen_toimittaja_row['tehdas_saldo'] < $prow['varattukpl']) {
        $tuotteen_toimittaja_row['tehdas_saldo'] = "<font class='error'>".sprintf("%.2f", $tuotteen_toimittaja_row['tehdas_saldo'])."</font>";
      }
      else {
        $tuotteen_toimittaja_row['tehdas_saldo'] = sprintf("%.2f", $tuotteen_toimittaja_row['tehdas_saldo']);
      }

      list(, , $tuotteen_toimittaja_row['saldo_myytavissa']) = saldo_myytavissa($_tuote['tuoteno'], '', '', '', '', '', '', '', '', '');

      $saldo_myytavissa_font = $tuotteen_toimittaja_row['saldo_myytavissa'] < 1 ? "error" : "ok";

      $vastaavat_table .= "<tr class='aktiivi back'>"
        ."<td><a href='../tuote.php?tee=Z&tuoteno=".urlencode($_tuote['tuoteno'])."&toim_kutsu=RIVISYOTTO&lopetus=$tilost_lopetus//from=LASKUTATILAUS'>{$_tuote["tuoteno"]} $vaihtoehtoinen</a></td>"
        ."<td>{$tuotteen_toimittaja_row['toim_tuoteno']}</td>"
        ."<td>{$tvrow["nimitys"]}</td>"
        ."<td>{$tuotteen_toimittaja_row['toimittajan_nimi']}</td>"
        ."<td>".sprintf("%.2f", $tuotteen_toimittaja_row['ostohinta'])."</td>"
        ."<td>".sprintf("%.2f", $tuotteen_toimittaja_row['myyntihinta'])."</td>"
        ."<td align='right'><font class='{$saldo_myytavissa_font}'>".$tuotteen_toimittaja_row['saldo_myytavissa']."</font></td>";

      $vastaavat_table .= "<td align='right' nowrap>";

      $_tilattu = (int) $prow['tilattu'];

      // Ostopuolella toimipaikan tunnus tallennetaan vanhatunnus-kenttään
      if ($tuotteen_toimittaja_row['selitetark_2'] != "") {
        $vastaavat_table .= "<div class='availability {$_tuote['vastaavat_tunnus']}_{$_tilattu}_availability' /> <span class='{$_tuote['vastaavat_tunnus']}_{$_tilattu}_loading'></span></div>&nbsp;";
        $vastaavat_table .= "<input type='hidden' class='{$_tuote['vastaavat_tunnus']}_custid' value='{$tuotteen_toimittaja_row['selitetark_2']}' />";
        $vastaavat_table .= "<input type='hidden' class='{$_tuote['vastaavat_tunnus']}_username' value='{$tuotteen_toimittaja_row['selitetark_3']}' />";
        $vastaavat_table .= "<input type='hidden' class='{$_tuote['vastaavat_tunnus']}_password' value='{$tuotteen_toimittaja_row['selitetark_4']}' />";
        $vastaavat_table .= "<input type='hidden' class='{$_tuote['vastaavat_tunnus']}_suppliernumber' value='{$tuotteen_toimittaja_row['selitetark_5']}' />";
        $vastaavat_table .= "<input type='hidden' class='{$_tuote['vastaavat_tunnus']}_tt_tunnus' value='{$tuotteen_toimittaja_row['tt_tunnus']}' />";
      }

      if ($tuotteen_toimittaja_row['selitetark_2'] == "") {

        if (!empty($laskurow['varasto'])) {
          $kohde_varastot = array($laskurow['varasto']);
        }
        elseif (empty($laskurow['varasto']) and !empty($laskurow['yhtio_toimipaikka'])) {
          $kohde_varastot = hae_yhtion_toimipaikan_varastot($laskurow['yhtio_toimipaikka']);
          $kohde_varastot = array_column($kohde_varastot, 'tunnus');
        }
        else {
          $kohde_varastot = array();
        }

        $siirto_varastot = hae_mahdolliset_siirto_varastot($kohde_varastot, $_tuote['tuoteno']);

        $_siirrettavissa = $siirto_varastot[0]['tuote']['myytavissa'];
        $siirrettavissa_saldon_font = $_siirrettavissa < 1 ? "error" : "ok";

        $vastaavat_table .= "<font class='{$siirrettavissa_saldon_font}'>".sprintf("%.2f", $_siirrettavissa)."</font>";
      }

      $vastaavat_table .= "</td>";
      $vastaavat_table .= "<td>";

      if ($sahkoinen_tilausliitanta and $tuotteen_toimittaja_row['selitetark_2'] != "") {

        $hae = 'nappi';
        $rivitunnus = $prow['tunnus'];
        $_vastaavat_tunnus = $_tuote['vastaavat_tunnus'];
        $_vastaavat_tuoteno = $tuotteen_toimittaja_row['toim_tuoteno'];

        require "inc/sahkoinen_tilausliitanta.inc";
      }

      $vastaavat_table .= "</td>";

      $vastaavat_table .= "<td align='left'>";

      if (strtoupper($prow['tuoteno']) != strtoupper($_tuote['tuoteno'])) {
        $vastaavat_table .= "<form method='post' name='jalkitoimita'>
              <input type='hidden' name='toim'       value = '$toim'>
              <input type='hidden' name='lopetus'     value = '$lopetus'>
              <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
              <input type='hidden' name='rivitunnus'     value = '$prow[tunnus]'>
              <input type='hidden' name='vastaavatuoteno' value = '$_tuote[tuoteno]'>
              <input type='hidden' name='toimi_tunnus'  value = '{$tuotteen_toimittaja_row['toimi_tunnus']}'>
              <input type='hidden' name='tee'       value = 'PV'>
              <input type='hidden' name='tapa'       value = 'VAIHDARIVI'>
              <input type='submit' value='".t("Tilaa tuote")."' onclick='return confirm(\"".t('Oletko varma että haluat vaihtaa tuotteen')."\")'>
              </form>";
      }

      $vastaavat_table .= "</td></tr>";
    }

    if (!empty($vastaavat_table)) {

      if ($vastaavattuotteet == 0) {
        $vastaavat_html .= "<table width='100%'>";
      }

      $vastaavat_html .= "<tr>
        <th>".t("Vastaava")." ".t("Tuoteno")."</th>
        <th>".t("Toimittajan tuoteno")."</th>
        <th>".t("Nimitys")."</th>
        <th>".t("Toimittaja")."</th>
        <th>".t("Ostohinta")."</th>
        <th>".t("Hinta")."</th>
        <th>".t("Saldo myytävissä")."</th>
        <th>".t("Siirrettävissä")."</th>";

      if ($sahkoinen_tilausliitanta) $vastaavat_html .= "<th>".t("Tarkista toimittajan saldo")."</th>";

      $vastaavat_html .= "<th></th></tr>$vastaavat_table";
      $vastaavattuotteet = 1;
    }
  }

  require_once 'korvaavat.class.php';
  $korvaavat = new Korvaavat($prow['tuoteno']);

  $korvaavat_tuotteet = $korvaavat->tuotteet();

  $korvaavat_table = "";

  foreach ($korvaavat_tuotteet as $korvaava_tuote) {

    if (strtoupper($korvaava_tuote['tuoteno']) != strtoupper($prow["tuoteno"]) and $korvaava_tuote['status'] != 'P') {
      $tvrow = hae_tuote($korvaava_tuote['tuoteno']);

      $tuotteen_toimittaja_query = "SELECT tuotteen_toimittajat.tehdas_saldo,
                                    tuotteen_toimittajat.toim_tuoteno,
                                    tuotteen_toimittajat.ostohinta,
                                    toimi.nimi as toimittajan_nimi,
                                    toimi.tunnus as toimi_tunnus,
                                    tuote.myyntihinta
                                    FROM tuotteen_toimittajat
                                    JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                                    JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio AND tuote.tuoteno = tuotteen_toimittajat.tuoteno)
                                    WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
                                    AND tuotteen_toimittajat.tuoteno = '{$korvaava_tuote['tuoteno']}'";
      $tuotteen_toimittaja_result = pupe_query($tuotteen_toimittaja_query);
      $tuotteen_toimittaja_row = mysql_fetch_assoc($tuotteen_toimittaja_result);

      if ($tuotteen_toimittaja_row['tehdas_saldo'] < $prow['varattukpl']) {
        $tuotteen_toimittaja_row['tehdas_saldo'] = "<font class='error'>".sprintf("%.2f", $tuotteen_toimittaja_row['tehdas_saldo'])."</font>";
      }
      else {
        $tuotteen_toimittaja_row['tehdas_saldo'] = sprintf("%.2f", $tuotteen_toimittaja_row['tehdas_saldo']);
      }

      list(, , $tuotteen_toimittaja_row['saldo_myytavissa']) = saldo_myytavissa($_tuote['tuoteno'], '', '', '', '', '', '', '', '', '');

      $korvaavat_table .= "<tr class='aktiivi'>"
        ."<td>{$korvaava_tuote["tuoteno"]}</td>"
        ."<td>{$tuotteen_toimittaja_row['toim_tuoteno']}</td>"
        ."<td>{$tvrow["nimitys"]}</td>"
        ."<td>{$tuotteen_toimittaja_row['toimittajan_nimi']}</td>"
        ."<td>".sprintf("%.2f", $tuotteen_toimittaja_row['ostohinta'])."</td>"
        ."<td>".sprintf("%.2f", $tuotteen_toimittaja_row['myyntihinta'])."</td>"
        ."<td align='right'>".$tuotteen_toimittaja_row['saldo_myytavissa']."</td>"
        ."<td align='right'>".$tuotteen_toimittaja_row['tehdas_saldo']."</td>"
        ."<td align='left'>"
        ." <form method='post' name='jalkitoimita'>
                  <input type='hidden' name='toim'       value = '$toim'>
                  <input type='hidden' name='lopetus'     value = '$lopetus'>
                  <input type='hidden' name='tilausnumero'   value = '$tilausnumero'>
                  <input type='hidden' name='rivitunnus'     value = '{$prow['tunnus']}'>
                  <input type='hidden' name='vastaavatuoteno' value = '{$korvaava_tuote['tuoteno']}'>
                  <input type='hidden' name='toimi_tunnus'  value = '{$tuotteen_toimittaja_row['toimi_tunnus']}'>
                  <input type='hidden' name='tee'       value = 'PV'>
                  <input type='hidden' name='tapa'       value = 'VAIHDARIVI'>
                  <input type='submit' value='".t("Tilaa tuote")."' onclick='return confirm(\"".t('Oletko varma että haluat vaihtaa tuotteen')."\")'>
                  </form> "
        ."</td></tr>";
    }
  }

  if (!empty($korvaavat_table)) {
    if (empty($vastaavat_html)) {
      $vastaavat_html = "<table width='100%'>";
    }
    $vastaavat_html .= "<tr>
      <th>".t("Korvaava")." ".t("Tuoteno")."</th>
      <th>".t("Toimittajan tuoteno")."</th>
      <th>".t("Nimitys")."</th>
      <th>".t("Toimittaja")."</th>
      <th>".t("Ostohinta")."</th>
      <th>".t("Hinta")."</th>
      <th>".t("Saldo myytävissä")."</th>
      <th>".t("Tehtaan saldo")."</th>";
    $vastaavat_html .= "<th>";

    $vastaavat_html .= "</th></tr>$korvaavat_table";
  }

  if (isset($vastaavat_html) and $vastaavat_html != "") {
    $vastaavat_html .= "</table>";
  }
}
