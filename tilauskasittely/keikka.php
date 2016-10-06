<?php

$pupe_DataTables = 'keikka';

if (!empty($_REQUEST["lisatiedot_submit"])) {
  setcookie("saap_erikoisale_ui", $_REQUEST["saap_erikoisale_ui"]);
}
elseif (!empty($_COOKIE["saap_erikoisale_ui"])) {
  $saap_erikoisale_ui = $_COOKIE["saap_erikoisale_ui"];
}

if (!empty($_REQUEST["lisatiedot_submit"])) {
  setcookie("saap_rivihinta_ui", $_REQUEST["saap_rivihinta_ui"]);
}
elseif (!empty($_COOKIE["saap_rivihinta_ui"])) {
  $saap_rivihinta_ui = $_COOKIE["saap_rivihinta_ui"];
}

if (isset($_REQUEST["komento"]) and in_array("PDF_RUUDULLE", $_REQUEST["komento"])) {
  $_REQUEST["tee"] = $_POST["tee"] = $_GET["tee"] = "NAYTATILAUS";
}

if ((isset($_REQUEST["tee"]) and $_REQUEST["tee"] == 'NAYTATILAUS') or
  (isset($_POST["tee"]) and $_POST["tee"] == 'NAYTATILAUS') or
  (isset($_GET["tee"]) and $_GET["tee"] == 'NAYTATILAUS')) $nayta_pdf = 1; //Generoidaan .pdf-file

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php")  !== FALSE) {

  if (isset($_REQUEST["toiminto"]) and
      ($_REQUEST["toiminto"] == "kalkyyli" or $_REQUEST["toiminto"] == "kaikkiok" or ($_REQUEST["toiminto"] == "tulosta" and !empty($_REQUEST["tee_excel"])))) {
    // Ei k‰ytet‰ pakkausta
    $compression = FALSE;
  }

  require "../inc/parametrit.inc";
}

if ($tee == 'lataa_tiedosto') {
  $filepath = "/tmp/".$tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  exit;
}

if (isset($_POST['ajax_toiminto']) and trim($_POST['ajax_toiminto']) != '') {
  require "../inc/keikan_toiminnot.inc";
}

if (isset($livesearch_tee) and $livesearch_tee == "TUOTEHAKU") {
  livesearch_tuotehaku();
  exit;
}

if (!isset($tee))         $tee = "";
if (!isset($toiminto))       $toiminto = "";
if (!isset($keikkarajaus))     $keikkarajaus = "";
if (!isset($ytunnus))       $ytunnus = "";
if (!isset($keikka))       $keikka = "";
if (!isset($ostotil))       $ostotil = "";
if (!isset($toimittajaid))     $toimittajaid = "";
if (!isset($kauttalaskutus))   $kauttalaskutus = "";
if (!isset($mobiili_keikka))   $mobiili_keikka = "";
if (!isset($toimipaikka))    $toimipaikka = $kukarow['toimipaikka'];

$onkolaajattoimipaikat = ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) ? TRUE : FALSE;
$onkologmaster = (LOGMASTER_RAJAPINTA and in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'S')));

if ($onkolaajattoimipaikat and isset($otunnus)) {

  $otunnus = (int) $otunnus;

  // Saapuminen
  $query = "SELECT *
            from lasku
            where tunnus = '{$otunnus}'
            and tila     = 'K'
            and yhtio    = '{$kukarow['yhtio']}'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $laskurow = mysql_fetch_assoc($result);

    $kukarow['toimipaikka'] = $laskurow['yhtio_toimipaikka'];
    $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
  }
}

echo "<font class='head'>".t("Saapumiset")."</font><hr>";

if (in_array($yhtiorow["livetuotehaku_tilauksella"], array("J", "K"))) {
  enable_ajax();
}

// scripti balloonien tekemiseen
js_popup();

echo "<script type=\"text/javascript\" charset=\"utf-8\">
        $(document).ready(function() {
          // HAETAAN SALDO MYYTƒVISSƒ
          $('img.hae_saldo').live('mouseover', function() {
            $(this).css('cursor', 'pointer');
          });

          $('img.hae_saldo').live('click', function() {
            var id = $(this).attr('id');
            var varasto = $('#'+id+'_varasto').val(),
                tuoteno = $('#'+id+'_tuoteno').val();

            if ($('#div_'+id).is(':visible')) {
              $('#div_'+id).hide();
            }
            else {
              $.post('{$_SERVER['SCRIPT_NAME']}',
                {   ajax_toiminto: 'hae_saldo_myytavissa',
                    id: tuoteno,
                    varasto: varasto,
                    no_head: 'yes',
                    ohje: 'off' },
                function(return_value) {
                  var data = jQuery.parseJSON(return_value);

                  $('#span_'+id).html(
                    '<br />' +
                    '<li>".t("Saldo").": ' + data.saldo + '</li>' +
                    '<li>".t("Hyllyss‰").": ' + data.hyllyssa + '</li>' +
                    '<li>".t("Myyt‰viss‰").": ' + data.myytavissa + '</li>'
                  );

                  $('#div_'+id).show();
                });
            }
          });
        });
      </script>";

echo "<div id='toimnapit'></div>";

if (isset($nappikeikalle) and $nappikeikalle == 'menossa') {
  $query = "UPDATE kuka SET kesken = 0 where yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
  $nappiresult = pupe_query($query);
}

// yhdistetaan saapumiseen $otunnus muita keikkoja
if ($toiminto == "yhdista") {
  require 'ostotilausten_rivien_yhdistys.inc';
}

// poistetaan vanha saapuminen numerolla $keikkaid
if ($toiminto == "poista") {
  $eisaapoistaa = 0;

  $query  = "SELECT tunnus
             from tilausrivi
             where yhtio     = '$kukarow[yhtio]'
             and uusiotunnus = '$tunnus'
             and tyyppi      = 'O'";
  $delres = pupe_query($query);

  if (mysql_num_rows($delres) != 0) {
    $eisaapoistaa++;
  }

  $query = "SELECT tunnus
            from lasku
            where yhtio     = '$kukarow[yhtio]'
            and tila        = 'K'
            and vanhatunnus <> 0
            and laskunro    = '$laskunro'";
  $delres2 = pupe_query($query);

  if (mysql_num_rows($delres2) != 0) {
    $eisaapoistaa++;
  }

  if ($eisaapoistaa == 0) {

    $komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit‰tˆitiin ohjelmassa keikka.php")."<br>";

    // Mit‰tˆid‰‰n saapuminen
    $query  = "UPDATE lasku SET alatila = tila, tila = 'D', comments = '$komm' where yhtio='$kukarow[yhtio]' and tila='K' and laskunro='$keikkaid'";
    $result = pupe_query($query);

    // Mit‰tˆid‰‰n keikalle suoraan "lis‰tyt" rivit eli otunnus=keikan tunnus ja uusiotunnus=0
    $query  = "UPDATE tilausrivi SET tyyppi = 'D' where yhtio='$kukarow[yhtio]' and tyyppi='O' and otunnus='$otunnus' and uusiotunnus=0";
    $result = pupe_query($query);

    // Siirret‰‰n t‰lle kiekalle lis‰tyt sille keikalle jolle ne on kohdistettu
    $query  = "UPDATE tilausrivi SET otunnus=uusiotunnus where yhtio='$kukarow[yhtio]' and tyyppi='O' and otunnus='$otunnus' and uusiotunnus>0";
    $result = pupe_query($query);

    // formissa on tullut myˆs $ytunnus, joten n‰in p‰‰st‰‰n takaisin selaukseen
    $toiminto = "";
  }
  else {
    echo "<font class='error'>".t("VIRHE: Saapumiseen on jo liitetty laskuja tai kohdistettu rivej‰, sit‰ ei voi poistaa")."!<br>";
    // formissa on tullut myˆs $ytunnus, joten n‰in p‰‰st‰‰n takaisin selaukseen
    $toiminto = "";
  }
}

// tulostetaan tarvittavia papruja $otunnuksen mukaan
if ($toiminto == "tulosta") {
  // Haetaan itse saapuminen
  $query    = "SELECT *
               from lasku
               where tunnus = '$otunnus'
               and yhtio    = '$kukarow[yhtio]'";
  $result   = pupe_query($query);
  $laskurow = mysql_fetch_assoc($result);

  // katotaan liitetyt laskut
  $query = "SELECT
            GROUP_CONCAT(distinct vanhatunnus SEPARATOR ',') volaskutunn
            from lasku
            where yhtio     = '$kukarow[yhtio]'
            and tila        = 'K'
            and vienti      in ('C','F','I','J','K','L')
            and vanhatunnus <> 0
            and laskunro    = '$laskurow[laskunro]'
            HAVING volaskutunn is not null";
  $llres = pupe_query($query);

  if (mysql_num_rows($llres) > 0) {
    $llrow = mysql_fetch_assoc($llres);

    $query = "SELECT
              GROUP_CONCAT(viesti SEPARATOR ' ') viesti,
              GROUP_CONCAT(tapvm SEPARATOR ' ') tapvm
              from lasku
              where yhtio = '$kukarow[yhtio]'
              and tunnus  in ($llrow[volaskutunn])";
    $llres = pupe_query($query);
    $llrow = mysql_fetch_assoc($llres);

    $laskurow["tapvm "] = $llrow["tapvm"];
    $laskurow["viesti"] = $llrow["viesti"];
  }

  if (count($komento) == 0) {
    $tulostimet = array('Purkulista', 'Tuotetarrat', 'Tariffilista');

    if ($yhtiorow['suuntalavat'] == 'S' and $otunnus != '') {

      $on_jo_lava = trim($valitut_lavat) != "" ? true : false;
      $valitut_lavat = $on_jo_lava ? $valitut_lavat : "";

      $query = "SELECT GROUP_CONCAT(tunnus) tunnukset, group_concat(suuntalava) suuntalavat
                FROM tilausrivi
                WHERE yhtio      = '{$kukarow['yhtio']}'
                AND uusiotunnus  = '{$otunnus}'
                AND tyyppi       = 'O'
                AND suuntalava   > 0
                AND kpl         != 0";
      $check_result = pupe_query($query);
      $check_row = mysql_fetch_assoc($check_result);

      if (trim($check_row['tunnukset']) != '') {
        $tulostimet[] = "Vastaanottoraportti";

        $valitut_lavat = $on_jo_lava ? $valitut_lavat : $check_row["suuntalavat"];
      }

      $query = "SELECT GROUP_CONCAT(tunnus) tunnukset, group_concat(suuntalava) suuntalavat
                FROM tilausrivi
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND uusiotunnus = '{$otunnus}'
                AND tyyppi      = 'O'
                AND suuntalava  > 0
                AND kpl         = 0";
      $check_result = pupe_query($query);
      $check_row = mysql_fetch_assoc($check_result);

      if (trim($check_row['tunnukset']) != '') {
        $tulostimet[] = "Tavaraetiketti";

        if (!$on_jo_lava) {
          $valitut_lavat = trim($valitut_lavat) != "" ? $valitut_lavat.",".$check_row["suuntalavat"] : $check_row["suuntalavat"];
        }
      }
    }

    echo "<br><table>";
    echo "<tr>";
    echo "<th>".t("saapuminen")."</th>";
    echo "<th>".t("ytunnus")."</th>";
    echo "<th>".t("nimi")."</th>";
    echo "</tr>";
    echo "<tr>
        <td>$laskurow[laskunro]</td>
        <td>$laskurow[ytunnus]</td>
        <td>$laskurow[nimi]</td>
        </tr>";
    echo "</table><br>";

    require '../inc/valitse_tulostin.inc';
  }
  else {
    // takaisin selaukseen
    $toiminto     = "";
    $ytunnus      = $laskurow["ytunnus"];
    $toimittajaid = $laskurow["liitostunnus"];
  }

  if ($komento["Purkulista"] != '' or !empty($tee_excel)) {
    require 'tulosta_purkulista.inc';
  }

  if ($komento["Tuotetarrat"] != '') {
    require 'tulosta_tuotetarrat.inc';
  }

  if ($komento["Tariffilista"] != '') {
    require 'tulosta_tariffilista.inc';
  }

  if ($komento["Vastaanottoraportti"] != '') {
    require 'tulosta_vastaanottoraportti.inc';
  }

  if ($komento["Tavaraetiketti"] != '') {
    require 'tulosta_tavaraetiketti.inc';
  }
}

if ($toiminto == "tulosta_hintalaput") {
  require "tulosta_hintalaput.inc";

  $tuotteet = hae_tuotteet_hintalappuja_varten($otunnus, $kukarow);
  list($tiedostonimi, $kaunisnimi) = tulosta_hintalaput($tuotteet);

  echo "<font class='ok'>" . t("Hintalaput tulostettu") . "</font>";

  echo "<form method='post' class='multisubmit'>";
  echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
  echo "<input type='hidden' name='lataa_tiedosto' value='1'>";
  echo "<input type='hidden' name='kaunisnimi' value='{$kaunisnimi}'>";
  echo "<input type='hidden' name='tmpfilenimi' value='{$tiedostonimi}'>";
  echo "<input type='submit' value='" . t("Tallenna hintalaput") . "'>";
  echo "</form>";

  $toiminto = "";
}

