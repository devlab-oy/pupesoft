<?php

if (strpos($_SERVER['SCRIPT_NAME'], "vientitilauksen_lisatiedot.php") !== FALSE) {

  require '../inc/cookie_functions.inc';
  handle_cookie("toimittamattomat", "find_submit");

  require '../inc/parametrit.inc';

  if (isset($livesearch_tee) and $livesearch_tee == "TULLINIMIKEHAKU") {
    livesearch_tullinimikehaku();
    exit;
  }

  enable_ajax();

  echo "<font class='head'>".t("Lisätietojen syöttö")."</font><hr>";
}

if (isset($bruttopaino)) $bruttopaino = str_replace(",", ".", $bruttopaino);
if (isset($lisattava_era)) $lisattava_era = str_replace(",", ".", $lisattava_era);
if (isset($vahennettava_era)) $vahennettava_era = str_replace(",", ".", $vahennettava_era);
if (empty($tapa)) $tapa = '';
if (empty($tee)) $tee = '';
if (empty($etsi)) $etsi = null;

$toim = strtoupper($toim);

if ($tapa == "tuonti" and $tee != "") {

  if ($toim == "TYOMAARAYS") {
    $query = "SELECT tyomaarays.*, lasku.*,
              tyomaarays.kuljetusmuoto,
              tyomaarays.maa_lahetys,
              tyomaarays.maa_maara,
              tyomaarays.maa_alkupera,
              tyomaarays.kauppatapahtuman_luonne,
              tyomaarays.bruttopaino,
              tyomaarays.tullikoodi,
              tyomaarays.tulliarvo
              FROM lasku
              JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio and tyomaarays.otunnus = lasku.tunnus)
              WHERE lasku.tunnus in ($otunnus)
              and lasku.yhtio    = '$kukarow[yhtio]'";
  }
  else {
    $query = "SELECT *
              FROM lasku
              WHERE tunnus in ($otunnus)
              and yhtio    = '$kukarow[yhtio]'";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo $toim == "TYOMAARAYS" ? t("Työmääräystä ei löydy") : t("Laskua ei löydy");
    exit;
  }
  else {
    $laskurow = mysql_fetch_assoc($result);
  }

  if ($tee == "update") {

    $ultilno = tarvitaanko_intrastat($maa_lahetys, $maa_maara);

    if ($toim == "TYOMAARAYS") {
      $query = "UPDATE tyomaarays SET
                tullikoodi              = '$tullikoodi',
                tulliarvo               = '$tulliarvo',
                maa_maara               = '$maa_maara',
                maa_lahetys             = '$maa_lahetys',
                maa_alkupera            = '$maa_alkupera',
                kauppatapahtuman_luonne = '$kauppatapahtuman_luonne',
                kuljetusmuoto           = '$kuljetusmuoto',
                bruttopaino             = '$bruttopaino'
                WHERE otunnus           in ($otunnus)
                and yhtio               = '$kukarow[yhtio]'";
      $result = pupe_query($query);
    }
    else {
      $query = "UPDATE lasku
                SET maa_maara                    = '$maa_maara',
                maa_lahetys                      = '$maa_lahetys',
                kauppatapahtuman_luonne          = '$kauppatapahtuman_luonne',
                kuljetusmuoto                    = '$kuljetusmuoto',
                sisamaan_kuljetus                = '$sisamaan_kuljetus',
                sisamaan_kuljetusmuoto           = '$sisamaan_kuljetusmuoto',
                sisamaan_kuljetus_kansallisuus   = '$sisamaan_kuljetus_kansallisuus',
                kontti                           = '$kontti',
                aktiivinen_kuljetus              = '$aktiivinen_kuljetus',
                aktiivinen_kuljetus_kansallisuus = '$aktiivinen_kuljetus_kansallisuus',
                poistumistoimipaikka             = '$poistumistoimipaikka',
                poistumistoimipaikka_koodi       = '$poistumistoimipaikka_koodi',
                aiotut_rajatoimipaikat           = '$aiotut_rajatoimipaikat',
                maaratoimipaikka                 = '$maaratoimipaikka',
                bruttopaino                      = '$bruttopaino',
                lisattava_era                    = '$lisattava_era',
                vahennettava_era                 = '$vahennettava_era',
                ultilno                          = '$ultilno'
                WHERE tunnus                     in ($otunnus)
                and yhtio                        = '$kukarow[yhtio]'";
      $result = pupe_query($query);
    }

    $tee = "";

    if ($lopetus != "") {
      lopetus($lopetus, 'meta');
    }
  }

  if ($tee == "K") {

    // näytetään vielä laskun tiedot, ettei kohdisteta päin berberiä
    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Ytunnus")."</th>";
    echo "<th>".t("Nimi")."</th>";
    echo "<th>".t("Tapvm")."</th>";
    echo "<th>".t("Summa")."</th>";
    echo "<th>".t("Toimitusehto")."</th>";
    echo "</tr>";
    echo "<tr><td>$laskurow[ytunnus]</td><td>$laskurow[nimi]</td><td>$laskurow[tapvm]</td><td>$laskurow[summa] $laskurow[valkoodi]</td><td>$laskurow[toimitusehto]</td></tr>";
    echo "</table><br>";

    $query  = "SELECT sum(tuotemassa*(varattu+kpl)) massa, sum(varattu+kpl) kpl, sum(if(tuotemassa!=0, varattu+kpl, 0)) kplok
               FROM tilausrivi
               JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
               WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
               and tilausrivi.otunnus in ($otunnus)";
    $painoresult = pupe_query($query);
    $painorow = mysql_fetch_assoc($painoresult);

    if ($painorow["kpl"] > 0) {
      $osumapros = round($painorow["kplok"] / $painorow["kpl"] * 100, 2);
    }
    else {
      $osumapros = "N/A";
    }

    echo "<font class='message'>".sprintf(t("Tilauksen paino tuoterekisterin tietojen mukaan on: %s KG, %s %%:lle kappaleista on annettu paino."), $painorow["massa"], $osumapros)."</font><br><br>";

    echo "<table>";
    echo "<form method='post' name='paaformi'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='asiakasid' value='$asiakasid'>
        <input type='hidden' name='toiminto' value='lisatiedot'>
        <input type='hidden' name='otunnus' value='$otunnus'>
        <input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='tee' value='update'>";

    if ($toim == "TYOMAARAYS") {
      echo "<tr>";
      echo "<th>", t("Tullinimike"), "</th>";
      echo "<td>";
      echo "<br>";
      echo livesearch_kentta("paaformi", 'TULLINIMIKEHAKU', 'tullikoodi', 140, $laskurow['tullikoodi'], 'EISUBMIT', '', '', 'ei_break_all');
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>", t("Tulliarvo"), "</th>";
      echo "<td>";
      echo "<br>";
      echo "<input type='text' name='tulliarvo' value='{$laskurow['tulliarvo']}' />";
      echo "</td>";
      echo "</tr>";
    }

    $query = "SELECT sum(kollit) kollit, sum(kilot) kilot
              FROM rahtikirjat
              WHERE otsikkonro in ($otunnus)
              and yhtio        = '$kukarow[yhtio]'";
    $result = pupe_query($query);
    $rahtirow = mysql_fetch_assoc($result);

    if ($laskurow["bruttopaino"] == 0) $laskurow["bruttopaino"] = $rahtirow["kilot"];

    echo "<tr>";
    echo "<th>".t("Bruttopaino").":</th>";
    echo "<td><input type='text' name='bruttopaino' value='$laskurow[bruttopaino]' style='width:300px;'></td>";
    echo "</tr>";

    if ($toim == "TYOMAARAYS") {
      echo "<tr>";
      echo "<th>".t("Alkuperämaa").":</th>";
      echo "<td>";
      echo "<select name='maa_alkupera' style='width:300px;'>";

      $query = "SELECT distinct koodi, nimi
                FROM maat
                where nimi != ''
                ORDER BY koodi";
      $result = pupe_query($query);

      echo "<option value=''>".t("Valitse")."</option>";

      while ($row = mysql_fetch_assoc($result)) {
        $sel = $row["koodi"] == $laskurow["maa_alkupera"] ? 'selected' : '';
        echo "<option value='$row[koodi]' $sel>$row[nimi]</option>";
      }
      echo "</select></td>";
      echo "</tr>";
    }

    echo "<tr>";
    echo "<th>".t("Lähetysmaa").":</th>";
    echo "<td>";
    echo "<select name='maa_lahetys' style='width:300px;'>";

    $query = "SELECT distinct koodi, nimi
              FROM maat
              where nimi != ''
              ORDER BY koodi";
    $result = pupe_query($query);

    echo "<option value=''>".t("Valitse")."</option>";

    while ($row = mysql_fetch_assoc($result)) {
      $sel = '';
      if ($row["koodi"] == $laskurow["maa_lahetys"]) {
        $sel = 'selected';
      }
      echo "<option value='$row[koodi]' $sel>$row[nimi]</option>";
    }
    echo "</select></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>".t("Määrämaan koodi").":</th>";
    echo "<td>";
    echo "<select name='maa_maara' style='width:300px;'>";

    $query = "SELECT distinct koodi, nimi
              FROM maat
              where nimi != ''
              ORDER BY koodi";
    $result = pupe_query($query);

    if ($laskurow["maa_maara"] == "") $laskurow["maa_maara"] = $yhtiorow["maa"];

    while ($row = mysql_fetch_assoc($result)) {
      $sel = '';
      if ($row["koodi"] == $laskurow["maa_maara"]) {
        $sel = 'selected';
      }
      echo "<option value='$row[koodi]' $sel>$row[nimi]</option>";
    }
    echo "</select></td>";
    echo "</tr>";

    if ($laskurow["tuontipvm"] == '0000-00-00') {
      $pp = date('d');
      $kk = date('m');
      $vv = date('Y');
      $laskurow["tuontipvm"] = $vv."-".$kk."-".$pp;
    }

    echo "<tr>";
    echo "<th>".t("Kauppatapahtuman luonne").":</th>";
    echo "<td>";
    echo "<select name='kauppatapahtuman_luonne' style='width:300px;'>";

    $result = t_avainsana("KT");

    echo "<option value=''>".t("Valitse")."</option>";

    while ($row = mysql_fetch_assoc($result)) {
      $sel = '';
      if ($row["selite"] == $laskurow["kauppatapahtuman_luonne"]) {
        $sel = 'selected';
      }
      echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
    }

    echo "</select></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>".t("Kuljetusmuoto").":</th>";
    echo "<td>";
    echo "<select name='kuljetusmuoto' style='width:300px;'>";

    $result = t_avainsana("KM");

    echo "<option value=''>".t("Valitse")."</option>";

    while ($row = mysql_fetch_assoc($result)) {
      $sel = '';
      if ($row["selite"] == $laskurow["kuljetusmuoto"]) {
        $sel = 'selected';
      }
      echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
    }

    echo "</select></td>";
    echo "</tr>";

    echo "</table>";

    echo "<input type='hidden' name='tapa' value='$tapa'";
    echo "<br><input type='submit' value='".t("Päivitä tiedot")."'>";
    echo "</form>";

    echo "<br><br>";
    $tunnus = $otunnus;
    require "raportit/naytatilaus.inc";
  }
}
elseif ($tee != "") {
  if ($tee == 'L') {

    list($poistumistoimipaikka, $poistumistoimipaikka_koodi) = explode("##", $poistumistoimipaikka, 2);

    if ($aktiivinen_kuljetus_kansallisuus == '') {
      $aktiivinen_kuljetus_kansallisuus = $sisamaan_kuljetus_kansallisuus;
    }

    $aktiivinen_kuljetus_kansallisuus = strtoupper($aktiivinen_kuljetus_kansallisuus);
    $sisamaan_kuljetus_kansallisuus = strtoupper($sisamaan_kuljetus_kansallisuus);
    $maa_maara = strtoupper($maa_maara);

    $otunnukset = explode(',', $otunnus);

    foreach ($otunnukset as $otun) {

      // lasketaan rahtikirjalta jos miellä on nippu tilauksia tai jos bruttopainoa ei ole annettu käyttöliittymästä
      if (count($otunnukset) > 1 or !isset($bruttopaino) or (int) $bruttopaino == 0) {
        $query = "SELECT sum(kilot) kilot
                  FROM rahtikirjat
                  WHERE otsikkonro = '$otun' and yhtio='$kukarow[yhtio]'";
        $result   = pupe_query($query);
        $rahtirow = mysql_fetch_assoc($result);
        $bruttopaino = $rahtirow['kilot'];
      }

      $query = "SELECT varasto from lasku where yhtio = '$kukarow[yhtio]' and tunnus = '$otun'";
      $laskun_res = pupe_query($query);
      $laskun_row = mysql_fetch_assoc($laskun_res);

      $query = "SELECT maa from varastopaikat where yhtio = '$kukarow[yhtio]' and tunnus = '$laskun_row[varasto]'";
      $varaston_res = pupe_query($query);
      $varaston_row = mysql_fetch_assoc($varaston_res);

      $ultilno = tarvitaanko_intrastat($varaston_row["maa"], $maa_maara);

      $query = "UPDATE lasku
                SET maa_maara                  = '$maa_maara',
                maa_lahetys                    = '$varaston_row[maa]',
                kauppatapahtuman_luonne        = '$kauppatapahtuman_luonne',
                kuljetusmuoto                  = '$kuljetusmuoto',
                sisamaan_kuljetus              = '$sisamaan_kuljetus',
                sisamaan_kuljetusmuoto         = '$sisamaan_kuljetusmuoto',
                sisamaan_kuljetus_kansallisuus = '$sisamaan_kuljetus_kansallisuus',
                kontti                         = '$kontti',
                aktiivinen_kuljetus            = '$aktiivinen_kuljetus',
                aktiivinen_kuljetus_kansallisuus= '$aktiivinen_kuljetus_kansallisuus',
                poistumistoimipaikka           = '$poistumistoimipaikka',
                poistumistoimipaikka_koodi     = '$poistumistoimipaikka_koodi',
                aiotut_rajatoimipaikat         = '$aiotut_rajatoimipaikat',
                maaratoimipaikka               = '$maaratoimipaikka',
                bruttopaino                    = '$bruttopaino',
                lisattava_era                  = '$lisattava_era',
                vahennettava_era               = '$vahennettava_era',
                comments                       = '$lomake_lisatiedot',
                ultilno                        = '$ultilno'
                WHERE tunnus = '$otun'
                and yhtio    = '$kukarow[yhtio]'";
      $result = pupe_query($query);

      //päivitetään alatila vain jos tilaus ei vielä ole laskutettu
      $query = "UPDATE lasku
                SET alatila = 'E'
                WHERE yhtio    = '$kukarow[yhtio]'
                and tunnus     = '$otun'
                and tila       = 'L'
                and jaksotettu = 0
                and alatila    NOT IN ('X', 'J')";
      $result = pupe_query($query);

      //päivitetään alatila vain jos tilauksella on maksupositioita
      $query = "UPDATE lasku SET
                alatila        = 'J'
                WHERE yhtio    = '{$kukarow['yhtio']}'
                and tunnus     = '{$otun}'
                and tila       = 'L'
                and jaksotettu > 0
                and alatila    NOT IN ('X', 'J', 'D')";
      $result = pupe_query($query);
    }

    if (empty($from_viennin_lisatiedot_funktio)) {
      $tee = '';
    }

    if ($lopetus != "") {
      lopetus($lopetus, 'meta');
    }
  }

  if ($tee == 'K') {

    echo "<table>";
    echo "<form method='post'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='otunnus' value='$otunnus'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='tee' value='L'>";

    $query = "SELECT *
              FROM lasku
              WHERE tunnus in ($otunnus) and yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);
    $laskurow = mysql_fetch_assoc($result);

    $query = "SELECT sum(kollit) kollit, sum(kilot) kilot
              FROM rahtikirjat
              WHERE otsikkonro in ($otunnus) and yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);
    $rahtirow = mysql_fetch_assoc($result);

    if ($laskurow["bruttopaino"] == 0) $laskurow["bruttopaino"] = $rahtirow["kilot"];

    $query = "SELECT * from asiakas WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$laskurow[liitostunnus]'";
    $result = pupe_query($query);
    $asiakasrow = mysql_fetch_assoc($result);

    $query  = "SELECT sum(tuotemassa*(varattu+kpl)) massa, sum(varattu+kpl) kpl, sum(if(tuotemassa!=0, varattu+kpl, 0)) kplok
               FROM tilausrivi
               JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
               WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.otunnus in ($otunnus)";
    $painoresult = pupe_query($query);
    $painorow = mysql_fetch_assoc($painoresult);

    if ($painorow["kpl"] > 0) {
      $osumapros = round($painorow["kplok"] / $painorow["kpl"] * 100, 2);
    }
    else {
      $osumapros = "N/A";
    }

    // otetaan defaultit asiakkaalta jos laskulla ei ole mitään
    if ($laskurow["poistumistoimipaikka_koodi"]       == "") $laskurow["poistumistoimipaikka_koodi"]       = $asiakasrow["poistumistoimipaikka_koodi"];
    if ($laskurow["kuljetusmuoto"]                    ==  0) $laskurow["kuljetusmuoto"]                    = $asiakasrow["kuljetusmuoto"];
    if ($laskurow["kauppatapahtuman_luonne"]          ==  0) $laskurow["kauppatapahtuman_luonne"]          = $asiakasrow["kauppatapahtuman_luonne"];
    if ($laskurow["aktiivinen_kuljetus_kansallisuus"] == "") $laskurow["aktiivinen_kuljetus_kansallisuus"] = $asiakasrow["aktiivinen_kuljetus_kansallisuus"];
    if ($laskurow["aktiivinen_kuljetus"]              == "") $laskurow["aktiivinen_kuljetus"]              = $asiakasrow["aktiivinen_kuljetus"];
    if ($laskurow["kontti"]                           ==  0) $laskurow["kontti"]                           = $asiakasrow["kontti"];
    if ($laskurow["sisamaan_kuljetusmuoto"]           ==  0) $laskurow["sisamaan_kuljetusmuoto"]           = $asiakasrow["sisamaan_kuljetusmuoto"];
    if ($laskurow["sisamaan_kuljetus_kansallisuus"]   == "") $laskurow["sisamaan_kuljetus_kansallisuus"]   = $asiakasrow["sisamaan_kuljetus_kansallisuus"];
    if ($laskurow["sisamaan_kuljetus"]                == "") $laskurow["sisamaan_kuljetus"]                = $asiakasrow["sisamaan_kuljetus"];
    if ($laskurow["maa_maara"]                        == "") $laskurow["maa_maara"]                        = $asiakasrow["maa_maara"];

    echo "<tr>";
    echo "<th>6.</th>";
    echo "<th>".t("Kollimäärä")."</th>";
    echo "<td>$rahtirow[kollit]</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>17.</th>";
    echo "<th>".t("Määrämaan koodi").":</th>";
    echo "<td>";

    $query = "SELECT distinct koodi, nimi
              FROM maat
              where nimi != ''
              ORDER BY koodi";
    $maat_result = pupe_query($query);

    echo "<select name='maa_maara' style='width:300px;'>";
    echo "<option value=''>".t("Valitse")."</option>";

    while ($row = mysql_fetch_assoc($maat_result)) {
      $sel = '';
      if ($row["koodi"] == $laskurow["maa_maara"]) {
        $sel = 'selected';
      }
      echo "<option value='$row[koodi]' $sel>$row[nimi]</option>";
    }
    echo "</select>";
    echo "</td>";

    echo "<td class='back'>".t("Pakollinen kenttä")."</td></tr>";

    if ($laskurow["vienti"] == "K") {
      echo "<tr>";
      echo "<th>18.</th>";
      echo "<th>".t("Sisämaan kuljetusväline").":</th>";
      echo "<td>";
      echo "<input type='text' name='sisamaan_kuljetus' style='width:200px;' value='$laskurow[sisamaan_kuljetus]'>";

      echo "<select name='sisamaan_kuljetus_kansallisuus' style='width:100px;'>";
      echo "<option value=''>".t("Valitse")."</option>";

      mysql_data_seek($maat_result, 0);

      while ($row = mysql_fetch_assoc($maat_result)) {
        $sel = '';
        if ($row["koodi"] == $laskurow["sisamaan_kuljetus_kansallisuus"]) {
          $sel = 'selected';
        }
        echo "<option value='$row[koodi]' $sel>$row[nimi]</option>";
      }
      echo "</select>";

      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>26.</th>";
      echo "<th>".t("Sisämaan kuljetusmuoto").":</th>";
      echo "<td>";
      echo "<select name='sisamaan_kuljetusmuoto' style='width:300px;'>";

      $result = t_avainsana("KM");

      echo "<option value=''>".t("Valitse")."</option>";

      while ($row = mysql_fetch_assoc($result)) {
        $sel = '';
        if ($row["selite"] == $laskurow["sisamaan_kuljetusmuoto"]) {
          $sel = 'selected';
        }
        echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
      }
      echo "</select></td>";
      echo "<td class='back'>".t("Pakollinen kenttä")."</td>";
      echo "</tr>";

      $chk1 = '';
      $chk2 = '';
      if ($laskurow["kontti"] == 1) {
        $chk1 = 'checked';
      }
      if ($laskurow["kontti"] == 0) {
        $chk2 = 'checked';
      }

      echo "<tr>";
      echo "<th>19.</th>";
      echo "<th>".t("Kulkeeko tavara kontissa").":</th>";
      echo "<td>Kyllä <input type='radio' name='kontti' value='1' $chk1> Ei <input type='radio' name='kontti' value='0' $chk2></td>";
      echo "<td class='back'>".t("Pakollinen kenttä")."</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>21.</th>";
      echo "<th>".t("Aktiivisen kuljetusvälineen tunnus ja kansalaisuus").":</th>";
      echo "<td>";
      echo "<input type='text' name='aktiivinen_kuljetus' style='width:200px;' value='$laskurow[aktiivinen_kuljetus]'>";
      echo "<select name='aktiivinen_kuljetus_kansallisuus' style='width:100px;'>";
      echo "<option value=''>".t("Valitse")."</option>";

      mysql_data_seek($maat_result, 0);

      while ($row = mysql_fetch_assoc($maat_result)) {
        $sel = '';
        if ($row["koodi"] == $laskurow["aktiivinen_kuljetus_kansallisuus"]) {
          $sel = 'selected';
        }
        echo "<option value='$row[koodi]' $sel>$row[nimi]</option>";
      }
      echo "</select>";

      echo "</td>";
      echo "<td class='back'>Voidaan jättää tyhjäksi jos asiakas täyttää</td>";
      echo "</tr>";
    }

    echo "<tr>";
    echo "<th>24.</th>";
    echo "<th>".t("Kauppatapahtuman luonne").":</th>";
    echo "<td>";
    echo "<select NAME='kauppatapahtuman_luonne' style='width:300px;'>";

    $result = t_avainsana("KT");

    echo "<option value=''>".t("Valitse")."</option>";

    while ($row = mysql_fetch_assoc($result)) {
      $sel = '';
      if ($row["selite"] == $laskurow["kauppatapahtuman_luonne"]) {
        $sel = 'selected';
      }
      echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
    }

    echo "</select></td>";
    echo "<td class='back'>".t("Pakollinen kenttä")."</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>25.</th>";
    echo "<th>".t("Kuljetusmuoto rajalla").":</th>";
    echo "<td>";
    echo "<select NAME='kuljetusmuoto' style='width:300px;'>";

    $result = t_avainsana("KM");

    echo "<option value=''>".t("Valitse")."</option>";

    while ($row = mysql_fetch_assoc($result)) {
      $sel = '';
      if ($row["selite"] == $laskurow["kuljetusmuoto"]) {
        $sel = 'selected';
      }
      echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
    }
    echo "</select></td>";
    echo "<td class='back'>".t("Pakollinen kenttä")."</td>";
    echo "</tr>";

    if ($laskurow["vienti"] == "K") {
      echo "<tr>";
      echo "<th>29.</th>";
      echo "<th>".t("Poistumistoimipaikka").":</th>";
      echo "<td>";
      echo "<select name='poistumistoimipaikka' style='width:300px;'>";
      echo "<option value = '##'>".t("Valitse")."</option>";

      $vresult = t_avainsana("TULLI");

      while ($vrow = mysql_fetch_assoc($vresult)) {
        $sel = "";
        if ($laskurow["poistumistoimipaikka_koodi"] == $vrow["selite"]) {
          $sel = "selected";
        }
        echo "<option value = '$vrow[selitetark]##$vrow[selite]' $sel>$vrow[selitetark] $vrow[selite]</option>";
      }

      echo "</select></td>";
      echo "<td class='back'>".t("Pakollinen kenttä")."</td>";
      echo "</tr>";

      if ($laskurow["lisattava_era"] == 0) {
        $laskurow["lisattava_era"] = $yhtiorow["tulli_lisattava_era"];
      }
      if ($laskurow["vahennettava_era"] == 0) {
        $laskurow["vahennettava_era"] = $yhtiorow["tulli_vahennettava_era"];
      }

      echo "<tr>";
      echo "<th>28.</th>";
      echo "<th>".t("Vähennettävä erä, ulkomaiset kustannukset")."</th>";
      echo "<td><input type='text' name='vahennettava_era' style='width:300px;' value='$laskurow[vahennettava_era]'></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>28.</th>";
      echo "<th>".t("Toimitusehdon mukainen lisättävä erä")."</th>";
      echo "<td><input type='text' name='lisattava_era' style='width:300px;' value='$laskurow[lisattava_era]'></td>";
      echo "</tr>";
    }

    echo "<tr>";
    echo "<th>35.</th>";
    echo "<th>".t("Bruttopaino").":</th>";
    echo "<td><input type='text' name='bruttopaino' value='$laskurow[bruttopaino]' style='width:300px;'></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>44.</th>";
    echo "<th>".t("Lisätiedot")."</th>";
    echo "<td><input type='text' name='lomake_lisatiedot' style='width:300px;' value='$laskurow[comments]'></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>51.</th>";
    echo "<th>" . t("Aiotut rajatoimipaikat (ja maat)") . "</th>";
    echo "<td>";
    echo "<select name='aiotut_rajatoimipaikat' style='width:300px;'>";
    echo "<option>" . t("Ei valittu") . "</option>";

    $aiotut_rajatoimipaikat = t_avainsana("RAJATOIMIPAIKAT");

    while ($rajatoimipaikka = mysql_fetch_assoc($aiotut_rajatoimipaikat)) {
      $sel = $rajatoimipaikka["selite"] == $laskurow["aiotut_rajatoimipaikat"] ? " selected" : "";

      echo "<option{$sel} value='{$rajatoimipaikka["selite"]}'>{$rajatoimipaikka["selitetark"]}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>53.</th>";
    echo "<th>" . t("Määrätoimipaikka (ja maa)") . "</th>";
    echo "<td>";
    echo "<select name='maaratoimipaikka' style='width:300px;'>";
    echo "<option>" . t("Ei valittu") . "</option>";

    $maaratoimipaikat = t_avainsana("MAARATOIMPAIKKA");

    while ($maaratoimipaikka = mysql_fetch_assoc($maaratoimipaikat)) {
      $sel = $maaratoimipaikka["selite"] == $laskurow["maaratoimipaikka"] ? " selected" : "";

      echo "<option{$sel} value='{$maaratoimipaikka["selite"]}'>{$maaratoimipaikka["selitetark"]}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";

    echo "</table>";
    echo "<br><input type='submit' value='".t("Päivitä tiedot")."'>";
    echo "</form>";

    echo "<br><br><font class='message'>".sprintf(t("Tilauksen paino tuoterekisterin tietojen mukaan on: %s KG, %s %%:lle kappaleista on annettu paino."), $painorow["massa"], $osumapros)."</font><br><br>";

    $tunnus = $otunnus;
    require "raportit/naytatilaus.inc";
  }
}