if ($onkologmaster and $toiminto == "saapuminen_ulkoiseen_jarjestelmaan") {
  $saapumisnro = $otunnus;

  require "rajapinnat/logmaster/inbound_delivery.php";

  $toiminto = "kohdista";
}

// syˆtet‰‰n keikan lis‰tietoja
if ($toiminto == "lisatiedot") {
  require "ostotilauksen_lisatiedot.inc";
}

// chekataan tilauksen varastopaikat
if ($toiminto == "varastopaikat") {
  require 'ostorivienvarastopaikat.inc';
}

// lis‰ill‰‰n saapumiseen kululaskuja
if ($toiminto == "kululaskut") {
  $keikanalatila   = "";

  require 'kululaskut.inc';
}

if ($toiminto == 'kalkyyli' and $yhtiorow['suuntalavat'] == 'S' and $tee == '' and trim($suuntalavan_tunnus) != '' and trim($koko_suuntalava) == 'X') {
  if ((isset($suuntalavanhyllyalue) and trim($suuntalavanhyllyalue) == '') or (isset($suuntalavanhyllypaikka) and trim($suuntalavanhyllypaikka) == '')) {
    echo "<font class='error'>", t("Hyllyalue oli tyhj‰"), "!</font><br />";
    $toiminto = 'suuntalavat';
    $tee = 'vie_koko_suuntalava';
  }
  else {
    $vietiinko_koko_suuntalava = '';

    if (trim($suuntalavanhyllypaikka) != '') {
      list($suuntalavanhyllyalue, $suuntalavanhyllynro, $suuntalavanhyllyvali, $suuntalavanhyllytaso) = explode("#", $suuntalavanhyllypaikka);
    }

    $suuntalavanhyllyalue = mysql_real_escape_string($suuntalavanhyllyalue);
    $suuntalavanhyllynro  = mysql_real_escape_string($suuntalavanhyllynro);
    $suuntalavanhyllyvali = mysql_real_escape_string($suuntalavanhyllyvali);
    $suuntalavanhyllytaso = mysql_real_escape_string($suuntalavanhyllytaso);

    // Koko suuntalava voidaan vied‰ vain reservipaikalle, jossa ei ole tuotteita.
    $options = array('reservipaikka' => 'K');
    $hyllypaikka_ok = tarkista_varaston_hyllypaikka($suuntalavanhyllyalue, $suuntalavanhyllynro, $suuntalavanhyllyvali, $suuntalavanhyllytaso, $options);

    // Hyllypaikkaa ei lˆydy tai se ei ole reservipaikka
    if (!$hyllypaikka_ok) {
      echo "<font class='error'>".t("Hyllypaikkaa ei lˆydy tai se ei ole reservipaikka")."</font></br>";

      // Takaisin samaan n‰kym‰‰n
      $toiminto = 'suuntalavat';
      $tee = 'vie_koko_suuntalava';
    }
    else {
      // OK, p‰ivitet‰‰n tilausrivien hyllypaikat
      $paivitetyt_rivit = paivita_hyllypaikat($suuntalavan_tunnus,
        $suuntalavanhyllyalue,
        $suuntalavanhyllynro,
        $suuntalavanhyllyvali,
        $suuntalavanhyllytaso);

      if ($paivitetyt_rivit > 0) {
        echo "<br />", t("P‰ivitettiin suuntalavan tuotteet paikalle"), " {$suuntalavanhyllyalue} {$suuntalavanhyllynro} {$suuntalavanhyllyvali} {$suuntalavanhyllytaso}<br />";
        $vietiinko_koko_suuntalava = 'joo';
      }
    }
  }
}

if ($toiminto == 'suuntalavat') {
  require 'suuntalavat.inc';
}

if ($toiminto == 'tulosta_sscc') {
  require 'tulosta_sscc.inc';
}

// tehd‰‰n errorichekkej‰ jos on varastoonvienti kyseess‰
if ($toiminto == "kaikkiok" or $toiminto == "kalkyyli") {

  $varastoerror = saako_vieda_varastoon($otunnus, $toiminto, 'echota_virheet');

  if ($varastoerror != 0) {
    echo "<br><form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value=''>";
    echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
    echo "<input type='submit' value='".t("Takaisin")."'>";
    echo "</form>";

    $ytunnus = "";
    $toiminto = "dummieimit‰‰n";
  }
}

// lasketaan lopullinen varastonarvo
if ($toiminto == "kaikkiok") {
  require "varastonarvo_historia.inc";
}

// vied‰‰n keikka varastoon
if ($toiminto == "kalkyyli") {
  require "kalkyyli.inc";
}

if (isset($nappikeikalla) and $nappikeikalla == 'ollaan' and $toiminto != 'kohdista') {
  $toiminto = "kohdista";
}

if (isset($messenger) and $messenger == 'X' and isset($message) and trim($message) != "" and isset($vastaanottaja) and trim($vastaanottaja) != "" and isset($status) and $status == 'X') {

  $message = trim($message);

  $message .= " {$message_postfix}";

  $query = "INSERT INTO messenger SET
            yhtio         = '{$kukarow['yhtio']}',
            kuka          = '{$kukarow['kuka']}',
            vastaanottaja = '{$vastaanottaja}',
            viesti        = '{$message}',
            status        = '{$status}',
            luontiaika    = now()";
  $messenger_result = pupe_query($query);

  echo "<font class='message'>", sprintf(t('Viesti l‰hetetty onnistuneesti k‰ytt‰j‰lle %s.'), $vastaanottaja)."</font><br />";

  $messenger = $message = $vastaanottaja = $status = "";
}

if ($toiminto == "kohdista") {
  if (isset($poista) and $poista != '') {
    // T‰m‰ on naimisissa olevien osto- ja myyntitilausrivien saapumisten kautta poistamista varten
    //ostotilauksen tilausrivi on poistettu jo t‰ss‰ vaiheessa, rivitunnus on tallessa formissa ja tilausrivin_lisatiedot taulusta lˆytyy oston ja myynnin yhdist‰v‰ linkki
    tarkista_myynti_osto_liitos_ja_poista($rivitunnus, true);

    $tee = 'TI';
    $tyhjenna = true;
    unset($rivitunnus);
  }

  require 'ostotilausten_rivien_kohdistus.inc';
}

// Haku
if ($ytunnus == "" and $keikka != "") {
  $keikka = (int) $keikka;

  $query = "SELECT ytunnus, liitostunnus, laskunro
            FROM lasku USE INDEX (yhtio_tila_laskunro)
            WHERE lasku.yhtio     = '$kukarow[yhtio]'
            and lasku.tila        = 'K'
            and lasku.alatila     = ''
            and lasku.vanhatunnus = 0
            and lasku.laskunro    = $keikka";
  $keikkahaku_res = pupe_query($query);

  if (mysql_num_rows($keikkahaku_res) > 0) {
    $keikkahaku_row = mysql_fetch_assoc($keikkahaku_res);

    $keikkarajaus = $keikkahaku_row["laskunro"];
    $ytunnus     = $keikkahaku_row["ytunnus"];
    $toimittajaid = $keikkahaku_row["liitostunnus"];
  }
  else {
    $ostotil = "";
    $keikka  = "";
  }
}

// Haku
if ($ytunnus == "" and $ostotil != "") {
  $ostotil = (int) $ostotil;

  $query = "SELECT lasku.ytunnus, lasku.liitostunnus, group_concat(lasku.laskunro) laskunro
            FROM tilausrivi
            JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and lasku.tila = 'K' and lasku.alatila = '' and lasku.vanhatunnus = 0)
            WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
             and tilausrivi.otunnus = $ostotil
            and tilausrivi.tyyppi   = 'O'";
  $keikkahaku_res = pupe_query($query);
  $keikkahaku_row = mysql_fetch_assoc($keikkahaku_res);

  if ($keikkahaku_row['laskunro'] != '') {
    $keikkarajaus = $keikkahaku_row["laskunro"];
    $ytunnus     = $keikkahaku_row["ytunnus"];
    $toimittajaid = $keikkahaku_row["liitostunnus"];
  }
  else {
    // Napataan kuitenkin toimittajan keikat auki
    $query = "SELECT lasku.ytunnus, lasku.liitostunnus
              FROM tilausrivi
              JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and lasku.tila = 'O')
              WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
               and tilausrivi.otunnus = $ostotil
              and tilausrivi.tyyppi   = 'O'
              LIMIT 1";
    $keikkahaku_res = pupe_query($query);

    if (mysql_num_rows($keikkahaku_res) > 0) {

      echo "<font class='error'>".t("HUOM: Haettua ostotilausta ei lˆytynyt saapumisilta. N‰ytet‰‰n toimittajan kaikki avoimet saapumiset")."!</font><br><br>";

      $keikkahaku_row = mysql_fetch_assoc($keikkahaku_res);

      $ytunnus     = $keikkahaku_row["ytunnus"];
      $toimittajaid = $keikkahaku_row["liitostunnus"];
    }

    $ostotil = "";
    $keikka  = "";
  }
}

// jos ollaan annettu $ytunnus haetaan toimittajan tiedot arrayseen $toimittajarow
if ($ytunnus != "" or $toimittajaid != "") {
  $ytunnus   = isset($ytunnus) ? $ytunnus : '';
  $keikkamonta = 0;
  $hakutunnus  = $ytunnus;
  $hakuid     = $toimittajaid;

  require "inc/kevyt_toimittajahaku.inc";

  $keikkamonta += $monta;

  if ($keikkamonta > 1) {
    $toimittajaid  = "";
    $toimittajarow = "";
    $ytunnus      = "";
  }
}