// meillä ei ole valittua tilausta
if ($tee == '' and $toim == "MUOKKAA") {

  $formi  = "find";
  $kentta = "etsi";

  // tehdään etsi valinta
  echo "<form name='find' method='post'>";
  echo "<input type='hidden' name='toim' value='$toim'>";

  echo "<table>";
  echo "<tr>";
  echo "<th>";
  echo t("Valitse tapa");
  echo "</th>";
  echo "<td>";

  $seltuoteni = "";

  if ($tapa == "tuonti") {
    $seltuoteni = "SELECTED";
  }

  echo "<select name='tapa'>";
  echo "<option value='vienti'>".t("Vienti")."</option>";
  echo "<option value='tuonti' $seltuoteni>".t("Tuonti")."</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>";
  echo t("Syötä nimi")." / ".t("Laskunumero")." / ".t("Saapumisnumero");
  echo "</th>";
  echo "<td>";
  echo "<input type='text' name='etsi' value='$etsi'>";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "<br><input type='submit' class='hae_btn' value='".t("Etsi")."'>";
  echo "</form><br><br>";

  if (trim($etsi) != "") {

    $haku = '';
    if (is_string($etsi))  $haku = "and lasku.nimi LIKE '%$etsi%'";
    if (is_numeric($etsi)) $haku = "and lasku.laskunro='$etsi' or lasku.tunnus='$etsi'";

    if ($tapa == "tuonti") $tila = " and lasku.tila='K' and lasku.vanhatunnus=0 ";
    else $tila = " and lasku.tila in ('L','U') and lasku.alatila IN ('X', 'J') ";

    $query = "SELECT lasku.laskunro, lasku.nimi, lasku.luontiaika, kuka.nimi laatija, lasku.vienti, lasku.tapvm, group_concat(lasku.tunnus) tunnus
              FROM lasku
              LEFT JOIN kuka on kuka.yhtio = lasku.yhtio and kuka.tunnus = lasku.myyja
              WHERE lasku.yhtio  = '$kukarow[yhtio]'
              and lasku.vienti  != ''
              $tila
              $haku
              GROUP BY lasku.laskunro
              ORDER BY lasku.tapvm desc
              LIMIT 50";
    $tilre = pupe_query($query);

    echo "<table>";

    if (mysql_num_rows($tilre) > 0) {

      echo "<tr>";

      echo "<th>".t("Laskunro")."</th>";
      echo "<th>".t("Asiakas")."</th>";
      echo "<th>".t("Laadittu")."</th>";
      echo "<th>".t("Laatija")."</th>";
      echo "<th>".t("Vienti")."</th>";
      echo "<th>".t("Tapvm")."</th>";

      echo "</tr>";

      while ($tilrow = mysql_fetch_assoc($tilre)) {

        echo "<tr>";

        echo "<td>$tilrow[laskunro]</td>";
        echo "<td>$tilrow[nimi]</td>";
        echo "<td>".tv1dateconv($tilrow["luontiaika"], "P")."</td>";
        echo "<td>$tilrow[laatija]</td>";
        echo "<td>$tilrow[vienti]</td>";
        echo "<td>".tv1dateconv($tilrow["tapvm"])."</td>";

        echo "<td class='back'>
            <form method='post'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='otunnus' value='$tilrow[tunnus]'>
            <input type='hidden' name='tee' value='K'>
            <input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='tapa' value='$tapa'>
            <input type='submit' value='".t("Valitse")."'></form></td>";

      }
      echo "</tr>";
    }
    else {
      echo "<tr>";
      echo "<th colspan='5'>".t("Yhtään laskua ei löytynyt")."!</th>";
      echo "</tr>";
    }

    echo "</table>";
  }
}
elseif ($tee == '') {

  $formi="find";
  $kentta="etsi";

  // tehdään etsi valinta
  echo "<form name='find' method='post'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<table>";
  echo "<tr>";
  echo "<th><label for='etsi'>" . t("Etsi tilausta (asiakkaan nimellä / tilausnumerolla)") . ":</label></th>";
  echo "<td><input type='text' name='etsi' id='etsi'></td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th><label for='toimittamattomat'>" . t("Näytä myös toimittamattomat / keskeneräiset tilaukset") . "</label></th>";

  $checked = isset($toimittamattomat) && $toimittamattomat == 1 ? " checked" : "";

  echo "<td><input type='checkbox' name='toimittamattomat' id='toimittamattomat' value='1' {$checked}/></td>";
  echo "</tr>";
  echo "<tr>";
  echo "<td class='back'><input type='submit' class='hae_btn' value='" . t("Etsi") . "' name='find_submit'></td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";

  $haku='';
  if (is_string($etsi))  $haku="and lasku.nimi LIKE '%$etsi%'";
  if (is_numeric($etsi)) $haku="and lasku.tunnus='$etsi'";

  if ($toim == "TYOMAARAYS") {
    $tyomaarays_tilaehto = "A";
    $tilaehto = "AND lasku.tilaustyyppi = 'A' ";
  }
  else {
    $tyomaarays_tilaehto = "N";
    $tilaehto = "";
  }

  // ehto, millä valitaan mukaan laskutetut tilaukset
  // vain avoin kausi. ei sisäisiä eikä KTL 999, koska niistä ei lähetetä intrastattia
  $laskutetut = "lasku.tila = 'L'
    AND lasku.alatila = 'X'
    AND lasku.tapvm >= '{$yhtiorow['tilikausi_alku']}'
    AND lasku.sisainen = ''
    AND lasku.kauppatapahtuman_luonne != '999'";

  if (isset($toimittamattomat) and $toimittamattomat == 1) {
    $tilaehto .= "AND ((lasku.tila = 'L' AND lasku.alatila NOT IN ('X')) OR (lasku.tila = '{$tyomaarays_tilaehto}') OR ({$laskutetut}))";
  }
  else {
    $tilaehto .= "AND ((lasku.tila = 'L' AND lasku.alatila IN ('B','D','E','J')) OR ({$laskutetut}))";
  }

  if ($toim == "TYOMAARAYS") {
    $tyomaaraysjoin = "JOIN tyomaarays ON (tyomaarays.yhtio = lasku.yhtio and tyomaarays.otunnus = lasku.tunnus)";
    $selectlisa = "tyomaarays.maa_maara, tyomaarays.kuljetusmuoto, tyomaarays.kauppatapahtuman_luonne,";
  }
  else {
    $tyomaaraysjoin = "";
    $selectlisa = "lasku.maa_maara, lasku.kuljetusmuoto, lasku.kauppatapahtuman_luonne,";
  }

  //listataan laskuttamattomat tilausket
  $query = "SELECT lasku.tunnus as tilaus,
            lasku.nimi as asiakas,
            lasku.luontiaika as laadittu,
            lasku.laatija,
            lasku.vienti,
            lasku.erpcm,
            lasku.ytunnus,
            lasku.nimi,
            lasku.nimitark,
            lasku.postino,
            lasku.postitp,
            lasku.maksuehto,
            lasku.lisattava_era,
            lasku.vahennettava_era,
            lasku.jaksotettu,
            lasku.ketjutus,
            {$selectlisa}
            lasku.sisamaan_kuljetus,
            lasku.sisamaan_kuljetusmuoto,
            lasku.poistumistoimipaikka,
            lasku.poistumistoimipaikka_koodi,
            lasku.alatila
            FROM lasku
            {$tyomaaraysjoin}
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            {$tilaehto}
            AND lasku.vienti  in ('K','E')
            {$haku}
            ORDER by 5,6,7,8,9,10,11,12,13,14,15";
  $tilre = pupe_query($query);

  echo "<br><br>";

  echo "<table>";

  if (mysql_num_rows($tilre) > 0) {
    echo "<tr class='aktiivi'>";

    for ($i = 0; $i < mysql_num_fields($tilre) - 18; $i++) {
      echo "<th align='left'>".t(mysql_field_name($tilre, $i))."</th>";
    }

    echo "<th>".t("Tyyppi")."</th>";
    echo "<th>".t("Lisätiedot")."</th>";
    echo "</tr>";

    $lask             = -1;
    $tunnukset        = '';
    $ketjutus         = '';
    $erpcm            = '';
    $ytunnus          = '';
    $nimi             = '';
    $nimitark         = '';
    $postino          = '';
    $postitp          = '';
    $maksuehto        = '';
    $lisattava_era    = '';
    $vahennettava_era = '';
    $jaksotettu       = '';

    while ($tilrow = mysql_fetch_assoc($tilre)) {

      // Katsotaan onko kaikki tiedot kunnossa
      if ((in_array($tilrow['alatila'], array('E', 'J', 'X')) or ($tilrow['alatila'] == 'D' and $tilrow['jaksotettu'] != 0))
        and $tilrow['vienti'] == 'K'
        and $tilrow['maa_maara'] != ''
        and $tilrow['kuljetusmuoto'] > 0
        and $tilrow['kauppatapahtuman_luonne'] > 0
        and $tilrow['sisamaan_kuljetusmuoto'] > 0
        and $tilrow['poistumistoimipaikka'] != ''
        and $tilrow['poistumistoimipaikka_koodi'] != '') {
        $rivi_ok = true;
      }
      elseif (((in_array($tilrow['alatila'], array('E', 'J', 'X')) or ($tilrow['alatila'] == 'D' and $tilrow['jaksotettu'] != 0)) or $toim == "TYOMAARAYS")
        and $tilrow['vienti'] == 'E'
        and $tilrow['maa_maara'] != ''
        and $tilrow['kuljetusmuoto'] > 0
        and $tilrow['kauppatapahtuman_luonne'] > 0) {
        $rivi_ok = true;
      }
      else {
        $rivi_ok = false;
      }

      // Jos laskutetut laskut on ok, ei näytetä niitä
      if ($tilrow['alatila'] == 'X' and $rivi_ok) {
        continue;
      }

      $query = "SELECT sum(if(varattu>0,1,0))  veloitus, sum(if(varattu<0,1,0)) hyvitys
                from tilausrivi
                where yhtio = '$kukarow[yhtio]'
                and otunnus = '$tilrow[tilaus]'";
      $hyvre = pupe_query($query);
      $hyvrow = mysql_fetch_assoc($hyvre);

      if (($tilrow['jaksotettu'] != 0 and $jaksotettu == $tilrow['jaksotettu']) or ($tilrow['jaksotettu'] == 0 and $ketjutus == '' and $erpcm == $tilrow["erpcm"] and $ytunnus == $tilrow["ytunnus"] and $nimi == $tilrow["nimi"] and $nimitark == $tilrow["nimitark"] and $postino == $tilrow["postino"] and $postitp == $tilrow["postitp"] and $maksuehto == $tilrow["maksuehto"] and $lisattava_era == $tilrow["lisattava_era"] and $vahennettava_era == $tilrow["vahennettava_era"])) {
        $tunnukset .= $tilrow["tilaus"].",";
        $lask++;
        echo "</tr>\n";
      }
      else {
        if ($lask >= 1) {
          $tunnukset = substr($tunnukset, 0, -1); // Vika pilkku pois
          echo "<td class='back'>
              <form method='post'>
              <input type='hidden' name='toim' value='$toim'>
              <input type='hidden' name='otunnus' value='$tunnukset'>
              <input type='hidden' name='tee' value='K'>
              <input type='submit' name='tila' value='".t("Ketjuta lisätiedot")."'></form></td>";
        }

        $tunnukset = $tilrow["tilaus"].",";

        if ($lask != -1) {
          echo "</tr>\n";
        }
        $lask = 0;
      }

      echo "\n\n<tr>";

      for ($i=0; $i < mysql_num_fields($tilre)-18; $i++) {
        $fieldname = mysql_field_name($tilre, $i);

        echo "<td>";

        if ($fieldname == 'laadittu') {
          echo tv1dateconv($tilrow[$fieldname], "PITKA");
        }
        else {
          echo $tilrow[$fieldname];
        }
        echo "</td>";
      }

      if ($hyvrow["veloitus"] > 0 and $hyvrow["hyvitys"] == 0) {
        $teksti = t("Veloitus");
      }
      if ($hyvrow["veloitus"] > 0 and $hyvrow["hyvitys"] > 0) {
        $teksti = t("Veloitusta ja hyvitystä");
      }
      if ($hyvrow["hyvitys"] > 0  and $hyvrow["veloitus"] == 0) {
        $teksti = t("Hyvitys");
      }

      echo "<td>";
      echo $teksti;
      if ($tilrow['alatila'] == 'X') {
        echo " " . t('Laskutettu');
      }
      echo "</td>";

      if ($rivi_ok) {
        echo "<td><font color='#00FF00'>".t("OK")."</font></td>";
      }
      else {
        echo "<td>".t("Kesken")."</td>";
      }

      echo "<td class='back'>
          <form method='post'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='otunnus' value='$tilrow[tilaus]'>
          <input type='hidden' name='tee' value='K'>
          <input type='submit' name='tila' value='".t("Valitse")."'>";

      if ($toim == "TYOMAARAYS") {
        echo "<input type='hidden' name='tapa' value='tuonti'>";
      }

      echo "</form></td>";

      $ketjutus      = $tilrow["ketjutus"];
      $erpcm        = $tilrow["erpcm"];
      $ytunnus      = $tilrow["ytunnus"];
      $nimi        = $tilrow["nimi"];
      $nimitark      = $tilrow["nimitark"];
      $postino      = $tilrow["postino"];
      $postitp      = $tilrow["postitp"];
      $maksuehto      = $tilrow["maksuehto"];
      $lisattava_era    = $tilrow["lisattava_era"];
      $vahennettava_era  = $tilrow["vahennettava_era"];
      $jaksotettu = $tilrow['jaksotettu'];
    }

    if ($tunnukset != '' and $lask >= 1) {
      $tunnukset = substr($tunnukset, 0, -1); // Vika pilkku pois

      echo "<td class='back'>
          <form method='post'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='otunnus' value='$tunnukset'>
          <input type='hidden' name='tee' value='K'>
          <input type='hidden' name='extra' value='K'>
          <input type='submit' name='tila' value='".t("Ketjuta lisätiedot")."'></form></td>";
      $tunnukset = '';
    }
    echo "</tr>";
  }
  else {
    echo "<tr>";
    echo "<th colspan='5'>".t("Ei tilauksia")."!</th>";
    echo "</tr>";
  }
  echo "</table><br>";
}

if (strpos($_SERVER['SCRIPT_NAME'], "vientitilauksen_lisatiedot.php") !== FALSE) {
  require "inc/footer.inc";
}