// N‰ytet‰‰n kaikkien toimittajien keskener‰iset saapumiset
if ($toiminto == "" and $ytunnus == "" and $keikka == "") {

  if (!isset($nayta_siirtovalmiit_suuntalavat)) $nayta_siirtovalmiit_suuntalavat = "";
  if (!isset($etsi_sscclla)) $etsi_sscclla = '';

  echo "<form name='toimi' method='post' autocomplete='off'>";
  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Etsi toimittaja")."</th>";
  echo "<td><input type='text' name='ytunnus' value='$ytunnus'></td>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>".t("Etsi saapumisnumerolla")."</th>";
  echo "<td><input type='text' name='keikka' value='$keikka'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Etsi ostotilausnumerolla")."</th>";
  echo "<td><input type='text' name='ostotil' value='$ostotil'></td>";

  if ($yhtiorow['varastopaikkojen_maarittely'] == 'M') {

    $query = "SELECT tunnus, nimitys FROM keraysvyohyke WHERE yhtio = '{$kukarow['yhtio']}' AND nimitys != ''";
    $keraysvyohyke_result = pupe_query($query);

    if (mysql_num_rows($keraysvyohyke_result) > 0) {
      echo "</tr><tr>";
      echo "<th>", t("Rajaa laatijaa ker‰ysvyˆhykkeell‰"), "</th>";
      echo "<td><select name='keraysvyohyke' onchange='submit();'>";
      echo "<option value=''>", t("Valitse"), "</option>";

      while ($keraysvyohyke_row = mysql_fetch_assoc($keraysvyohyke_result)) {

        $sel = $keraysvyohyke_row['tunnus'] == $keraysvyohyke ? ' selected' : '';

        echo "<option value='{$keraysvyohyke_row['tunnus']}'{$sel}>{$keraysvyohyke_row['nimitys']}</option>";
      }

      echo "</select></td>";
    }

    echo "</tr><tr>";
    echo "<th>", t("Etsi saapumisen laatijalla"), "</th>";

    $kukalisa = trim($keraysvyohyke) != '' ? " and keraysvyohyke = '{$keraysvyohyke}' " : '';

    $query = "SELECT kuka, nimi
              FROM kuka
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND extranet = ''
              $kukalisa
              ORDER BY nimi";
    $keikan_laatija_res = pupe_query($query);

    echo "<td><select name='keikan_laatija' onchange='submit();'>";
    echo "<option value=''>", t("Valitse"), "</option>";

    while ($keikan_laatija_row = mysql_fetch_assoc($keikan_laatija_res)) {

      $sel = $keikan_laatija_row['kuka'] == $keikan_laatija ? ' selected' : '';

      echo "<option value='{$keikan_laatija_row['kuka']}'{$sel}>{$keikan_laatija_row['nimi']} ({$keikan_laatija_row['kuka']})</option>";
    }

    echo "</select></td>";
  }

  if ($yhtiorow['suuntalavat'] == 'S') {

    echo "</tr><tr>";
    echo "<th>", t("Etsi SSCC-numerolla"), "</th>";

    echo "<td><input type='text' name='etsi_sscclla' value='{$etsi_sscclla}' /></td>";

    echo "</tr><tr>";
    echo "<th>", t("N‰yt‰ vain saapumiset, joilla on siirtovalmiita suuntalavoja"), "</th>";

    $chk = $nayta_siirtovalmiit_suuntalavat != '' ? ' checked' : '';

    echo "<td><input type='checkbox' name='nayta_siirtovalmiit_suuntalavat' {$chk} /></td>";
  }

  echo "</tr>";

  if ($onkolaajattoimipaikat) {

    $sel = (isset($toimipaikka) and is_numeric($toimipaikka) and $toimipaikka == 0) ? "selected" : "";

    echo "<tr>";
    echo "<th>", t("Toimipaikka"), "</th>";
    echo "<td>";
    echo "<select name='toimipaikka'>";
    echo "<option value='kaikki'>", t("Kaikki toimipaikat"), "</option>";
    echo "<option value='0' {$sel}>", t("Ei toimipaikkaa"), "</option>";

    while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {

      $sel = '';

      if (isset($toimipaikka)) {
        if ($toimipaikka == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }
      }
      else {
        if ($kukarow['toimipaikka'] == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }
      }

      echo "<option value='{$toimipaikat_row['tunnus']}'{$sel}>{$toimipaikat_row['nimi']}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }

  echo "<tr>";
  echo "<th>", t("Lis‰rajaus"), "</th>";

  if (!isset($lisarajaus)) $lisarajaus = "";

  if ($toim != 'AVOIMET') {
    $sel = array_fill_keys(array($lisarajaus), ' selected') + array_fill_keys(array('riveja_viematta_varastoon', 'liitetty_lasku', 'ei_liitetty_lasku', 'liitetty_lasku_rivitok_kohdistus_eiok', 'liitetty_lasku_rivitok_kohdistus_ok'), '');
  }
  else {
    $sel = array(
      'riveja_viematta_varastoon' => 'selected',
      'liitetty_lasku' => '',
      'ei_liitetty_lasku' => '',
      'liitetty_lasku_rivitok_kohdistus_eiok' => '',
      'liitetty_lasku_rivitok_kohdistus_ok' => '',
    );

    $lisarajaus = 'riveja_viematta_varastoon';
  }

  echo "<td><select name='lisarajaus' ", js_alasvetoMaxWidth('lisarajaus', 250), ">";
  echo "<option value=''>", t("N‰yt‰ kaikki"), "</option>";
  echo "<option value='riveja_viematta_varastoon'{$sel['riveja_viematta_varastoon']}>", t("Saapumiset joissa on rivej‰ viem‰tt‰ varastoon"), "</option>";
  echo "<option value='liitetty_lasku'{$sel['liitetty_lasku']}>", t("Saapumiset joihin on liitetty lasku"), "</option>";
  echo "<option value='ei_liitetty_lasku'{$sel['ei_liitetty_lasku']}>", t("Saapumiset joihin ei ole liitetty laskua"), "</option>";
  echo "<option value='liitetty_lasku_rivitok_kohdistus_eiok'{$sel['liitetty_lasku_rivitok_kohdistus_eiok']}>", t("Saapumiset joihin on liitetty lasku ja kaikki rivit on viety varastoon ja kohdistus ei ole ok"), "</option>";
  echo "<option value='liitetty_lasku_rivitok_kohdistus_ok'{$sel['liitetty_lasku_rivitok_kohdistus_ok']}>", t("Saapumiset joihin on liitetty lasku ja kaikki rivit on viety varastoon ja kohdistus on ok"), "</option>";
  echo "</select></td>";

  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td>";
  echo "</tr>";

  echo "</table>";
  echo "</form>";

  // kursorinohjausta
  $formi    = "toimi";
  $kentta   = "ytunnus";
  $toiminto = "";

  $kaikkivarastossayhteensa     = 0;
  $kaikkiliitettyyhteensa      = 0;
  $vaihtoomaisuuslaskujayhteensa   = 0;
  $kululaskujayhteensa       = 0;
  $vaihtoomaisuuslaskujayhteensa_kulut = 0;
  $kululaskujayhteensa_kulut = 0;
  $rahti_ja_kulut         = 0;
  $laatijalisa           = '';

  if (isset($keikan_laatija) and trim($keikan_laatija) != '') {
    $laatijalisa = " and lasku.laatija = '$keikan_laatija' ";
  }

  $suuntalavajoin = '';
  $left_join = "LEFT ";

  if ($yhtiorow['suuntalavat'] == 'S' and ($nayta_siirtovalmiit_suuntalavat != '' or $etsi_sscclla != '')) {
    $left_join = "";

    $suuntalava_lisa = $etsi_sscclla != '' ? " and suuntalavat.sscc = '{$etsi_sscclla}'" : "";

    $suuntalava_tila_lisa = $nayta_siirtovalmiit_suuntalavat != '' ? " AND suuntalavat.tila = 'S'" : "";

    $suuntalavajoin = " JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tunnus = tilausrivi.suuntalava {$suuntalava_tila_lisa} {$suuntalava_lisa})
              JOIN suuntalavat_saapuminen ON (suuntalavat_saapuminen.yhtio = suuntalavat.yhtio AND suuntalavat_saapuminen.suuntalava = suuntalavat.tunnus AND suuntalavat_saapuminen.saapuminen = lasku.tunnus) ";
  }

  $query_ale_lisa = generoi_alekentta("O");

  $joinlisa = "";

  if ($lisarajaus == 'liitetty_lasku' or $lisarajaus == 'ei_liitetty_lasku' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok') {
    $joinlisa = "JOIN lasku AS liitetty_lasku ON (liitetty_lasku.yhtio = lasku.yhtio AND liitetty_lasku.tila = 'K' AND liitetty_lasku.laskunro = lasku.laskunro AND liitetty_lasku.vanhatunnus <> 0 AND liitetty_lasku.vienti IN ('C','F','I','J','K','L'))";

    if ($lisarajaus == 'ei_liitetty_lasku') $joinlisa = "LEFT {$joinlisa}";
  }

  $kohdistuslisa = "";

  if ($lisarajaus == 'riveja_viematta_varastoon') {
    $tilriv_joinlisa = "AND tilausrivi.kpl >= 0 AND tilausrivi.varattu > 0";
    $left_join = "";
  }
  elseif ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok') {
    $tilriv_joinlisa = "AND tilausrivi.kpl > 0 AND tilausrivi.varattu = 0";
    $left_join = "";
    if ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok') $kohdistuslisa = " and lasku.kohdistettu = 'K'";
    else $kohdistuslisa = " and lasku.kohdistettu = ''";
  }
  else {
    $tilriv_joinlisa = "";
  }

  $ei_lasku_lisa = "";

  if ($lisarajaus == 'ei_liitetty_lasku') {
    $ei_lasku_lisa = "and liitetty_lasku.tunnus is null";
  }

  if ($onkolaajattoimipaikat and isset($toimipaikka) and $toimipaikka != 'kaikki') {
    $toimipaikkalisa = "AND lasku.yhtio_toimipaikka = '{$toimipaikka}'";
  }
  else {
    $toimipaikkalisa = "";
  }

  // n‰ytet‰‰n mill‰ toimittajilla on keskener‰isi‰ keikkoja
  $query = "SELECT lasku.liitostunnus,
            max(lasku.ytunnus)  ytunnus,
            max(lasku.nimi)     nimi,
            max(lasku.nimitark) nimitark,
            max(lasku.osoite)   osoite,
            max(lasku.postitp)  postitp,
            group_concat(distinct if(lasku.comments!='',CONCAT(lasku.laskunro, ': ', lasku.comments),NULL) SEPARATOR '<br><br>') comments,
            count(distinct lasku.tunnus) kpl,
            group_concat(distinct lasku.laskunro SEPARATOR ', ') keikat,
            round(sum(if(tilausrivi.kpl!=0, tilausrivi.rivihinta, 0)),2) varastossaarvo,
            ROUND(SUM(tilausrivi.kpl * tilausrivi.hinta * {$query_ale_lisa}), 2) varastoonvietyarvo,
            round(sum((tilausrivi.varattu+tilausrivi.kpl) * tilausrivi.hinta * {$query_ale_lisa}),2) kohdistettuarvo,
            SUM(tilausrivi.kpl) var_kpl,
            SUM(tilausrivi.varattu) var_varattu,
            GROUP_CONCAT(DISTINCT lasku.tunnus) tilauksien_tunnukset
            FROM lasku
            {$left_join}JOIN tilausrivi USE INDEX (uusiotunnus_index) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O' {$tilriv_joinlisa})
            {$joinlisa}
            {$suuntalavajoin}
            WHERE lasku.yhtio     = '$kukarow[yhtio]'
            and lasku.tila        = 'K'
            and lasku.alatila     = ''
            and lasku.vanhatunnus = 0
            and lasku.mapvm       = '0000-00-00'
            $laatijalisa
            {$kohdistuslisa}
            {$toimipaikkalisa}
            {$ei_lasku_lisa}
            GROUP BY lasku.liitostunnus
            ORDER BY nimi, nimitark, ytunnus";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<br><font class='head'>".t("Keskener‰iset saapumiset")."</font><hr>";

    echo "<table>";
    echo "<tr><th>".t("ytunnus")."</th><th>&nbsp;</th><th>".t("nimi")."</th><th>".t("osoite")."</th><th>".t("saapumisnumerot")."</th><th>".t("kpl")."</th><th>".t("varastonarvo")."</th><td class='back'></td></tr>";

    $toimipaikka = isset($toimipaikka) ? $toimipaikka : 'kaikki';

    while ($row = mysql_fetch_assoc($result)) {

      $query = "SELECT count(*) num,
                sum(if(vienti in ('C','F','I','J','K','L'), 1, 0)) volasku,
                sum(if(vienti not in ('C','F','I','J','K','L'), 1, 0)) kulasku,
                sum(if(vienti in ('C','F','I','J','K','L'), arvo * vienti_kurssi, 0)) vosumma,
                sum(if(vienti in ('C','F','I','J','K','L'), (osto_kulu + osto_rahti + osto_rivi_kulu), 0)) vosumma_kulut,
                sum(if(vienti not in ('C','F','I','J','K','L'), arvo * vienti_kurssi, 0)) kusumma,
                sum(if(vienti not in ('C','F','I','J','K','L'), (osto_kulu + osto_rahti + osto_rivi_kulu), 0)) kusumma_kulut
                FROM lasku use index (yhtio_tila_laskunro)
                WHERE yhtio     = '$kukarow[yhtio]'
                AND tila        = 'K'
                AND vanhatunnus > 0
                AND laskunro    IN ({$row['keikat']})";
      $laskuja_result = pupe_query($query);
      $laskuja_row = mysql_fetch_assoc($laskuja_result);

      $kaikkivarastossayhteensa     += $row["varastossaarvo"];
      $kaikkiliitettyyhteensa     += $row["kohdistettuarvo"];
      $rahti_ja_kulut         += ($row["varastoonvietyarvo"] - $row['varastossaarvo']);
      $vaihtoomaisuuslaskujayhteensa  += $laskuja_row["vosumma"];
      $kululaskujayhteensa       += $laskuja_row["kusumma"];
      $vaihtoomaisuuslaskujayhteensa_kulut += $laskuja_row['vosumma_kulut'];
      $kululaskujayhteensa_kulut += $laskuja_row['kusumma_kulut'];

      echo "<tr class='aktiivi'>";
      echo "<td valign='top'>$row[ytunnus]</td>";

      // tehd‰‰n pop-up divi jos keikalla on kommentti...
      if ($row["comments"] != "") {
        echo "<td valign='top' class='tooltip' id='$row[liitostunnus]'><img src='$palvelin2/pics/lullacons/info.png'>";
        echo "<div id='div_$row[liitostunnus]' class='popup' style='width: 500px;'>";
        echo $row["comments"];
        echo "</div>";
        echo "</td>";
      }
      else {
        echo "<td>&nbsp;</td>";
      }

      if ($row["varastossaarvo"] == 0) $row["varastossaarvo"] = "";

      echo "<td>$row[nimi] $row[nimitark]</td><td>$row[osoite] $row[postitp]</td><td>$row[keikat]</td><td align='right'>$row[kpl]</td><td align='right'>$row[varastossaarvo]</td>";
      echo "<td class='back'><form method='post'>";
      echo "<input type='hidden' name='toimittajaid' value='$row[liitostunnus]'>";
      echo "<input type='hidden' name='lisarajaus' value='{$lisarajaus}' />";
      echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";
      echo "<input type='hidden' name='lopetus' value='$PHP_SELF////toimipaikka=$toimipaikka//lisarajaus=$lisarajaus//indexvas=1' />";

      if ($keikkarajaus == '' and $row['keikat'] != '' and strpos($row['keikat'], ',') === FALSE) {
        echo "<input type='hidden' name='keikkarajaus' value='{$row['keikat']}' />";
      }

      echo "<input type='submit' value='".t("Valitse")."'>";
      echo "</form></td>";
      echo "</tr>";
    }

    echo "</table>";

    echo "<br><form name='toimi' method='post' autocomplete='off'>";
    echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
    echo "<input type='hidden' name='naytalaskelma' value='JOO'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";

    $naytalaskelma_pp = isset($naytalaskelma_pp) ? (int) $naytalaskelma_pp : date('d');
    $naytalaskelma_kk = isset($naytalaskelma_kk) ? (int) $naytalaskelma_kk : date('m');
    $naytalaskelma_vv = isset($naytalaskelma_vv) ? (int) $naytalaskelma_vv : date('Y');

    echo "<input type='text' name='naytalaskelma_pp' value='{$naytalaskelma_pp}' size='3' />";
    echo "<input type='text' name='naytalaskelma_kk' value='{$naytalaskelma_kk}' size='3' />";
    echo "<input type='text' name='naytalaskelma_vv' value='{$naytalaskelma_vv}' size='5' />";

    echo "<input type='submit' value='".t("N‰yt‰ varastonarvolaskelma")."'>";
    echo "</form>";

    if (isset($naytalaskelma) and $naytalaskelma != "" and checkdate($naytalaskelma_kk, $naytalaskelma_pp, $naytalaskelma_vv)) {
      list (  $liitetty_lasku_viety_summa,
        $liitetty_lasku_viety_summa_tuloutettu,
        $ei_liitetty_lasku_viety_summa,
        $ei_liitetty_lasku_viety_summa_tuloutettu,
        $liitetty_lasku_ei_viety_summa,
        $ei_liitetty_lasku_ei_viety_summa,
        $ei_liitetty_lasku_ei_viety_summa_tuloutettu,
        $liitetty_lasku_osittain_viety_summa,
        $liitetty_lasku_osittain_viety_summa_tuloutettu,
        $ei_liitetty_lasku_osittain_viety_summa,
        $ei_liitetty_lasku_osittain_viety_summa_tuloutettu,
        $laskut_ei_viety,
        $laskut_ei_viety_osittain,
        $laskut_viety,
        $laskut_osittain_viety,
        $row_vaihto,
        $liitetty_lasku_osittain_ei_viety_summa,
        $liitetty_lasku_osittain_ei_viety_summa_tuloutettu,
        $ei_liitetty_lasku_osittain_ei_viety_summa,
        $ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu,
        $liitetty_lasku_ei_viety_summa_tuloutettu
      ) = hae_yhteenveto_tiedot($toimittajaid, $toimipaikka, $naytalaskelma_pp, $naytalaskelma_kk, $naytalaskelma_vv);

      $params = array(
        'kaikkivarastossayhteensa'         => $kaikkivarastossayhteensa,
        'kaikkiliitettyyhteensa'         => $kaikkiliitettyyhteensa,
        'vaihtoomaisuuslaskujayhteensa'       => $vaihtoomaisuuslaskujayhteensa,
        'row_vaihto'               => $row_vaihto,
        'kululaskujayhteensa'           => $kululaskujayhteensa,
        'liitetty_lasku_ei_viety_summa'       => $liitetty_lasku_ei_viety_summa,
        'ei_liitetty_lasku_ei_viety_summa'     => $ei_liitetty_lasku_ei_viety_summa,
        'ei_liitetty_lasku_ei_viety_summa_tuloutettu' => $ei_liitetty_lasku_ei_viety_summa_tuloutettu,
        'laskut_ei_viety'             => $laskut_ei_viety,
        'laskut_ei_viety_osittain'         => $laskut_ei_viety_osittain,
        'liitetty_lasku_viety_summa'       => $liitetty_lasku_viety_summa,
        'liitetty_lasku_viety_summa_tuloutettu'  => $liitetty_lasku_viety_summa_tuloutettu,
        'ei_liitetty_lasku_viety_summa'       => $ei_liitetty_lasku_viety_summa,
        'ei_liitetty_lasku_viety_summa_tuloutettu' => $ei_liitetty_lasku_viety_summa_tuloutettu,
        'laskut_viety'               => $laskut_viety,
        'liitetty_lasku_osittain_viety_summa'   => $liitetty_lasku_osittain_viety_summa,
        'liitetty_lasku_osittain_viety_summa_tuloutettu' => $liitetty_lasku_osittain_viety_summa_tuloutettu,
        'ei_liitetty_lasku_osittain_viety_summa' => $ei_liitetty_lasku_osittain_viety_summa,
        'ei_liitetty_lasku_osittain_viety_summa_tuloutettu' => $ei_liitetty_lasku_osittain_viety_summa_tuloutettu,
        'laskut_osittain_viety'           => $laskut_osittain_viety,
        'rahti_ja_kulut'             => $rahti_ja_kulut,
        'vaihtoomaisuuslaskujayhteensa_kulut'    => $vaihtoomaisuuslaskujayhteensa_kulut,
        'kululaskujayhteensa_kulut'        => $kululaskujayhteensa_kulut,
        'liitetty_lasku_osittain_ei_viety_summa' => $liitetty_lasku_osittain_ei_viety_summa,
        'liitetty_lasku_osittain_ei_viety_summa_tuloutettu' => $liitetty_lasku_osittain_ei_viety_summa_tuloutettu,
        'ei_liitetty_lasku_osittain_ei_viety_summa' => $ei_liitetty_lasku_osittain_ei_viety_summa,
        'ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu' => $ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu,
        'liitetty_lasku_ei_viety_summa_tuloutettu' => $liitetty_lasku_ei_viety_summa_tuloutettu,
      );

      echo_yhteenveto_table($params);
    }
  }
}

// perustetaan uusi keikka toimittajalle $ytunnus
if ($toiminto == "uusi" and $toimittajaid > 0) {

  $toimipaikka = isset($toimipaikka) ? $toimipaikka : 0;

  // Toiminta funktioitu
  $result = uusi_saapuminen($toimittajarow, $toimipaikka);

  // selaukseen
  $toiminto = "";
}

// selataan toimittajan keikkoja
if ($toiminto == "" and (($ytunnus != "" or $keikkarajaus != '') and $toimittajarow["ytunnus"] != '')) {

  // n‰ytet‰‰n v‰h‰ toimittajan tietoja
  echo "<table>";
  echo "<tr>";
  echo "<th colspan='5'>".t("Toimittaja")."</th>";
  echo "</tr><tr>";
  echo "<td>$toimittajarow[ytunnus]</td>";
  echo "<td>$toimittajarow[nimi]</td>";
  echo "<td>$toimittajarow[osoite]</td>";
  echo "<td>$toimittajarow[postino]</td>";
  echo "<td>$toimittajarow[postitp]</td>";

  $toimipaikka = isset($toimipaikka) ? $toimipaikka : 0;

  echo "<td class='back' style='vertical-align:bottom;'>";
  echo "<form method='post'>";
  echo "<input type='hidden' name='toiminto' value='uusi'>";
  echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
  echo "<input type='hidden' name='ytunnus' value='{$ytunnus}'>";
  echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
  echo "<input type='submit' value='".t("Perusta uusi saapuminen")."'>";
  echo "</form>";
  echo "</td>";
  echo "</tr>";

  if (trim($toimittajarow["fakta"]) != "") {
    echo "<tr><td colspan='5'>".wordwrap($toimittajarow["fakta"], 100, "<br>")."</td></tr>";
  }

  echo "<form method='post'>";

  if ($onkolaajattoimipaikat) {

    $sel = (isset($toimipaikka) and is_numeric($toimipaikka) and $toimipaikka == 0) ? "selected" : "";

    echo "<tr>";
    echo "<th>", t("Toimipaikka"), "</th>";
    echo "<td colspan='4'>";
    echo "<select name='toimipaikka' onchange='submit();'>";
    echo "<option value='kaikki'>", t("Kaikki toimipaikat"), "</option>";
    echo "<option value='0' {$sel}>", t("Ei toimipaikkaa"), "</option>";

    while ($toimipaikat_row = mysql_fetch_assoc($toimipaikat_res)) {

      $sel = '';

      if (isset($toimipaikka)) {
        if ($toimipaikka == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }
      }
      else {
        if ($kukarow['toimipaikka'] == $toimipaikat_row['tunnus']) {
          $sel = ' selected';
          $toimipaikka = $toimipaikat_row['tunnus'];
        }
      }

      echo "<option value='{$toimipaikat_row['tunnus']}'{$sel}>{$toimipaikat_row['nimi']}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }

  if (!isset($lisarajaus)) $lisarajaus = "";

  $sel = array_fill_keys(array($lisarajaus), ' selected') + array_fill_keys(array('riveja_viematta_varastoon', 'liitetty_lasku', 'ei_liitetty_lasku', 'liitetty_lasku_rivitok_kohdistus_eiok', 'liitetty_lasku_rivitok_kohdistus_ok'), '');

  echo "<tr>";
  echo "<th>", t("Lis‰rajaus"), "</th>";
  echo "<td colspan='4'>";
  echo "<input type='hidden' name='toiminto' value=''>";
  echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
  echo "<select name='lisarajaus' ", js_alasvetoMaxWidth('lisarajaus', 250), " onchange='submit();'>";
  echo "<option value=''>", t("N‰yt‰ kaikki"), "</option>";
  echo "<option value='riveja_viematta_varastoon'{$sel['riveja_viematta_varastoon']}>", t("Saapumiset joissa on rivej‰ viem‰tt‰ varastoon"), "</option>";
  echo "<option value='liitetty_lasku'{$sel['liitetty_lasku']}>", t("Saapumiset joihin on liitetty lasku"), "</option>";
  echo "<option value='ei_liitetty_lasku'{$sel['ei_liitetty_lasku']}>", t("Saapumiset joihin ei ole liitetty laskua"), "</option>";
  echo "<option value='liitetty_lasku_rivitok_kohdistus_eiok'{$sel['liitetty_lasku_rivitok_kohdistus_eiok']}>", t("Saapumiset joihin on liitetty lasku ja kaikki rivit on viety varastoon ja kohdistus ei ole ok"), "</option>";
  echo "<option value='liitetty_lasku_rivitok_kohdistus_ok'{$sel['liitetty_lasku_rivitok_kohdistus_ok']}>", t("Saapumiset joihin on liitetty lasku ja kaikki rivit on viety varastoon ja kohdistus on ok"), "</option>";
  echo "</select></form></td></tr>";

  echo "</table><br />";

  $joinlisa = $havinglisa = $selectlisa = $groupbylisa = "";

  if (in_array($lisarajaus, array('riveja_viematta_varastoon', 'liitetty_lasku_rivitok_kohdistus_eiok', 'liitetty_lasku_rivitok_kohdistus_ok', 'liitetty_lasku', 'ei_liitetty_lasku'))) {

    if ($lisarajaus == 'liitetty_lasku' or $lisarajaus == 'ei_liitetty_lasku') {

      $joinlisa = "JOIN lasku AS liitetty_lasku ON (liitetty_lasku.yhtio = lasku.yhtio AND liitetty_lasku.tila = 'K' AND liitetty_lasku.laskunro = lasku.laskunro AND liitetty_lasku.vanhatunnus <> 0 AND liitetty_lasku.vienti IN ('C','F','I','J','K','L'))";
      $groupbylisa = "GROUP BY 1,2,3,4,5,6";

      if ($lisarajaus == 'ei_liitetty_lasku') {
        $joinlisa = "LEFT {$joinlisa}";
        $selectlisa = ", liitetty_lasku.tunnus liitetty_lasku_tunnus";
        $havinglisa = "HAVING liitetty_lasku_tunnus IS NULL";
      }
    }
    else {

      $joinlisa = "JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.uusiotunnus = lasku.tunnus AND tilausrivi.tyyppi = 'O')";
      $groupbylisa = "GROUP BY 1,2,3,4,5,6,7,8";
      $selectlisa = ", SUM(tilausrivi.kpl) kpl, SUM(tilausrivi.varattu) varattu";

      if ($lisarajaus == 'riveja_viematta_varastoon') {
        $havinglisa = "HAVING kpl >= 0 AND varattu > 0";
      }
      else {
        $havinglisa = "HAVING kpl > 0 AND varattu = 0";
      }
    }
  }

  if ($onkolaajattoimipaikat and isset($toimipaikka) and $toimipaikka != 'kaikki') {
    $toimipaikkalisa = "AND lasku.yhtio_toimipaikka = '{$toimipaikka}'";
  }
  else {
    $toimipaikkalisa = "";
  }

  if ($onkolaajattoimipaikat and isset($toimipaikka) and $toimipaikka == 'kaikki') {
    $joinlisa .= " LEFT JOIN yhtion_toimipaikat ON (yhtion_toimipaikat.yhtio = lasku.yhtio AND yhtion_toimipaikat.tunnus = lasku.yhtio_toimipaikka)";
    $selectlisa .= ", IF(yhtion_toimipaikat.tunnus IS NULL, '".t('Ei toimipaikkaa')."', yhtion_toimipaikat.nimi) as toimipaikka_nimi";
  }
  // etsit‰‰n vanhoja keikkoja, vanhatunnus pit‰‰ olla tyhj‰‰ niin ei listata liitettyj‰ laskuja
  $query = "SELECT lasku.tunnus,
            lasku.laskunro,
            lasku.comments,
            lasku.nimi,
            lasku.ytunnus,
            lasku.luontiaika,
            lasku.laatija,
            lasku.rahti_etu,
            lasku.kohdistettu,
            lasku.sisviesti3,
            lasku.yhtio_toimipaikka
            {$selectlisa}
            FROM lasku
            {$joinlisa}
            where lasku.yhtio      = '$kukarow[yhtio]'
            and lasku.liitostunnus = '$toimittajaid'
            and lasku.tila         = 'K'
            and lasku.alatila      = ''
            and lasku.vanhatunnus  = 0
            and lasku.mapvm        = '0000-00-00'
            {$toimipaikkalisa}
            {$groupbylisa}
            {$havinglisa}
            ORDER BY lasku.laskunro DESC";
  $result = pupe_query($query);

  echo "<font class='head'>".t("Toimittajan keskener‰iset saapumiset")."</font><hr>";

  if (mysql_num_rows($result) > 0) {

    $maara = 9;

    if ($onkolaajattoimipaikat and isset($toimipaikka) and $toimipaikka == 'kaikki') {
      $maara = 10;
    }

    if ($onkologmaster) {
      $maara++;
    }

    pupe_DataTables(array(array($pupe_DataTables, $maara, $maara, false)));

    echo "<table class='display dataTable' id='{$pupe_DataTables}'>";
    echo "<thead>";
    echo "<tr>";
    if ($onkolaajattoimipaikat and isset($toimipaikka) and $toimipaikka == 'kaikki') {
      echo "<th valign='top'>".t("toimipaikka")."</th>";
    }
    if ($onkologmaster) {
      echo "<th valign='top'>".t("Ulkoinen j‰rjestelm‰")."</th>";
    }
    echo "<th valign='top'>".t("saapuminen")."</th>";
    echo "<th valign='top'>&nbsp;</th>";
    echo "<th valign='top'>".t("laadittu")." /<br>".t("viety varastoon")."</th>";
    echo "<th valign='top'>".t("kohdistus")." /<br>".t("lis‰tiedot")."</th>";
    echo "<th valign='top'>".t("paikat")." /<br>".t("sarjanrot")."</th>";
    echo "<th valign='top'>".t("kohdistettu")." /<br>".t("varastossa")."</th>";
    echo "<th valign='top'>".t("tilaukset")."</th>";
    echo "<th valign='top'>".t("ostolaskuja")." /<br>".t("kululaskuja")."</th>";
    echo "<th valign='top'>".t("toiminto")."</th>";
    echo "</tr>";

    echo "<tr>";
    if ($onkolaajattoimipaikat and isset($toimipaikka) and $toimipaikka == 'kaikki') {
      echo "<td><input type='text'   class='search_field' name='search_toimipaikka'></td>";
    }
    if ($onkologmaster) {
      echo "<td><input type='hidden' class='search_field' name='search_eimitaan'></td>";
    }
    echo "<td><input type='text'   class='search_field' name='search_saapuminen'></td>";
    echo "<td><input type='hidden' class='search_field' name='search_eimitaan'></td>";
    echo "<td><input type='text'   class='search_field' name='search_pvm'></td>";
    echo "<td><input type='text'   class='search_field' name='search_kohdistus'></td>";
    echo "<td><input type='text'   class='search_field' name='search_paikat'></td>";
    echo "<td><input type='text'   class='search_field' name='search_kohdistettu'></td>";
    echo "<td><input type='text'   class='search_field' name='search_tilaukset'></td>";
    echo "<td><input type='text'   class='search_field' name='search_laskuja'></td>";
    echo "<td><input type='hidden' class='search_field' name='search_eimitaan'></td>";
    echo "</tr>";

    echo "</thead>";
    echo "<tbody>";

    $keikkakesken = 0;

    $lock_params = array(
      "locktime" => 0,
      "lockfile" => "$kukarow[yhtio]-keikka.lock",
      "return"   => TRUE
    );

    if (!pupesoft_flock($lock_params)) {
      list($keikkakesken, $_kuka, $_timestamp) = explode(";", file_get_contents("/tmp/$kukarow[yhtio]-keikka.lock"));
    }

    $kaikkivarastossayhteensa     = 0;
    $kaikkiliitettyyhteensa      = 0;
    $vaihtoomaisuuslaskujayhteensa   = 0;
    $kululaskujayhteensa       = 0;
    $vaihtoomaisuuslaskujayhteensa_kulut = 0;
    $kululaskujayhteensa_kulut = 0;
    $rahti_ja_kulut = 0;

    while ($row = mysql_fetch_assoc($result)) {

      list($kaikkivarastossayhteensa, $kaikkiliitettyyhteensa, $kohdistus, $kohok, $kplvarasto, $kplyhteensa, $lisatiedot, $lisok, $llrow, $sarjanrook, $sarjanrot, $uusiot, $varastopaikat, $varastossaarvo, $liitettyarvo, $varok, $rahti_ja_kulut) = tsekit($row, $kaikkivarastossayhteensa, $kaikkiliitettyyhteensa);
      $vaihtoomaisuuslaskujayhteensa += $llrow["vosumma"];
      $kululaskujayhteensa += $llrow["kusumma"];
      $vaihtoomaisuuslaskujayhteensa_kulut += $llrow['vosumma_kulut'];
      $kululaskujayhteensa_kulut += $llrow['kusumma_kulut'];

      if ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' or $lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok') {
        if ($llrow['num'] == 0 or ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_eiok' and $kohok == 1) or ($lisarajaus == 'liitetty_lasku_rivitok_kohdistus_ok' and $kohok == 0)) continue;
      }

      echo "<tr class='aktiivi'>";

      if ($onkolaajattoimipaikat and isset($toimipaikka) and $toimipaikka == 'kaikki') {
        echo "<td valign='top'>$row[toimipaikka_nimi]</td>";
      }
      if ($onkologmaster) {
        echo "<td valign='top'>";

        switch ($row['sisviesti3']) {
        case 'lahetetty_ulkoiseen_jarjestelmaan':
          echo "<font class='error'>";
          echo t("Odottaa kuittausta");
          echo "</font>";
          break;
        case 'kuittaus_saapunut_ulkoisesta_jarjestelmasta':
          echo "<font class='ok'>";
          echo t("Kuittaus saapunut");
          echo "</font>";
          break;
        }

        echo "</td>";
      }
      echo "<td valign='top'>$row[laskunro]</td>";

      // tehd‰‰n pop-up divi jos keikalla on kommentti...
      if ($row["comments"] != "") {

        $query = "SELECT nimi
                  FROM kuka
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND kuka    = '{$row['laatija']}'";
        $kuka_chk_res = pupe_query($query);
        $kuka_chk_row = mysql_fetch_assoc($kuka_chk_res);

        echo "<td valign='top' class='tooltip' id='$row[laskunro]'>";
        echo "<div id='div_$row[laskunro]' class='popup' style='width:500px;'>";
        echo t("Saapuminen").": $row[laskunro] / $row[nimi]<br><br>";
        echo t("Laatija"), ": {$kuka_chk_row['nimi']}<br />";
        echo t("Luontiaika"), ": ", tv1dateconv($row['luontiaika'], "pitk‰"), "<br /><br />";
        echo $row["comments"];
        echo "</div>";
        echo "<img src='$palvelin2/pics/lullacons/info.png'></td>";
      }
      else {
        echo "<td>&nbsp;</td>";
      }

      $query = "SELECT min(laskutettuaika) laskutettuaika
                FROM tilausrivi
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND uusiotunnus = {$row['tunnus']}
                AND tyyppi      = 'O'";
      $result2 = pupe_query($query);
      $tilausrivirow = mysql_fetch_assoc($result2);

      echo "<td valign='top'>".pupe_DataTablesEchoSort($row['luontiaika']).tv1dateconv($row['luontiaika']), "<br>", tv1dateconv($tilausrivirow['laskutettuaika']), "</td>";
      echo "<td valign='top'>$kohdistus<br>$lisatiedot</td>";
      echo "<td valign='top'>$varastopaikat<br>$sarjanrot</td>";
      echo "<td valign='top'>".pupe_DataTablesEchoSort($kplyhteensa)."$kplyhteensa<br>$kplvarasto $varastossaarvo</td>";

      if (count($uusiot) > 0 and count($uusiot) < 4) {
        echo "<td valign='top'>";
        echo implode("<br>", $uusiot);
        echo "</td>";
      }
      elseif (count($uusiot) > 0) {
        echo "<td valign='top' class='tooltip' id='keikka_$row[laskunro]'>";
        echo "<div id='div_keikka_$row[laskunro]' class='popup' style='width:100px;'>";
        echo t("Tilaukset").":<br><br>";
        echo implode("<br>", $uusiot);
        echo "</div>";
        echo "<img src='$palvelin2/pics/lullacons/info.png'></td>";
      }
      else {
        echo "<td>&nbsp;</td>";
      }

      $laskujen_tiedot = "";

      if ($llrow["volasku"] != $llrow["volasku_ok"] or $llrow["kulasku"] != $llrow["kulasku_ok"]) {
        $query = "SELECT ostores_lasku.*, kuka.nimi kukanimi
                  FROM lasku use index (yhtio_tila_laskunro)
                  JOIN lasku ostores_lasku use index (PRIMARY) ON (ostores_lasku.yhtio = lasku.yhtio and ostores_lasku.tunnus = lasku.vanhatunnus and ostores_lasku.hyvaksyja_nyt != '')
                  LEFT JOIN kuka ON (kuka.yhtio = ostores_lasku.yhtio and kuka.kuka = ostores_lasku.hyvaksyja_nyt)
                  WHERE lasku.yhtio     = '$kukarow[yhtio]'
                  AND lasku.tila        = 'K'
                  AND lasku.vanhatunnus > 0
                  AND lasku.laskunro    = '$row[laskunro]'";
        $volasresult = pupe_query($query);

        $laskujen_tiedot .= "<div id='div_lasku_$row[laskunro]' class='popup'>";
        while ($volasrow = mysql_fetch_assoc($volasresult)) {
          $laskujen_tiedot .= t("Lasku")." $volasrow[nimi] ($volasrow[summa] $volasrow[valkoodi]) ".t("hyv‰ksytt‰v‰n‰ k‰ytt‰j‰ll‰")." $volasrow[kukanimi]<br>";
        }
        $laskujen_tiedot .= "</div>";
      }

      if ($llrow["volasku"] > 0) {
        if ($llrow["volasku"] != $llrow["volasku_ok"]) {
          $laskujen_tiedot .= "$llrow[volasku] ($llrow[vosumma]) <font class='error'>*</font> <img class='tooltip' id='lasku_$row[laskunro]' src='$palvelin2/pics/lullacons/alert.png'>";
        }
        else {
          $laskujen_tiedot .= "$llrow[volasku] ($llrow[vosumma]) <font class='ok'>*</font>";
        }
      }

      $laskujen_tiedot .= "<br>";

      if ($llrow["kulasku"] > 0) {
        if ($llrow["kulasku"] != $llrow["kulasku_ok"]) {
          $laskujen_tiedot .= "$llrow[kulasku] ($llrow[kusumma]) <font class='error'>*</font> <img class='tooltip' id='lasku_$row[laskunro]' src='$palvelin2/pics/lullacons/alert.png'>";
        }
        else {
          $laskujen_tiedot .= "$llrow[kulasku] ($llrow[kusumma]) <font class='ok'>*</font>";
        }
      }

      echo "<td valign='top'>$laskujen_tiedot</td>";

      // jos t‰t‰ keikkaa ollaan just viem‰ss‰ varastoon ei tehd‰ dropdownia
      if ($keikkakesken == $row["tunnus"]) {
        echo "<td>".t("Varastoonvienti kesken")." ".t("k‰ytt‰j‰ll‰")." {$_kuka} @ {$_timestamp}</td>";
      }
      else {

        echo "<td align='right'>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='toimittajaid'   value='$toimittajaid'>";
        echo "<input type='hidden' name='toimipaikka'   value='$row[yhtio_toimipaikka]'>";
        echo "<input type='hidden' name='otunnus'     value='$row[tunnus]'>";
        echo "<input type='hidden' name='ytunnus'     value='$ytunnus'>";
        echo "<input type='hidden' name='keikkaid'     value='$row[laskunro]'>";
        echo "<input type='hidden' name='tunnus'     value='$row[tunnus]'>";
        echo "<input type='hidden' name='laskunro'     value='$row[laskunro]'>";
        echo "<input type='hidden' name='indexvas'     value='1'>";
        echo "<input type='hidden' name='lisarajaus'  value='{$lisarajaus}' />";
        echo "<select name='toiminto'>";

        // n‰it‰ saa tehd‰ aina keikalle
        echo "<option value='kohdista'>"         .t("Kohdista rivej‰")."</option>";
        echo "<option value='kululaskut'>"       .t("Saapumisen laskut")."</option>";
        echo "<option value='lisatiedot'>"       .t("Lis‰tiedot")."</option>";
        echo "<option value='yhdista'>"          .t("Yhdist‰ saapumisia")."</option>";

        // poista keikka vaan jos ei ole yht‰‰n rivi‰ kohdistettu ja ei ole yht‰‰n kululaskua liitetty
        if ($kplyhteensa == 0 and $llrow["num"] == 0) {
          echo "<option value='poista'>"       .t("Poista saapuminen")."</option>";
        }

        if ($yhtiorow['suuntalavat'] == 'S') {
          echo "<option value='suuntalavat'>", t("Suuntalavat"), "</option>";
        }

        // jos on kohdistettuja rivej‰ niin saa tehd‰ n‰it‰
        if ($kplyhteensa > 0) {
          echo "<option value='varastopaikat'>".t("Varastopaikat")."</option>";
          echo "<option value='tulosta'>"      .t("Tulosta paperit")."</option>";
        }

        // jos on kohdistettuja rivej‰ ja lis‰tiedot on syˆtetty ja varastopaikat on ok ja on viel‰ jotain viet‰v‰‰ varastoon
        if ($kplyhteensa > 0 and $varok == 1 and $kplyhteensa != $kplvarasto and $sarjanrook == 1 and $yhtiorow['suuntalavat'] != 'S') {
          echo "<option value='kalkyyli'>"     .t("Vie varastoon")."</option>";
        }

        // jos lis‰tiedot, kohdistus ja paikat on ok sek‰ kaikki rivit on viety varastoon, ja kaikki liitetyt laskut on hyv‰ksytty (kukarow.taso 3 voi ohittaa t‰m‰n), niin saadaan laskea virallinen varastonarvo
        if ($lisok == 1 and $kohok == 1 and $varok == 1 and $kplyhteensa == $kplvarasto and $sarjanrook == 1 and (($llrow["volasku"] == $llrow["volasku_ok"] and $llrow["kulasku"] == $llrow["kulasku_ok"]) or $kukarow["taso"] == "3")) {
          echo "<option value='kaikkiok'>"     .t("Laske virallinen varastonarvo")."</option>";
        }

        echo "</select>";
        echo "<input type='submit' value='".t("Tee")."'>";
        echo "</form>";
        echo "</td>";

      }
      echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";

    $toimipaikka = isset($toimipaikka) ? $toimipaikka : 0;

    echo "<br><br><form method='post'>";

    $naytalaskelma_pp = isset($naytalaskelma_pp) ? (int) $naytalaskelma_pp : date('d');
    $naytalaskelma_kk = isset($naytalaskelma_kk) ? (int) $naytalaskelma_kk : date('m');
    $naytalaskelma_vv = isset($naytalaskelma_vv) ? (int) $naytalaskelma_vv : date('Y');

    echo "<input type='text' name='naytalaskelma_pp' value='{$naytalaskelma_pp}' size='3' />";
    echo "<input type='text' name='naytalaskelma_kk' value='{$naytalaskelma_kk}' size='3' />";
    echo "<input type='text' name='naytalaskelma_vv' value='{$naytalaskelma_vv}' size='5' />";

    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
    echo "<input type='hidden' name='naytalaskelma' value='JOO'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
    echo "<input type='submit' value='".t("N‰yt‰ varastonarvolaskelma")."'>";
    echo "</form>";

    if (isset($naytalaskelma) and $naytalaskelma != "" and checkdate($naytalaskelma_kk, $naytalaskelma_pp, $naytalaskelma_vv)) {
      list (  $liitetty_lasku_viety_summa,
        $liitetty_lasku_viety_summa_tuloutettu,
        $ei_liitetty_lasku_viety_summa,
        $ei_liitetty_lasku_viety_summa_tuloutettu,
        $liitetty_lasku_ei_viety_summa,
        $ei_liitetty_lasku_ei_viety_summa,
        $ei_liitetty_lasku_ei_viety_summa_tuloutettu,
        $liitetty_lasku_osittain_viety_summa,
        $liitetty_lasku_osittain_viety_summa_tuloutettu,
        $ei_liitetty_lasku_osittain_viety_summa,
        $ei_liitetty_lasku_osittain_viety_summa_tuloutettu,
        $laskut_ei_viety,
        $laskut_ei_viety_osittain,
        $laskut_viety,
        $laskut_osittain_viety,
        $row_vaihto,
        $liitetty_lasku_osittain_ei_viety_summa,
        $liitetty_lasku_osittain_ei_viety_summa_tuloutettu,
        $ei_liitetty_lasku_osittain_ei_viety_summa,
        $ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu,
        $liitetty_lasku_ei_viety_summa_tuloutettu
      ) = hae_yhteenveto_tiedot($toimittajaid, $toimipaikka, $naytalaskelma_pp, $naytalaskelma_kk, $naytalaskelma_vv);

      $params = array(
        'kaikkivarastossayhteensa'         => $kaikkivarastossayhteensa,
        'kaikkiliitettyyhteensa'         => $kaikkiliitettyyhteensa,
        'vaihtoomaisuuslaskujayhteensa'       => $vaihtoomaisuuslaskujayhteensa,
        'row_vaihto'               => $row_vaihto,
        'kululaskujayhteensa'           => $kululaskujayhteensa,
        'liitetty_lasku_ei_viety_summa'       => $liitetty_lasku_ei_viety_summa,
        'ei_liitetty_lasku_ei_viety_summa'     => $ei_liitetty_lasku_ei_viety_summa,
        'ei_liitetty_lasku_ei_viety_summa_tuloutettu' => $ei_liitetty_lasku_ei_viety_summa_tuloutettu,
        'laskut_ei_viety'             => $laskut_ei_viety,
        'laskut_ei_viety_osittain'         => $laskut_ei_viety_osittain,
        'liitetty_lasku_viety_summa'       => $liitetty_lasku_viety_summa,
        'liitetty_lasku_viety_summa_tuloutettu'  => $liitetty_lasku_viety_summa_tuloutettu,
        'ei_liitetty_lasku_viety_summa'       => $ei_liitetty_lasku_viety_summa,
        'ei_liitetty_lasku_viety_summa_tuloutettu' => $ei_liitetty_lasku_viety_summa_tuloutettu,
        'laskut_viety'               => $laskut_viety,
        'liitetty_lasku_osittain_viety_summa'   => $liitetty_lasku_osittain_viety_summa,
        'liitetty_lasku_osittain_viety_summa_tuloutettu' => $liitetty_lasku_osittain_viety_summa_tuloutettu,
        'ei_liitetty_lasku_osittain_viety_summa' => $ei_liitetty_lasku_osittain_viety_summa,
        'ei_liitetty_lasku_osittain_viety_summa_tuloutettu' => $ei_liitetty_lasku_osittain_viety_summa_tuloutettu,
        'laskut_osittain_viety'           => $laskut_osittain_viety,
        'rahti_ja_kulut'             => $rahti_ja_kulut,
        'vaihtoomaisuuslaskujayhteensa_kulut'   => $vaihtoomaisuuslaskujayhteensa_kulut,
        'kululaskujayhteensa_kulut'         => $kululaskujayhteensa_kulut,
        'liitetty_lasku_osittain_ei_viety_summa' => $liitetty_lasku_osittain_ei_viety_summa,
        'liitetty_lasku_osittain_ei_viety_summa_tuloutettu' => $liitetty_lasku_osittain_ei_viety_summa_tuloutettu,
        'ei_liitetty_lasku_osittain_ei_viety_summa' => $ei_liitetty_lasku_osittain_ei_viety_summa,
        'ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu' => $ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu,
        'liitetty_lasku_ei_viety_summa_tuloutettu' => $liitetty_lasku_ei_viety_summa_tuloutettu
      );
      echo_yhteenveto_table($params);
    }

    // Rajaukset
    if ($keikkarajaus != "") {
      $datatables_rajaus = str_replace(",", "|", $keikkarajaus);

      echo "<script language='javascript' type='text/javascript'>
          $(document).ready(function() {
            $('input[name=\"search_saapuminen\"]').val(\"$datatables_rajaus\");
            $('input[name=\"search_saapuminen\"]').keyup();
          });
          </script>";
    }
  }
  else {
    echo "<br>".t("Toimittajalla ei ole keskener‰isi‰ saapumisia")."!";
  }
}

$nappikeikka = "";

// kohdisteaan keikkaa laskun tunnuksella $otunnus
if ($toiminto == "kohdista" or $toiminto == "yhdista" or $toiminto == "poista" or $toiminto == "tulosta" or $toiminto == "lisatiedot" or
  $toiminto == "varastopaikat" or $toiminto == "kululaskut" or $toiminto == "kalkyyli" or $toiminto == "kaikkiok" or $toiminto == "suuntalavat") {

  $query = "SELECT *
            FROM lasku
            where lasku.yhtio      = '$kukarow[yhtio]'
            and lasku.liitostunnus = '$toimittajaid'
            and lasku.tunnus       = '$otunnus'";
  $tsekkiresult = pupe_query($query);
  $tsekkirow = mysql_fetch_assoc($tsekkiresult);

  if (!isset($kaikkivarastossayhteensa)) $kaikkivarastossayhteensa = 0;
  if (!isset($kaikkiliitettyyhteensa))   $kaikkiliitettyyhteensa   = 0;

  list ($kaikkivarastossayhteensa, $kaikkiliitettyyhteensa, $kohdistus, $kohok, $kplvarasto, $kplyhteensa, $lisatiedot, $lisok, $llrow, $sarjanrook, $sarjanrot, $uusiot, $varastopaikat, $varastossaarvo, $liitettyarvo, $varok) = tsekit($tsekkirow, $kaikkivarastossayhteensa, $kaikkiliitettyyhteensa);

  $formalku =  "<td class='back'>";
  $formalku .= "<form action = '?indexvas=1' method='post'>";
  $formalku .= "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  $formalku .= "<input type='hidden' name='otunnus' value='$tsekkirow[tunnus]'>";
  $formalku .= "<input type='hidden' name='ytunnus' value='$ytunnus'>";
  $formalku .= "<input type='hidden' name='keikkaid' value='$tsekkirow[laskunro]'>";
  $formalku .= "<input type='hidden' name='tunnus' value='$tsekkirow[tunnus]'>";
  $formalku .= "<input type='hidden' name='laskunro' value='$tsekkirow[laskunro]'>";
  $formalku .= "<input type='hidden' name='nappikeikalle' value='menossa'>";
  $formalku .= "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";

  $formloppu = "</form></td>";

  // n‰it‰ saa tehd‰ aina keikalle
  $nappikeikka = "<table><tr>";

  $nappikeikka .= "$formalku";
  $nappikeikka .= "<input type='hidden' name='toiminto' value='kohdista'>";
  $nappikeikka .= "<input type='submit' value='".t("Kohdista rivej‰")."'>";
  $nappikeikka .= "$formloppu";

  $nappikeikka .= "$formalku";
  $nappikeikka .= "<input type='hidden' name='toiminto' value='kululaskut'>";
  $nappikeikka .= "<input type='submit' value='".t("Saapumisen laskut")."'>";
  $nappikeikka .= "$formloppu";

  $nappikeikka .= "$formalku";
  $nappikeikka .= "<input type='hidden' name='toiminto' value='lisatiedot'>";
  $nappikeikka .= "<input type='submit' value='".t("Lis‰tiedot")."'>";
  $nappikeikka .= "$formloppu";

  // poista keikka vaan jos ei ole yht‰‰n rivi‰ kohdistettu ja ei ole yht‰‰n kululaskua liitetty
  if ($kplyhteensa == 0 and $llrow["num"] == 0) {
    $nappikeikka .= "$formalku";
    $nappikeikka .= "<input type='hidden' name='toiminto' value='poista'>";
    $nappikeikka .= "<input type='submit' value='".t("Poista saapuminen")."'>";
    $nappikeikka .= "$formloppu";
  }

  // jos on kohdistettuja rivej‰ niin saa tehd‰ n‰it‰
  if ($kplyhteensa > 0) {
    $nappikeikka .= "$formalku";
    $nappikeikka .= "<input type='hidden' name='toiminto' value='varastopaikat'>";
    $nappikeikka .= "<input type='submit' value='".t("Varastopaikat")."'>";
    $nappikeikka .= "$formloppu";

    $nappikeikka .= "$formalku";
    $nappikeikka .= "<input type='hidden' name='toiminto' value='tulosta'>";
    $nappikeikka .= "<input type='submit' value='".t("Tulosta paperit")."'>";
    $nappikeikka .= "$formloppu";

    $nappikeikka .= $formalku;
    $nappikeikka .= "<input type='hidden' name='toiminto' value='tulosta_hintalaput'/>";
    $nappikeikka .= "<input type='submit' value='" . t("Tulosta hintalaput") . "'/>";
    $nappikeikka .= $formloppu;
  }

  // jos on kohdistettuja rivej‰ ja lis‰tiedot on syˆtetty ja varastopaikat on ok ja on viel‰ jotain viet‰v‰‰ varastoon
  if ($yhtiorow['suuntalavat'] != 'S' and $kplyhteensa > 0 and $varok == 1 and $kplyhteensa != $kplvarasto and $sarjanrook == 1) {
    $nappikeikka .= "$formalku";
    $nappikeikka .= "<input type='hidden' name='toiminto' value='kalkyyli'>";
    $nappikeikka .= "<input type='submit' value='".t("Vie varastoon")."'>";
    $nappikeikka .= "$formloppu";
  }

  if ($yhtiorow['suuntalavat'] == 'S') {
    $nappikeikka .= "$formalku";
    $nappikeikka .= "<input type='hidden' name='toiminto' value='suuntalavat'>";
    $nappikeikka .= "<input type='submit' value='".t("Suuntalavat")."'>";
    $nappikeikka .= "$formloppu";
  }

  $nappikeikka .=  "</tr></table>";
  $nappikeikka = str_replace('\n', '', $nappikeikka);
}

function echo_yhteenveto_table($params) {
  global $yhtiorow;

  echo "<br><br><table>";
  echo "<tr><th>".t("Tuotteita liitetty saapumisille yhteens‰")."</th><td align='right'> ".number_format($params['kaikkiliitettyyhteensa'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";
  echo "<tr><th>".t("Tuotteita viety varastoon yhteens‰")."</th><td align='right'> ".number_format($params['kaikkivarastossayhteensa'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";

  echo "<tr><th>".t("Eturahdin / kulujen vaikutus varastoon viedyille tuotteille")."</th><td align='right'> ".number_format($params['rahti_ja_kulut'], 2, '.', ' ')." {$yhtiorow['valkoodi']}</td></tr>";

  echo "<tr><th>".t("Vaihto-omaisuuslaskuja liitetty saapumisille")."</th><td align='right'>".number_format($params['vaihtoomaisuuslaskujayhteensa'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";
  echo "<tr><th>".t("Vaihto-omaisuuslaskuja liitetty saapumisille, v‰hennetyt kulut")."</th><td align='right'>".number_format($params['vaihtoomaisuuslaskujayhteensa_kulut'], 2, '.', ' ')." {$yhtiorow['valkoodi']}</td></tr>";
  echo "<tr><th>".t("Vaihto-omaisuuslaskuja liitt‰m‰tt‰ saapumisille")."</th><td align='right'>".number_format($params['row_vaihto']['vosumma'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";

  echo "<tr><th>".t("Huolinta-/rahtilaskuja liitetty saapumisille")."</th><td align='right'>".number_format($params['kululaskujayhteensa'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";
  echo "<tr><th>".t("Huolinta-/rahtilaskuja liitetty saapumisille, v‰hennetyt kulut")."</th><td align='right'>".number_format($params['kululaskujayhteensa_kulut'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";
  echo "<tr><th>".t("Huolinta-/rahtilaskuja osittain liitt‰m‰tt‰ saapumisille")."</th><td align='right'>".number_format($params['row_vaihto']['kuosasumma'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";
  echo "<tr><th>".t("Huolinta-/rahtilaskuja liitt‰m‰tt‰ saapumisille")."</th><td align='right'>".number_format($params['row_vaihto']['kusumma'], 2, '.', ' ')." $yhtiorow[valkoodi]</td></tr>";

  echo '<tr><td class="back"></td></tr>';
  echo '<tr>';
  echo "<th>".t('Saapumiset')."</th>";
  echo '<th>'.t('johon liitetty lasku (rivien arvo)').'</th>';
  echo '<th>'.t('johon liitetty lasku (tuloutettu arvo)').'</th>';
  echo '<th>'.t('johon ei liitetty lasku (rivien arvo)').'</th>';
  echo '<th>'.t('johon ei liitetty lasku (tuloutettu arvo)').'</th>';
  echo '<th>'.t('Laskut').'</th>';
  echo '</tr>';

  echo '<tr>';
  echo '<th>'.t('Viem‰tt‰ varastoon').'</th>';
  echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_ei_viety_summa'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_ei_viety_summa_tuloutettu'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_ei_viety_summa'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_ei_viety_summa_tuloutettu'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['laskut_ei_viety'], 2, '.', ' ')."</td>";
  echo '</tr>';

  echo '<tr>';
  echo '<th>'.t('Viem‰tt‰ varastoon osittain').'</th>';
  echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_osittain_ei_viety_summa'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_osittain_ei_viety_summa_tuloutettu'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_osittain_ei_viety_summa'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['laskut_ei_viety_osittain'], 2, '.', ' ')."</td>";
  echo '</tr>';

  echo '<tr>';
  echo '<th>'.t('Yhteens‰').'</th>';
  $yhteensa   = $params['liitetty_lasku_ei_viety_summa'] + $params['liitetty_lasku_osittain_ei_viety_summa'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa    = $params['liitetty_lasku_ei_viety_summa_tuloutettu'] + $params['liitetty_lasku_osittain_ei_viety_summa_tuloutettu'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa   = $params['ei_liitetty_lasku_ei_viety_summa'] + $params['ei_liitetty_lasku_osittain_ei_viety_summa'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa   = $params['ei_liitetty_lasku_ei_viety_summa_tuloutettu'] + $params['ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa   = $params['laskut_ei_viety'] + $params['laskut_ei_viety_osittain'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  echo '</tr>';

  echo '<tr>';
  echo '<th>'.t('Viety varastoon kokonaan').'</th>';
  echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_viety_summa'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_viety_summa_tuloutettu'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_viety_summa'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_viety_summa_tuloutettu'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['laskut_viety'], 2, '.', ' ')."</td>";
  echo '</tr>';

  echo '<tr>';
  echo '<th>'.t('Viety varastoon osittain').'</th>';
  echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_osittain_viety_summa'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['liitetty_lasku_osittain_viety_summa_tuloutettu'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_osittain_viety_summa'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['ei_liitetty_lasku_osittain_viety_summa_tuloutettu'], 2, '.', ' ')."</td>";
  echo "<td style='text-align:right;'>".number_format($params['laskut_osittain_viety'], 2, '.', ' ')."</td>";
  echo '</tr>';

  echo '<tr>';
  echo '<th>'.t('Yhteens‰').'</th>';
  $yhteensa   = $params['liitetty_lasku_viety_summa'] + $params['liitetty_lasku_osittain_viety_summa'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa    = $params['liitetty_lasku_viety_summa_tuloutettu'] + $params['liitetty_lasku_osittain_viety_summa_tuloutettu'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa   = $params['ei_liitetty_lasku_viety_summa'] + $params['ei_liitetty_lasku_osittain_viety_summa'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa   = $params['ei_liitetty_lasku_viety_summa_tuloutettu'] + $params['ei_liitetty_lasku_osittain_viety_summa_tuloutettu'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa   = $params['laskut_viety'] + $params['laskut_osittain_viety'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  echo '</tr>';

  echo '<tr>';
  echo '<th>'.t('Yhteens‰ kaikki').'</th>';
  $yhteensa   = $params['liitetty_lasku_ei_viety_summa'] + $params['liitetty_lasku_viety_summa'] + $params['liitetty_lasku_osittain_viety_summa'] + $params['liitetty_lasku_osittain_ei_viety_summa'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa    = $params['liitetty_lasku_ei_viety_summa_tuloutettu'] + $params['liitetty_lasku_osittain_ei_viety_summa_tuloutettu'] + $params['liitetty_lasku_viety_summa_tuloutettu'] + $params['liitetty_lasku_osittain_viety_summa_tuloutettu'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa   = $params['ei_liitetty_lasku_ei_viety_summa'] + $params['ei_liitetty_lasku_viety_summa'] + $params['ei_liitetty_lasku_osittain_viety_summa'] + $params['ei_liitetty_lasku_osittain_ei_viety_summa'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa   = $params['ei_liitetty_lasku_ei_viety_summa_tuloutettu'] + $params['ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu'] + $params['ei_liitetty_lasku_viety_summa_tuloutettu'] + $params['ei_liitetty_lasku_osittain_viety_summa_tuloutettu'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  $yhteensa   = $params['laskut_viety'] + $params['laskut_ei_viety'] + $params['laskut_osittain_viety'] + $params['laskut_ei_viety_osittain'];
  echo "<th style='text-align:right;'>".number_format($yhteensa, 2, '.', ' ')."</th>";
  echo '</tr>';
  echo "</table>";
}

function hae_yhteenveto_tiedot($toimittajaid = null, $toimipaikka = 0, $pp = null, $kk = null, $vv = null) {
  global $kukarow, $yhtiorow, $onkolaajattoimipaikat, $kaikkiliitettyyhteensa, $kaikkivarastossayhteensa, $rahti_ja_kulut,
  $vaihtoomaisuuslaskujayhteensa, $kululaskujayhteensa, $vaihtoomaisuuslaskujayhteensa_kulut, $kululaskujayhteensa_kulut;

  if (!$pp) $pp = date('d');
  if (!$kk) $kk = date('m');
  if (!$vv) $vv = date('Y');

  if ($toimittajaid == null) {
    $toimittaja_where = '';
  }
  else {
    $toimittaja_where = "AND lasku.liitostunnus = '{$toimittajaid}'";
  }

  $toimipaikkalisa = ($onkolaajattoimipaikat and isset($toimipaikka) and $toimipaikka != 'kaikki') ? "AND lasku.yhtio_toimipaikka = '".(int) $toimipaikka."'" : "";

  $compare_date1 = new DateTime("now");
  $compare_date2 = new DateTime("{$vv}-{$kk}-{$pp}");

  $comp = $compare_date1->format('Y-m-d') != $compare_date2->format('Y-m-d');

  $query_ale_lisa = generoi_alekentta("O");

  if ($comp) {

    $_groupby = "GROUP BY lasku.liitostunnus";
    $_where1 = "AND lasku.alatila = ''
                AND lasku.mapvm = '0000-00-00'
                AND lasku.kohdistettu IN ('','K')";
    $_where2 = "AND lasku.alatila = 'X'
                AND lasku.mapvm >= '{$vv}-{$kk}-{$pp}'
                AND lasku.kohdistettu = 'X'";

    // n‰ytet‰‰n mill‰ toimittajilla on keskener‰isi‰ keikkoja
    $query = "SELECT
              group_concat(distinct lasku.laskunro SEPARATOR ', ') keikat,
              round(sum(IF((tilausrivi.laskutettuaika <= '{$vv}-{$kk}-{$pp}' AND tilausrivi.laskutettuaika != '0000-00-00' AND tilausrivi.kpl != 0), tilausrivi.rivihinta, 0)),2) varastossaarvo,
              ROUND(SUM(IF((tilausrivi.laskutettuaika <= '{$vv}-{$kk}-{$pp}' AND tilausrivi.laskutettuaika != '0000-00-00'), tilausrivi.kpl, 0) * tilausrivi.hinta * {$query_ale_lisa}), 2) varastoonvietyarvo,
              round(sum((tilausrivi.varattu+tilausrivi.kpl) * tilausrivi.hinta * {$query_ale_lisa}),2) kohdistettuarvo
              FROM lasku USE INDEX (yhtio_tila_mapvm)
              LEFT JOIN tilausrivi USE INDEX (uusiotunnus_index) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O')
              WHERE lasku.yhtio     = '{$kukarow['yhtio']}'
              and lasku.tila        = 'K'
              and lasku.vanhatunnus = 0
              AND lasku.luontiaika  <= '{$vv}-{$kk}-{$pp}'
              {$toimipaikkalisa}
              {$toimittaja_where}";

    $query = "( {$query}
                {$_where1}
                {$_groupby})
                UNION
              ( {$query}
                {$_where2}
                {$_groupby})";
    $result_x = pupe_query($query);

    $kaikkiliitettyyhteensa = 0;
    $kaikkivarastossayhteensa = 0;
    $rahti_ja_kulut = 0;
    $vaihtoomaisuuslaskujayhteensa = 0;
    $kululaskujayhteensa = 0;
    $vaihtoomaisuuslaskujayhteensa_kulut = 0;
    $kululaskujayhteensa_kulut = 0;

    while ($row_x = mysql_fetch_assoc($result_x)) {
      $kaikkivarastossayhteensa += $row_x['varastossaarvo'];
      $kaikkiliitettyyhteensa += $row_x['kohdistettuarvo'];
      $rahti_ja_kulut  += ($row_x["varastossaarvo"] - $row_x['varastoonvietyarvo']);

      $query = "SELECT
                sum(if(vienti in ('C','F','I','J','K','L'), arvo * vienti_kurssi, 0)) vosumma,
                sum(if(vienti in ('C','F','I','J','K','L'), (osto_kulu + osto_rahti + osto_rivi_kulu), 0)) vosumma_kulut,
                sum(if(vienti not in ('C','F','I','J','K','L'), arvo * vienti_kurssi, 0)) kusumma,
                sum(if(vienti not in ('C','F','I','J','K','L'), (osto_kulu + osto_rahti + osto_rivi_kulu), 0)) kusumma_kulut
                FROM lasku use index (yhtio_tila_laskunro)
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND tila        = 'K'
                AND vanhatunnus > 0
                AND laskunro    IN ({$row_x['keikat']})";
      $laskuja_result = pupe_query($query);
      $laskuja_row = mysql_fetch_assoc($laskuja_result);

      $vaihtoomaisuuslaskujayhteensa  += $laskuja_row["vosumma"];
      $kululaskujayhteensa       += $laskuja_row["kusumma"];
      $vaihtoomaisuuslaskujayhteensa_kulut += $laskuja_row['vosumma_kulut'];
      $kululaskujayhteensa_kulut += $laskuja_row['kusumma_kulut'];
    }
  }

  // haetaan vaihto-omaisuus- ja huolinta/rahti- laskut joita ei oo liitetty saapumisiin
  $query = "SELECT
            lasku.tunnus,
            if(lasku.vienti in ('C','F','I','J','K','L'), lasku.summa * lasku.vienti_kurssi, 0) vosumma,
            if(lasku.vienti in ('B','E','H'),         lasku.summa * lasku.vienti_kurssi, 0) kusumma,
            sum(if(lasku.vienti in ('C','F','I','J','K','L'), tiliointi.summa, 0)) voalvit,
            sum(if(lasku.vienti in ('B','E','H'),         tiliointi.summa, 0)) kualvit
            FROM lasku
            LEFT JOIN tiliointi USE INDEX (tositerivit_index) ON (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and tiliointi.tilino = '$yhtiorow[alv]' and tiliointi.korjattu = '' AND if(lasku.summa > 0, tiliointi.summa, tiliointi.summa*-1) > 0)
            LEFT JOIN lasku AS liitos ON liitos.yhtio = lasku.yhtio AND liitos.vanhatunnus = lasku.tunnus AND liitos.tila = 'K'
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tila    IN ('H','Y','M','P','Q')
            AND lasku.vienti  in ('B','C','J','E','F','K','H','I','L')
            AND (liitos.tunnus IS NULL or liitos.luontiaika > '{$vv}-{$kk}-{$pp}')
            AND (lasku.tapvm <= '{$vv}-{$kk}-{$pp}' AND lasku.tapvm >= date_sub('{$vv}-{$kk}-{$pp}', interval 12 month))
            {$toimittaja_where}
            GROUP BY lasku.tunnus";
  $result_vaihto_omaisuus = pupe_query($query);

  $rv_vosumma = 0;
  $rv_voalvit = 0;
  $rv_kusumma = 0;
  $rv_kualvit = 0;

  while ($row_vaihto = mysql_fetch_assoc($result_vaihto_omaisuus)) {
    $rv_vosumma += $row_vaihto["vosumma"];
    $rv_voalvit += $row_vaihto["voalvit"];
    $rv_kusumma += $row_vaihto["kusumma"];
    $rv_kualvit += $row_vaihto["kualvit"];
  }

  $row_vaihto["vosumma"] = $rv_vosumma - $rv_voalvit;
  $row_vaihto["kusumma"] = $rv_kusumma - $rv_kualvit;

  // haetaan rahti/huolinta laskut jotka on liitetty vain osittain saapumisiin
  $query = "SELECT
            (SELECT sum(summa) summa
               FROM tiliointi
               WHERE tiliointi.yhtio  = lasku.yhtio
               AND tiliointi.ltunnus  = lasku.tunnus
               AND tiliointi.tilino   in ('$yhtiorow[varasto]','$yhtiorow[raaka_ainevarasto]')
               AND tiliointi.korjattu = '') varastossa,
            sum(liitos.arvo*liitos.vienti_kurssi) kohdistettu
            FROM lasku
            JOIN lasku AS liitos on (liitos.yhtio = lasku.yhtio and liitos.vanhatunnus = lasku.tunnus and liitos.tila = 'K')
            WHERE lasku.yhtio         = '$kukarow[yhtio]'
            AND lasku.tila            in ('H','Y','M','P','Q')
            AND lasku.vienti          in ('B','E','H')
            AND (lasku.tapvm <= '{$vv}-{$kk}-{$pp}' AND lasku.tapvm >= date_sub('{$vv}-{$kk}-{$pp}', interval 12 month))
            {$toimittaja_where}
            GROUP BY lasku.tunnus
            HAVING varastossa != kohdistettu";
  $result_huolintarahdit = pupe_query($query);

  $row_vaihto["kuosasumma"] = 0;

  while ($row_huorah = mysql_fetch_assoc($result_huolintarahdit)) {
    $row_vaihto["kuosasumma"] += ($row_huorah["varastossa"]-$row_huorah["kohdistettu"]);
  }

  $liitetty_lasku_viety_summa     = 0;
  $liitetty_lasku_viety_summa_tuloutettu = 0;
  $ei_liitetty_lasku_viety_summa = 0;
  $ei_liitetty_lasku_viety_summa_tuloutettu =

    $liitetty_lasku_ei_viety_summa    = 0;
  $liitetty_lasku_ei_viety_summa_tuloutettu = 0;
  $ei_liitetty_lasku_ei_viety_summa = 0;
  $ei_liitetty_lasku_ei_viety_summa_tuloutettu = 0;

  $liitetty_lasku_osittain_ei_viety_summa = 0;
  $liitetty_lasku_osittain_ei_viety_summa_tuloutettu = 0;
  $ei_liitetty_lasku_osittain_ei_viety_summa = 0;
  $ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu = 0;

  $liitetty_lasku_osittain_viety_summa  = 0;
  $liitetty_lasku_osittain_viety_summa_tuloutettu = 0;
  $ei_liitetty_lasku_osittain_viety_summa = 0;
  $ei_liitetty_lasku_osittain_viety_summa_tuloutettu = 0;

  $laskut_ei_viety     = 0;
  $laskut_ei_viety_osittain = 0;
  $laskut_viety       = 0;
  $laskut_osittain_viety = 0;

  if ($comp) {

    $query = "SELECT lasku.laskunro,
              lasku.tila,
              lasku.vanhatunnus,
              lasku.tunnus,
              count(DISTINCT liitos.tunnus) liitetty,
              group_concat(liitos.vanhatunnus) tunnukset
              FROM lasku
              LEFT JOIN lasku AS liitos ON liitos.yhtio = lasku.yhtio AND liitos.laskunro = lasku.laskunro AND liitos.vanhatunnus > 0 AND liitos.vienti IN ('C','F','I','J','K','L') AND liitos.tila = 'K'
                AND liitos.luontiaika <= '{$vv}-{$kk}-{$pp} 00:00:00'
              WHERE  lasku.yhtio      = '{$kukarow['yhtio']}'
              AND lasku.tila          = 'K'
              AND ((lasku.alatila = '' AND lasku.mapvm = '0000-00-00' AND lasku.kohdistettu in ('', 'K'))
                OR
                (lasku.alatila = 'X' AND lasku.mapvm >= '{$vv}-{$kk}-{$pp}' AND lasku.kohdistettu = 'X'))
              AND lasku.vanhatunnus   = 0
              AND lasku.luontiaika    <= '{$vv}-{$kk}-{$pp} 00:00:00'
              {$toimipaikkalisa}
              {$toimittaja_where}
              GROUP BY 1,2,3,4";
  }
  else {
    $query = "SELECT lasku.laskunro,
              lasku.tila,
              lasku.vanhatunnus,
              lasku.tunnus,
              count(DISTINCT liitos.tunnus) liitetty,
              group_concat(liitos.vanhatunnus) tunnukset
              FROM lasku
              LEFT JOIN lasku AS liitos ON liitos.yhtio = lasku.yhtio AND liitos.laskunro = lasku.laskunro AND liitos.vanhatunnus > 0 AND liitos.vienti IN ('C','F','I','J','K','L') AND liitos.tila = 'K'
              WHERE  lasku.yhtio    = '{$kukarow['yhtio']}'
              AND lasku.tila        = 'K'
              AND lasku.alatila     = ''
              AND lasku.mapvm       = '0000-00-00'
              AND lasku.vanhatunnus = 0
              {$toimipaikkalisa}
              {$toimittaja_where}
              GROUP BY 1,2,3,4";
  }

  $result = pupe_query($query);

  //haetaan saapuvia ostotilauksia, joihin liitetty tai ei liitetty lasku (kts. liitetty)
  while ($row = mysql_fetch_assoc($result)) {

    if ($comp) {
      $query = "SELECT
                sum(IF((laskutettuaika <= '{$vv}-{$kk}-{$pp}' AND laskutettuaika != '0000-00-00'), kpl, 0) * hinta * {$query_ale_lisa}) viety,
                sum(IF(laskutettuaika > '{$vv}-{$kk}-{$pp}', kpl, IF(laskutettuaika = '0000-00-00', varattu, 0)) * hinta * {$query_ale_lisa}) ei_viety,
                sum(IF((laskutettuaika <= '{$vv}-{$kk}-{$pp}' AND laskutettuaika != '0000-00-00'), rivihinta, 0)) tuloutettu
                FROM tilausrivi
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND uusiotunnus = {$row['tunnus']}
                AND tyyppi      = 'O'";
    }
    else {
      $query = "SELECT
                sum(kpl * hinta * {$query_ale_lisa}) viety,
                sum(varattu * hinta * {$query_ale_lisa}) ei_viety,
                sum(rivihinta) tuloutettu
                FROM tilausrivi
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND uusiotunnus = {$row['tunnus']}
                AND tyyppi      = 'O'";
    }

    $result2 = pupe_query($query);
    $tilausrivirow = mysql_fetch_assoc($result2);

    if ($row['liitetty'] == 0) {

      if ($tilausrivirow['viety'] == 0 and $tilausrivirow['ei_viety'] != 0) {
        $ei_liitetty_lasku_ei_viety_summa += $tilausrivirow['ei_viety'];
        $ei_liitetty_lasku_ei_viety_summa_tuloutettu += $tilausrivirow['tuloutettu'];
      }
      elseif ($tilausrivirow['viety'] != 0 and $tilausrivirow['ei_viety'] == 0) {
        //viety kokonaan
        $ei_liitetty_lasku_viety_summa += $tilausrivirow['viety'];
        $ei_liitetty_lasku_viety_summa_tuloutettu += $tilausrivirow['tuloutettu'];
      }
      else {
        //saapuminen viety osittain varastoon ja ei liitetty lasku
        $ei_liitetty_lasku_osittain_viety_summa += $tilausrivirow['viety'];
        $ei_liitetty_lasku_osittain_viety_summa_tuloutettu += $tilausrivirow['tuloutettu'];
        $ei_liitetty_lasku_osittain_ei_viety_summa += $tilausrivirow['ei_viety'];
        $ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu += $tilausrivirow['tuloutettu'];
      }
    }
    else {
      $query = "SELECT round(lasku.vienti_kurssi * lasku.summa, 2) summa,
                sum(round(tiliointi.summa, 2)) alvit
                FROM lasku
                LEFT JOIN tiliointi USE INDEX (tositerivit_index) ON (tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus and tiliointi.tilino = '$yhtiorow[alv]' and tiliointi.korjattu = '' AND if(lasku.summa > 0, tiliointi.summa, tiliointi.summa*-1) > 0)
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.tunnus  IN ({$row['tunnukset']})
                GROUP BY 1";
      $result_laskut = pupe_query($query);

      $laskujensummat = 0;

      while ($laskut = mysql_fetch_assoc($result_laskut)) {
        $laskujensummat += ($laskut['summa']-$laskut['alvit']);
      }

      //on liitetty lasku
      if ($tilausrivirow['viety'] == 0 and $tilausrivirow['ei_viety'] != 0) {
        // ei viety ollenkaan
        $liitetty_lasku_ei_viety_summa += $tilausrivirow['ei_viety'];
        $liitetty_lasku_ei_viety_summa_tuloutettu += $tilausrivirow['tuloutettu'];
        $laskut_ei_viety += $laskujensummat;
      }
      elseif ($tilausrivirow['viety'] != 0 and $tilausrivirow['ei_viety'] == 0) {
        //viety kokonaan
        $liitetty_lasku_viety_summa += $tilausrivirow['viety'];
        $liitetty_lasku_viety_summa_tuloutettu += $tilausrivirow['tuloutettu'];
        $laskut_viety += $laskujensummat;
      }
      else {
        //saapuminen viety osittain varastoon ja liitetty lasku
        $liitetty_lasku_osittain_viety_summa += $tilausrivirow['viety'];
        $liitetty_lasku_osittain_viety_summa_tuloutettu += $tilausrivirow['tuloutettu'];
        $liitetty_lasku_osittain_ei_viety_summa += $tilausrivirow['ei_viety'];
        //$liitetty_lasku_osittain_ei_viety_summa_tuloutettu += $tilausrivirow['tuloutettu'];

        $laskut_osittain_viety += $laskujensummat;
        //$laskut_ei_viety_osittain += $laskujensummat;
      }
    }
  }

  return array(
    $liitetty_lasku_viety_summa,
    $liitetty_lasku_viety_summa_tuloutettu,
    $ei_liitetty_lasku_viety_summa,
    $ei_liitetty_lasku_viety_summa_tuloutettu,
    $liitetty_lasku_ei_viety_summa,
    $ei_liitetty_lasku_ei_viety_summa,
    $ei_liitetty_lasku_ei_viety_summa_tuloutettu,
    $liitetty_lasku_osittain_viety_summa,
    $liitetty_lasku_osittain_viety_summa_tuloutettu,
    $ei_liitetty_lasku_osittain_viety_summa,
    $ei_liitetty_lasku_osittain_viety_summa_tuloutettu,
    $laskut_ei_viety,
    $laskut_ei_viety_osittain,
    $laskut_viety,
    $laskut_osittain_viety,
    $row_vaihto,
    $liitetty_lasku_osittain_ei_viety_summa,
    $liitetty_lasku_osittain_ei_viety_summa_tuloutettu,
    $ei_liitetty_lasku_osittain_ei_viety_summa,
    $ei_liitetty_lasku_osittain_ei_viety_summa_tuloutettu,
    $liitetty_lasku_ei_viety_summa_tuloutettu
  );
}

echo "<SCRIPT LANGUAGE=JAVASCRIPT>
nappikeikka = \"$nappikeikka\";
document.getElementById('toimnapit').innerHTML = nappikeikka;
</SCRIPT>";

if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php")  !== FALSE) {
  require "inc/footer.inc";
}
