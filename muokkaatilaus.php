<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {

  if (isset($_REQUEST["ajax"]) and $_REQUEST["ajax"] == "OK") {
    $no_head = "yes";
  }

  require "inc/parametrit.inc";

  if (isset($toim)) {
    $toim = strtoupper($toim);
  }

  if (isset($tee) and $tee == "lataa_tiedosto") {
    readfile("/tmp/".$tmpfilenimi);
    exit;
  }

  if ($tee == 'NAYTATILAUS') {
    require "raportit/naytatilaus.inc";
    echo "<hr>";
    exit;
  }

  require 'valmistuslinjat.inc';
  require 'validation/Validation.php';

  if (isset($_REQUEST["ajax"]) and $_REQUEST["ajax"] == "OK") {

    $otunnus = (int) $_REQUEST['otunnus'];

    $query = "SELECT nimi, kuka
              FROM kuka
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND kesken   = '{$otunnus}'
              AND kesken  != 0
              AND kuka    != '{$kukarow['kuka']}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 0) {
      $row = mysql_fetch_assoc($result);
      echo t("Tilaus on aktiivisena käyttäjällä"), " {$row['nimi']} ({$row['kuka']}). ", t("Tilausta ei voi tällä hetkellä muokata");
    }
    else {
      echo false;
    }

    exit;
  }

  if ($toim == "LAVAKERAYS") {
    echo "<script src='inc/checkboxrange.js'></script>";
  }

  ?>

 <script type='text/javascript' language='javascript'>
    $(function() {
      $.ajaxSetup({
        url: '<?php echo $palvelin2 ?>muokkaatilaus.php?toim=EXTRANET&indexvas=1&ajax=OK',
        type: 'POST',
        cache: false
      });

      $('.myyntiformi').on('click', '.check_kesken', function(e) {
        e.preventDefault();

        var otunnus = $(this).siblings('.tilausnumero').val();

        $.ajax({
          data: {
            otunnus: otunnus
          },
          success: function(retval) {
            if (retval) {
              alert(retval);
              window.location.replace('<?php echo $palvelin2 ?>muokkaatilaus.php?toim=EXTRANET&indexvas=1');
            }
            else {
              $('#myyntiformi_'+otunnus).submit();
            }
          }
        });
      });

      $('#lavakerays_valitsekaikki').on('click', function(e) {
          if($(this).is(":checked")) {
            $('.lavakerays_valitse_keraykseen').prop('checked', true);
          }
          else {
            $('.lavakerays_valitse_keraykseen').prop('checked', false);
          }
      });

      $('#lavakerays_siirra_keraykseen_nappi').on('click', function() {
        // Siirretään ruksatut formin mukana
        var valitut_tilaukset = []

        $('.lavakerays_valitse_keraykseen').each(function(i, obj) {
          if ($(obj).is(":checked")) {
            valitut_tilaukset.push($(obj).val());
          }
        });

        $('#lavakerays_keraykseen').val(valitut_tilaukset.join(","));
      });
    });
  </script>

  <?php
}

if (!isset($toim)) $toim = '';

if ($toim == "VASTAANOTA_REKLAMAATIO" and $yhtiorow['reklamaation_kasittely'] != 'U') {
  echo "<font class='error'>".t("HUOM: Ohjelma on käytössä vain kun käytetään laajaa reklamaatioprosessia")."!</font>";
  exit;
}

if (isset($tee) and $tee == 'MITATOI_TARJOUS') {
  if (($toim == "TARJOUS" or $toim == "TARJOUSSUPER" or $toim == "SUPER") and $tilausnumero != "") {
    if ($toim == "SUPER") {
      $laskutyyppilisa = " AND tila in ('L', 'N') ";
      $tilausrivityyppilisa = " AND tyyppi = 'L' ";
    }
    else {
      $laskutyyppilisa = " AND tila = 'T' ";
      $tilausrivityyppilisa = " AND tyyppi = 'T' ";
    }
    pupemaster_start();

    $query_tarjous = "UPDATE lasku
                      SET alatila = tila,
                      tila        = 'D',
                      muutospvm   = now(),
                      comments    = CONCAT(comments, ' $kukarow[nimi] ($kukarow[kuka]) ".t("mitätöi tilauksen ohjelmassa muokkaatilaus.php")." 2')
                      WHERE yhtio = '$kukarow[yhtio]'
                      {$laskutyyppilisa}
                      AND tunnus  = $tilausnumero";
    pupe_query($query_tarjous);

    $query = "UPDATE tilausrivi
              SET tyyppi = 'D'
              WHERE yhtio = '{$kukarow['yhtio']}'
              {$tilausrivityyppilisa}
              AND otunnus = $tilausnumero";
    pupe_query($query);

    //Nollataan sarjanumerolinkit
    vapauta_sarjanumerot("", $tilausnumero);

    echo "<font class='message'>".t("Mitätöitiin tilaus").": $tilausnumero</font><br><br>";
    pupemaster_stop();
  }
}

if ($toim == 'TARJOUS' and $tee == 'MITATOI_TARJOUS_KAIKKI' and $tunnukset != "") {
  pupemaster_start();

  $query = "UPDATE lasku
            SET tila    = 'D',
            alatila     = 'T',
            comments    = CONCAT(comments, ' $kukarow[nimi] ($kukarow[kuka]) ".t("mitätöi tilauksen ohjelmassa muokkaatilaus.php")." 1')
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tila    = 'T'
            AND tunnus  IN {$tunnukset}";
  pupe_query($query);

  $query = "UPDATE tilausrivi
            SET tyyppi = 'D'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tyyppi  = 'T'
            AND otunnus IN {$tunnukset}";
  pupe_query($query);

  foreach (explode(",", preg_replace("/[^0-9,]/", "", $tunnukset)) as $sarjatun) {
    //Nollataan sarjanumerolinkit
    vapauta_sarjanumerot("", $sarjatun);
  }

  pupemaster_stop();
}

if ($toim == 'LAVAKERAYS' and $tee == 'KERAA_KAIKKI_LAVAKERAYS' and $lavakerays_keraykseen != "") {
  pupemaster_start();

  if (!function_exists('lavakerays_valmis')) {
    function lavakerays_valmis($tilaus) {
      global $kukarow, $yhtiorow, $pupe_root_polku;

      $query = "SELECT *
                FROM lasku
                WHERE tunnus = $tilaus
                and yhtio = '$kukarow[yhtio]'
                and tila = 'N'
                and alatila = 'FF'";
      $lasres = pupe_query($query);
      $laskurow = mysql_fetch_assoc($lasres);

      $kukarow['kesken'] = $tilaus;

      require "tilauskasittely/tilaus-valmis.inc";

      return $tilausnumerot;
    }
  }

  $laskuja = count(explode(",", $lavakerays_keraykseen));

  // katsotaan, ettei tilaus ole kenelläkään auki ruudulla
  $query = "SELECT *
            FROM kuka
            WHERE kesken in ($lavakerays_keraykseen)
            and yhtio = '{$kukarow['yhtio']}'";
  $keskenresult = pupe_query($query);

  // jos kaikki on ok...
  if (mysql_num_rows($keskenresult) == 0) {
    $tilausnumeroita = array();

    foreach(explode(",", $lavakerays_keraykseen) as $tilaus) {
      $tilausnumerot = lavakerays_valmis($tilaus);

      $tilausnumeroita = array_merge($tilausnumeroita, $tilausnumerot);
    }

    $tilausnumeroita = implode(",", $tilausnumeroita);

    // Jos tilauksella on vain puuttetia, niin se mitätöidään tilaus-valmis.inc:ssä
    // tilaus on poistettava tilausnumeroita-listasta jos näin on käynyt
    $query = " SELECT group_concat(tunnus) tilausnumeroita
               from lasku
               where tunnus in ($tilausnumeroita)
               and yhtio = '$kukarow[yhtio]'
               and tila = 'N'
               and alatila = 'A'";
    $result = pupe_query($query);
    $tilrow = mysql_fetch_assoc($result);

    $tilausnumeroita = $tilrow['tilausnumeroita'];

    // Tulostetaan keräyslistat
    // Haetaan ekan kerättävän tilauksen tiedot
    $query = " SELECT *
               from lasku
               where tunnus in ($tilausnumeroita)
               and yhtio = '$kukarow[yhtio]'
               and tila = 'N'
               and alatila = 'A'
               ORDER BY clearing DESC
               LIMIT 1";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      $laskurow = mysql_fetch_array($result);

      require "tilauskasittely/tilaus-valmis-tulostus.inc";
    }
    else {
      echo "<font class='error'>".t("Keräyslista on jo tulostettu")."! ($tilausnumeroita)</font><br>";
    }
  }
  else {
    $keskenrow = mysql_fetch_array($keskenresult);
    echo t("Tilaus on kesken käyttäjällä").", $keskenrow[nimi], ".t("ota yhteyttä häneen ja käske hänen laittaa vähän vauhtia tähän touhuun")."!<br>";
    $tee2 = "";
  }

  pupemaster_stop();
  unset($tee);
}

if (isset($tee) and $tee == 'TOIMITA_ENNAKKO' and in_array($yhtiorow["ennakkotilausten_toimitus"], array('M','K'))) {
  pupemaster_start();

  $toimita_ennakko = explode(",", $toimita_ennakko);

  foreach ($toimita_ennakko as $tilausnro) {
    $query  = "SELECT *
               FROM lasku
               WHERE yhtio      = '$kukarow[yhtio]'
               AND tunnus       = '$tilausnro'
               and tila         IN ('E', 'N')
               and alatila      IN ('','A','J')
               and tilaustyyppi = 'E'";
    $jtrest = pupe_query($query);

    while ($laskurow = mysql_fetch_assoc($jtrest)) {

      $query  = "UPDATE lasku
                 SET tila    = 'N',
                 alatila      = '',
                 clearing     = 'ENNAKKOTILAUS',
                 tilaustyyppi = ''
                 WHERE yhtio  = '$kukarow[yhtio]'
                 and tunnus   = '$laskurow[tunnus]'";
      $apure  = pupe_query($query);

      $laskurow["tila"]         = "N";
      $laskurow["alatila"]      = "";
      $laskurow["clearing"]     = "ENNAKKOTILAUS";
      $laskurow["tilaustyyppi"] = "";

      // Päivitetään rivit
      $query  = "SELECT tunnus, tuoteno, hyllyalue, hyllynro, hyllyvali, hyllytaso
                 FROM tilausrivi
                 WHERE yhtio = '$kukarow[yhtio]'
                 and otunnus = '$laskurow[tunnus]'
                 and tyyppi  = 'E'";
      $apure  = pupe_query($query);

      while ($rivirow = mysql_fetch_assoc($apure)) {

        $varastorotunnus = kuuluukovarastoon($rivirow["hyllyalue"], $rivirow["hyllynro"]);

        if ($laskurow["varasto"] > 0 and $varastorotunnus != $laskurow["varasto"]) {
          // Katotaan, että rivit myydään halutusta varastosta
          $query = "SELECT tuotepaikat.hyllyalue,
                    tuotepaikat.hyllynro,
                    tuotepaikat.hyllytaso,
                    tuotepaikat.hyllyvali
                    FROM tuotepaikat
                    WHERE tuotepaikat.yhtio = '{$kukarow['yhtio']}'
                    AND tuotepaikat.tuoteno = '{$rivirow['tuoteno']}'
                    and tuotepaikat.varasto = '{$laskurow["varasto"]}'
                    ORDER BY saldo desc
                    LIMIT 1";
          $tuotepaikka_result = pupe_query($query);

          if (mysql_num_rows($tuotepaikka_result) == 1) {
            $tuotepaikka_row = mysql_fetch_assoc($tuotepaikka_result);

            $rivirow["hyllyalue"] = $tuotepaikka_row["hyllyalue"];
            $rivirow["hyllynro"]  = $tuotepaikka_row["hyllynro"];
            $rivirow["hyllyvali"] = $tuotepaikka_row["hyllyvali"];
            $rivirow["hyllytaso"] = $tuotepaikka_row["hyllytaso"];
          }
        }
        elseif ($varastorotunnus == 0) {
          // Rivillä ei ollut mitään viksua paikkaa
          $query = "SELECT tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllytaso, tuotepaikat.hyllyvali
                    FROM tuotepaikat
                    WHERE tuotepaikat.yhtio  = '{$kukarow['yhtio']}'
                    AND tuotepaikat.tuoteno  = '{$rivirow['tuoteno']}'
                    AND tuotepaikat.oletus  != ''";
          $tuotepaikka_result = pupe_query($query);
          $tuotepaikka_row = mysql_fetch_assoc($tuotepaikka_result);

          $rivirow["hyllyalue"] = $tuotepaikka_row["hyllyalue"];
          $rivirow["hyllynro"]  = $tuotepaikka_row["hyllynro"];
          $rivirow["hyllyvali"] = $tuotepaikka_row["hyllyvali"];
          $rivirow["hyllytaso"] = $tuotepaikka_row["hyllytaso"];
        }

        $query  = "UPDATE tilausrivi
                   SET tyyppi   = 'L',
                   hyllyalue   = '{$rivirow["hyllyalue"]}',
                   hyllynro    = '{$rivirow["hyllynro"]}',
                   hyllyvali   = '{$rivirow["hyllyvali"]}',
                   hyllytaso   = '{$rivirow["hyllytaso"]}'
                   WHERE yhtio = '$kukarow[yhtio]'
                   and tunnus  = '$rivirow[tunnus]'
                   and tyyppi  = 'E'";
        $updapure  = pupe_query($query);
      }

      if ($yhtiorow["ennakkotilausten_toimitus"] == 'M') {
        // tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
        $kukarow["kesken"] = $laskurow["tunnus"];

        $kateisohitus = "X";
        $mista = "jtselaus";

        require "tilauskasittely/tilaus-valmis.inc";
      }
    }
  }

  pupemaster_stop();
  unset($tee);
}

if (!isset($asiakastiedot)) $asiakastiedot = '';
if (!isset($viitetiedot)) $viitetiedot = '';
if (!isset($limit)) $limit = '';
if (!isset($etsi)) $etsi = '';
if (!isset($pv_rajaus)) $pv_rajaus = $yhtiorow['muokkaatilaus_pv_rajaus'];
if (!isset($tee_excel)) $tee_excel = '';

// scripti balloonien tekemiseen
js_popup();
enable_ajax();

// Saako poistaa tarjouksia
$deletarjous = FALSE;
// Saako poistaa tilauksia
$deletilaus = FALSE;

if ($toim == "TARJOUS" or $toim == "TARJOUSSUPER" or $toim == "HYPER") {
  //Saako poistaa tarjouksia
  $query = "SELECT yhtio
            FROM oikeu
            WHERE yhtio  = '$kukarow[yhtio]'
            and kuka     = '$kukarow[kuka]'
            and nimi     = 'tilauskasittely/tilaus_myynti.php'
            and alanimi  = 'TARJOUS'
            and paivitys = '1'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $deletarjous = TRUE;
  }
}
elseif ($toim == "SUPER") {
  //Saako poistaa tilauksia
  if (tarkista_oikeus('tilauskasittely/tilaus_myynti.php', 'TILAUS', 1)) {
    $deletilaus = TRUE;
  }
}

echo "  <script type='text/javascript' language='JavaScript'>
    <!--
      function verify() {
        msg = '".t("Oletko varma?")."';

        if (confirm(msg)) {
          return true;
        }
        else {
          skippaa_tama_submitti = true;
          return false;
        }
      }

      function tarkista_mitatointi(count, type) {
        if (typeof type !== 'undefined' && type === 'SUPER') {
          msg = '".t("Oletko varma, että haluat mitätöidä ")."' + count + '".t(" tilausta?")."';
        }
        else {
          msg = '".t("Oletko varma, että haluat mitätöidä ")."' + count + '".t(" tarjousta?")."';
        }

        if (confirm(msg)) {
          return true;
        }
        else {
          skippaa_tama_submitti = true;
          return false;
        }
      }

      function tarkista_tulostus() {
        msg = '".t("Oletko varma, että haluat siirtää vaitut tilaukset keräykseen?")."';

        if (confirm(msg)) {
          return true;
        }
        else {
          skippaa_tama_submitti = true;
          return false;
        }
      }
    -->
    </script>";

$toim = strtoupper($toim);

if ($toim == "" or $toim == "SUPER" or $toim == "SUPER_EITYOM" or $toim == "SUPER_EILUONTITAPATYOM" or $toim == "LASKUTUSKIELTO" or $toim == "KESKEN" or $toim == "TOSI_KESKEN" or $toim == 'KESKEN_TAI_TOIMITETTAVISSA' or $toim == "LAVAKERAYS") {
  $pika_oikeu = tarkista_oikeus('tilaus_myynti.php', 'PIKATILAUS');
  $rivi_oikeu = tarkista_oikeus('tilaus_myynti.php', 'RIVISYOTTO');
}

if ($toim == "" or $toim == "SUPER" or $toim == "SUPER_EITYOM" or $toim == "SUPER_EILUONTITAPATYOM" or $toim == "KESKEN") {
  $otsikko = t("myyntitilausta");
}
elseif ($toim == "ENNAKKO") {
  $otsikko = t("ennakkotilausta");
}
elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
  $otsikko = t("työmääräystä");
}
elseif ($toim == "REKLAMAATIO" or $toim == "REKLAMAATIOSUPER") {
  $otsikko = t("reklamaatiota");
}
elseif ($toim == "VASTAANOTA_REKLAMAATIO") {
  $otsikko = t("reklamaatio");
}
elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
  $otsikko = t("sisäistä työmääräystä");
}
elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
  $otsikko = t("valmistusta");
}
elseif ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER") {
  $otsikko = t("varastosiirtoa");
}
elseif ($toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER" or $toim == "MYYNTITILITOIMITA") {
  $otsikko = t("myyntitiliä");
}
elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
  $otsikko = t("tarjousta");
}
elseif ($toim == "EXTTARJOUS") {
  $otsikko = t("ext-tarjousta");
}
elseif ($toim == "EXTENNAKKO") {
  $otsikko = t("ext-ennakkoa");
}
elseif ($toim == "LASKUTUSKIELTO") {
  $otsikko = t("laskutuskieltoa");
}
elseif ($toim == "EXTRANET") {
  $otsikko = t("hyväksyttäviä tilauksia");
}
elseif ($toim == "LAVAKERAYS") {
  $otsikko = t("HB-tilauksia");
}
elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
  $otsikko = t("osto-tilausta");
}
elseif ($toim == "HAAMU") {
  $otsikko = t("työ/tarvikeostoa");
}
elseif ($toim == "YLLAPITO") {
  $otsikko = t("ylläpitosopimusta");
}
elseif ($toim == "PROJEKTI") {
  $otsikko = t("tilauksia");
}
elseif ($toim == "VALMISTUSMYYNTI" or $toim == "VALMISTUSMYYNTISUPER") {
  $otsikko = t("tilauksia ja valmistuksia");
}
elseif ($toim == "JTTOIMITA") {
  $otsikko = t("JT-tilausta");
}
elseif ($toim == "HYPER") {
  $otsikko = t("tilauksia");
}
elseif ($toim == 'TOSI_KESKEN') {
  $otsikko = t("keskeneräisiä myyntitilauksia");
}
elseif ($toim == 'KESKEN_TAI_TOIMITETTAVISSA') {
  $otsikko = t("Keskeneräiset ja toimitettavat tilaukset");
}
elseif ($toim == 'ODOTTAA_SUORITUSTA') {
  $otsikko = t("suoritusta odottavia tilauksia");
}
elseif ($toim == 'TEHDASPALAUTUKSET' or $toim == 'SUPERTEHDASPALAUTUKSET') {
  $otsikko = t("tehdaspalautuksia");
}
elseif ($toim == 'TAKUU' or $toim == 'TAKUUSUPER') {
  $otsikko = t("takuita");
}
else {
  $otsikko = t("myyntitilausta");
  $toim = "";
}

//onko pikatilaus ja rivisyöttä napit disabloitu
$button_disabled = "";

if (($row["tila"] == "L" or $row["tila"] == "N") and isset($row["mapvm"]) and $row["mapvm"] != '0000-00-00' and $row["mapvm"] != '') {
  $button_disabled = "disabled";
}

if (empty($oikeurow['paivitys'])) {
  $button_disabled = "disabled";
}

if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {

  if ($toim == "VASTAANOTA_REKLAMAATIO") {
    $otsikkoteksti = t("Vastaanota");
  }
  else {
    $otsikkoteksti = t("Muokkaa");
  }

  echo "<font class='head'>".$otsikkoteksti." ".$otsikko."<hr></font>";

  // Tehdään popup käyttäjän lepäämässä olevista tilauksista
  if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER" or $toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER") {
    $query = "SELECT *
              FROM lasku use index (tila_index)
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'G'";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
    $query = "SELECT *
              FROM lasku use index (tila_index)
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'S'";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='A' and alatila = '' and tilaustyyppi='A'";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "VASTAANOTA_REKLAMAATIO") {
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]') and tila = 'C' and alatila in ('A','B') and tilaustyyppi='R'";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "REKLAMAATIO" or $toim == "REKLAMAATIOSUPER" or $toim == "TAKUU" or $toim == "TAKUUSUPER") {
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila = 'C' and alatila = '' and tilaustyyppi = 'R'";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='T' and alatila in ('','A') and tilaustyyppi='T' AND clearing != 'EXTTARJOUS'";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "EXTTARJOUS") {
    $query = "SELECT lasku.*
              FROM lasku
              JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
              WHERE lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]')  and lasku.tila='T' and lasku.alatila in ('','A') and lasku.tilaustyyppi='T' AND lasku.clearing = 'EXTTARJOUS' and laskun_lisatiedot.sopimus_lisatietoja != ''";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "EXTENNAKKO") {
    $query = "SELECT lasku.*
              FROM lasku
              JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
              WHERE lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]')  and lasku.tila in ('E', 'N') and lasku.alatila in ('','A','J') AND
              lasku.clearing    = 'EXTENNAKKO' AND laskun_lisatiedot.sopimus_lisatietoja != ''";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "OSTO") {
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and tilaustyyppi != 'O' and alatila = ''";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "OSTOSUPER") {
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and tilaustyyppi != 'O' and alatila in ('A','')";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "HAAMU") {
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and tilaustyyppi = 'O' and alatila = ''";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "ENNAKKO") {
    $query = "SELECT lasku.*
              FROM lasku use index (tila_index)
              LEFT JOIN tilausrivi use index (yhtio_otunnus) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'E')
              WHERE lasku.yhtio       = '$kukarow[yhtio]'
              and (lasku.laatija = '$kukarow[kuka]' or lasku.tunnus = '$kukarow[kesken]')
              and lasku.tila          in ('E', 'N')
              and lasku.alatila       in ('','A','J')
              and lasku.tilaustyyppi  = 'E'
              and lasku.clearing     != 'EXTENNAKKO'
              GROUP BY lasku.tunnus";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
    $query = "SELECT *
              FROM lasku use index (tila_index)
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'V'";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "" or $toim == "SUPER" or $toim == "PROJEKTI" or $toim == "KESKEN") {
    $query = "SELECT *
              FROM lasku use index (tila_index)
              WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]') and alatila='' and tila in ('N','E') and clearing not in ('EXTENNAKKO','EXTTARJOUS')";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "LASKUTUSKIELTO") {
    $query = "SELECT lasku.*
              FROM lasku use index (tila_index)
              JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus and lasku.chn = '999'
              WHERE lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]') and tila in ('N','L') and alatila != 'X'";
    $eresult = pupe_query($query);
  }
  elseif ($toim == "YLLAPITO") {
    $query = "(SELECT lasku.*
               FROM lasku use index (tila_index)
               WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
               AND (lasku.laatija = '{$kukarow["kuka"]}' or lasku.tunnus = '{$kukarow["kesken"]}')
               AND tila          = '0'
               AND alatila       not in ('V','D'))

               UNION

               (SELECT lasku.*
               FROM lasku use index (tila_index)
               WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
               AND (lasku.laatija = '{$kukarow["kuka"]}' or lasku.tunnus = '{$kukarow["kesken"]}')
               AND tila          = 'N'
               AND alatila       = ''
               AND tilaustyyppi  = 0)

               ORDER BY tunnus DESC";
    $eresult = pupe_query($query);
  }

  if ($toim != "MYYNTITILITOIMITA" and $toim != "EXTRANET" and $toim != "VALMISTUSMYYNTI" and $toim != "VALMISTUSMYYNTISUPER") {
    if (isset($eresult) and  mysql_num_rows($eresult) > 0) {
      // tehdään aktivoi nappi.. kaikki mitä näytetään saa aktvoida, joten tarkkana queryn kanssa.
      if ($toim == "" or $toim == "SUPER" or $toim == "SUPER_EITYOM" or $toim == "SUPER_EILUONTITAPATYOM" or $toim == "LASKUTUSKIELTO" or $toim == "KESKEN" or $toim == "TOSI_KESKEN" or $toim == 'KESKEN_TAI_TOIMITETTAVISSA' or $toim == "LAVAKERAYS") {

        if (isset($pika_oikeu) and $pika_oikeu and !$rivi_oikeu) {
          $aputoim1 = "PIKATILAUS";
          $aputoim2 = "";

          $lisa1 = t("Pikatilaukseen");
          $lisa2 = "";
        }
        elseif (isset($pika_oikeu) and !$pika_oikeu and $rivi_oikeu) {
          $aputoim1 = "RIVISYOTTO";
          $aputoim2 = "";

          $lisa1 = t("Rivisyöttöön");
          $lisa2 = "";
        }
        else {
          $aputoim1 = "RIVISYOTTO";
          $aputoim2 = "PIKATILAUS";

          $lisa1 = t("Rivisyöttöön");
          $lisa2 = t("Pikatilaukseen");
        }
      }
      elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
        $aputoim1 = "VALMISTAASIAKKAALLE";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "MYYNTITILISUPER") {
        $aputoim1 = "MYYNTITILI";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "SIIRTOLISTASUPER") {
        $aputoim1 = "SIIRTOLISTA";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "TARJOUSSUPER") {
        $aputoim1 = "TARJOUS";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "EXTTARJOUS") {
        $aputoim1 = "EXTTARJOUS";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "EXTENNAKKO") {
        $aputoim1 = "EXTENNAKKO";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "ENNAKKO") {
        $aputoim1 = "ENNAKKO";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "TYOMAARAYSSUPER") {
        $aputoim1 = "TYOMAARAYS";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
        $aputoim1 = "";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "VASTAANOTA_REKLAMAATIO") {
        $aputoim1 = "REKLAMAATIO";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "HAAMU") {
        $aputoim1 = "HAAMU";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      else {
        $aputoim1 = $toim;
        $aputoim2 = "";

        $lisa1 = t("Muokkaa");
        $lisa2 = "";
      }

      if ($toim == "OSTO" or $toim == "OSTOSUPER") {
        echo "<form method='post' action='tilauskasittely/tilaus_osto.php'>
            <input type='hidden' name='tee' value='AKTIVOI'>";
      }
      else {
        echo "<form method='post' action='tilauskasittely/tilaus_myynti.php'>";
      }

      $lopetus = "{$palvelin2}muokkaatilaus.php////toim={$toim}//asiakastiedot={$asiakastiedot}//viitetiedot={$viitetiedot}//limit={$limit}//etsi={$etsi}";

      if (isset($toimipaikka)) {
        $lopetus .= "//toimipaikka=$toimipaikka";
      }

      echo "<input type='hidden' name='toim' value='$aputoim1'>";
      echo "<input type='hidden' name='kaytiin_otsikolla' value='NOJOO!' />";
      echo "<input type='hidden' name='lopetus' value='$lopetus'>";

      echo "<br><table>
          <tr>
          <th>".t("Kesken olevat").":</th>
          <td><select name='tilausnumero'>";

      while ($row = mysql_fetch_assoc($eresult)) {
        $select = "";

        //valitaan keskenoleva oletukseksi..
        if ($row['tunnus'] == $kukarow["kesken"]) {
          $select="SELECTED";
        }
        echo "<option value='$row[tunnus]' $select>$row[tunnus]: $row[nimi] ($row[luontiaika])</option>";
      }

      echo "</select></td>";

      if ($aputoim2 != "" and ($toim == "" or $toim == "SUPER" or $toim == "SUPER_EITYOM" or $toim == "SUPER_EILUONTITAPATYOM" or $toim == "ENNAKKO" or $toim == "LASKUTUSKIELTO" or $toim == "KESKEN" or $toim == "TOSI_KESKEN" or $toim == 'KESKEN_TAI_TOIMITETTAVISSA' or $toim == "LAVAKERAYS")) {
        echo "<td class='back'><input type='submit' name='$aputoim2' value='$lisa2' $button_disabled></td>";
      }

      echo "<td class='back'><input type='submit' name='$aputoim1' value='$lisa1' $button_disabled></td>";
      echo "</tr></table></form>";
    }
    else {
      echo t("Sinulla ei ole aktiivisia eikä kesken olevia tilauksia").".<br>";
    }
  }
}

if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {

  // Näytetään muuten vaan sopivia tilauksia
  echo "<br><br>";
  echo "<form method='post' name='hakuformi'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='asiakastiedot' value='$asiakastiedot'>";
  echo "<input type='hidden' name='viitetiedot' value='$viitetiedot'>";
  echo "<input type='hidden' name='limit' value='$limit'>";
  echo "<font class='head'>".t("Etsi")." $otsikko<hr></font>";

  echo "<table>";
  echo "<tr>";
  echo "<th>";

  if ($toim == "YLLAPITO") {
    echo t("Syötä tilausnumeron, asiakkaan tilausnumeron, nimen, laatijan tai sopimuksen lisätiedon osa")."</th>";
  }
  elseif ($toim == "MYYNTITILITOIMITA") {
    echo t('Syötä tuotenumeron, tilausnumeron, nimen tai laatijan osa')."</th>";
  }
  elseif ($toim == "VALMISTUS") {
    $valmistuslinjat = hae_valmistuslinjat();

    echo t('Syötä tilausnumeron, nimen tai laatijan osa')."</th>";
    echo "<td>";
    echo "<input type='text' size='25' name='etsi'>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>".t('Tuotenumero')."</th>";
    echo "<td>";
    echo "<input type='text' size='25' name='tuoteno' value='{$tuoteno}' />";
    echo "</td>";

    if (!empty($valmistuslinjat)) {
      echo "</tr><tr>";
      echo "<th>".t('Valmistuslinja')."</th>";
      echo "<td>";
      echo "<select name='valmistuslinja'>";
      echo "<option value='' >".t('Ei valintaa')."</option>";
      foreach ($valmistuslinjat as $_valmistuslinja) {
        $sel = "";
        if ($_valmistuslinja['selite'] == $valmistuslinja) {
          $sel = "SELECTED";
        }
        echo "<option value='{$_valmistuslinja['selite']}' {$sel}>{$_valmistuslinja['selitetark']}</option>";
      }
      echo "</select>";
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>".t('Keräyspäivämäärä')." (pp-kk-vvvv)</th>";
      echo "<td>";
      echo "  <input type='text' name='pp' value='{$pp}' size='3'>
          <input type='text' name='kk' value='{$kk}' size='3'>
          <input type='text' name='vv' value='{$vv}' size='5'>";
      echo "</td>";
    }
  }
  else {

    $teksti = "Syötä tilausnumeron, nimen";

    if (in_array($toim, array('', 'SUPER', 'KESKEN', 'HYPER', 'TOSI_KESKEN', 'ODOTTAA_SUORITUSTA', 'TEHDASPALAUTUKSET', 'SUPERTEHDASPALAUTUKSET', 'TAKUU', 'TAKUUSUPER', 'TARJOUS', 'TARJOUSSUPER', 'OSTO', 'OSTOSUPER', 'ENNAKKO', 'SIIRTOLISTA', 'SIIRTOLISTASUPER', 'REKLAMAATIO', 'REKLAMAATIOSUPER', 'VASTAANOTA_REKLAMAATIO'))) {
      $teksti .= ", tuotenumeron";
    }

    if ($yhtiorow['myyntitilausrivi_rekisterinumero'] == 'K' and in_array($toim, array('', 'SUPER', 'KESKEN', 'HYPER', 'TOSI_KESKEN', 'TARJOUS', 'TARJOUSSUPER', 'REKLAMAATIO', 'REKLAMAATIOSUPER', 'VASTAANOTA_REKLAMAATIO'))) {
      $teksti .= ", rekisterinumeron";
    }

    $teksti .= " tai tilausviitteen osa";

    echo t($teksti), "</th>";
  }

  if ($toim != 'VALMISTUS') {
    echo "<td>";
    echo "<input type='text' size='25' name='etsi'>";
    echo "</td>";
  }

  if ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikkares = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikkares) > 0) {

    echo "</tr><tr>";

    echo "<th>", t("Toimipaikka"), "</th>";

    echo "<td><select name='toimipaikka' onchange='submit();'>";
    echo "<option value='kaikki'>", t("Kaikki toimipaikat"), "</option>";

    $sel = "";
    $toimipaikka_requestista = (isset($toimipaikka) and $toimipaikka != "kaikki" and $toimipaikka == 0);
    $toimipaikka_kayttajalta = (!isset($toimipaikka) and $kukarow['toimipaikka'] == 0);
    if ($toimipaikka_requestista or $toimipaikka_kayttajalta) {
      $sel = "selected";
      $toimipaikka = 0;
    }

    echo "<option value='0' {$sel}>".t('Ei toimipaikkaa')."</option>";

    $sel = "";

    while ($toimipaikkarow = mysql_fetch_assoc($toimipaikkares)) {
      if (!isset($toimipaikka) and $kukarow['toimipaikka'] == $toimipaikkarow['tunnus']) {
        $sel = ' selected';
        $toimipaikka = $kukarow['toimipaikka'];
      }
      elseif (isset($toimipaikka) and $toimipaikka == $toimipaikkarow['tunnus']) {
        $sel = ' selected';
      }
      else {
        $sel = '';
      }

      echo "<option value='{$toimipaikkarow['tunnus']}'{$sel}>{$toimipaikkarow['nimi']}</option>";
    }

    echo "</select></td><td class='back'>&nbsp;</td>";
  }

  echo "</tr>";
  echo "<tr>";

  $_alkiot = array(
    '0' => '',
    '1' => '',
    '2' => '',
    '3' => '',
    '5' => '',
    '7' => '',
    '14' => '',
    '30' => '',
    '60' => '',
    '90' => '',
  );

  $_sel = array($pv_rajaus => 'selected') + $_alkiot;

  echo "<th>";
  echo t("Aikarajaus");
  echo "</th>";
  echo "<td>";
  echo "<select name='pv_rajaus'>";
  echo "<option value='0' {$_sel['0']}>", t("Ei rajausta"), "</option>";
  echo "<option value='1' {$_sel['1']}>", t("%d päivää", "", 1), "</option>";
  echo "<option value='2' {$_sel['2']}>", t("%d päivää", "", 2), "</option>";
  echo "<option value='3' {$_sel['3']}>", t("%d päivää", "", 3), "</option>";
  echo "<option value='5' {$_sel['5']}>", t("%d päivää", "", 5), "</option>";
  echo "<option value='7' {$_sel['7']}>", t("%d päivää", "", 7), "</option>";
  echo "<option value='14' {$_sel['14']}>", t("%d päivää", "", 14), "</option>";
  echo "<option value='30' {$_sel['30']}>", t("%d päivää", "", 30), "</option>";
  echo "<option value='60' {$_sel['60']}>", t("%d päivää", "", 60), "</option>";
  echo "<option value='90' {$_sel['90']}>", t("%d päivää", "", 90), "</option>";
  echo "</select>";
  echo "</td>";

  echo "</tr>";

  if ($toim == "LAVAKERAYS") {

    echo "</tr><tr>";

    echo "<th>", t("Toimitustapa"), "</th>";

    echo "<td><select name='toimitustapa' onchange='submit();'>";
    echo "<option value='kaikki'>", t("Kaikki toimitustavat"), "</option>";

    $toimitustavat = hae_kaikki_toimitustavat();

    foreach ($toimitustavat as $ttapa) {
      if (stripos($ttapa['selite'], "HORN") === FALSE) {
        continue;
      }

      $sel = '';

      if (isset($toimitustapa) and $toimitustapa == $ttapa['selite']) {
        $sel = ' selected';
      }

      echo "<option value='{$ttapa['selite']}'{$sel}>{$ttapa['selite']}</option>";
    }

    echo "</select></td><td class='back'>&nbsp;</td>";
    echo "</tr>";
  }

  echo "<tr>";

  echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Etsi")."'></td></tr>";
  echo "</table>";
  echo "</form>";
  echo "<br>";

  // kursorinohjausta
  $formi  = "hakuformi";
  $kentta = "etsi";

  // pvm 30 pv taaksepäin
  $dd = date("d", mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
  $mm = date("m", mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
  $yy = date("Y", mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

  $haku = "";
  $myyntitili_haku = "";
  $etsi = mysql_real_escape_string($etsi);

  if ($toim == "MYYNTITILITOIMITA") {
    $myyntitili_haku = " or tilausrivi.tuoteno like '%{$etsi}%' ";
  }

  if (!empty($etsi) and is_string($etsi) and $toim == "LAVAKERAYS") {
    $haku = " and (lasku.nimi like '%{$etsi}%' or lasku.nimitark like '%{$etsi}%' or lasku.viesti like '%{$etsi}%' or lasku.toim_nimi like '%{$etsi}%' or lasku.toim_nimitark like '%{$etsi}%') ";
  }
  else if (!empty($etsi) and is_string($etsi)) {
    $haku = " and (lasku.nimi like '%{$etsi}%' or lasku.nimitark like '%{$etsi}%' or lasku.viesti like '%{$etsi}%' or lasku.toim_nimi like '%{$etsi}%' or lasku.toim_nimitark like '%{$etsi}%' or lasku.laatija like '%{$etsi}%' or kuka1.nimi like '%{$etsi}%' or kuka2.nimi like '%{$etsi}%' {$myyntitili_haku}) ";
  }

  if (is_numeric($etsi)) {
    $haku = " and (lasku.tunnus like '{$etsi}%' or lasku.ytunnus like '{$etsi}%' or lasku.viesti like '%{$etsi}%' {$myyntitili_haku}) ";
  }

  if ($toim == "LAVAKERAYS" and !empty($toimitustapa)) {
    $haku .= " and lasku.toimitustapa = '{$toimitustapa}'";
  }

  if ($toim == 'YLLAPITO' and $etsi != "" and $haku != "") {
    $haku = substr($haku, 0, -2); // Poistetaan vika sulku $hausta

    $laitelisa = '';

    if ($yhtiorow['laiterekisteri_kaytossa'] != '') {
      $laitelisa = " or laite.sarjanro like '%{$etsi}%'
                     or tilausrivin_lisatiedot.sopimuksen_lisatieto1 like '%{$etsi}%'
                     or tilausrivin_lisatiedot.sopimuksen_lisatieto2 like '%{$etsi}%'";
    }

    $haku .= " or lasku.asiakkaan_tilausnumero like '%{$etsi}%'
               $laitelisa) ";
  }

  // Myyntitilauksia voidaan etsiä myös asiakkaan tilausnumerolla
  if ($etsi != "" and $haku != "" and ($toim == '' or $toim == 'SUPER' or $toim == 'KESKEN' or $toim == 'HYPER' or $toim == 'TOSI_KESKEN' or $toim == 'ODOTTAA_SUORITUSTA' or $toim == 'KESKEN_TAI_TOIMITETTAVISSA' or $toim == "TEHDASPALAUTUKSET" or $toim == "SUPERTEHDASPALAUTUKSET" or $toim == "TAKUU" or $toim == "TAKUUSUPER")) {
    $haku = substr($haku, 0, -2); // Poistetaan vika sulku $hausta
    $haku .= " or (lasku.asiakkaan_tilausnumero like '%{$etsi}%' and lasku.asiakkaan_tilausnumero != '')) ";
  }

  if ($yhtiorow['myyntitilausrivi_rekisterinumero'] == 'K' and in_array($toim, array('', 'SUPER', 'KESKEN', 'HYPER', 'TOSI_KESKEN', 'TARJOUS', 'TARJOUSSUPER', 'REKLAMAATIO', 'REKLAMAATIOSUPER', 'VASTAANOTA_REKLAMAATIO')) and $etsi != "" and $haku != "") {
    $tilausrivin_lisatiedot_join = "LEFT JOIN tilausrivin_lisatiedot on (tilausrivin_lisatiedot.yhtio = lasku.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)";

    $haku = substr($haku, 0, -2); // Poistetaan vika sulku $hausta
    $haku .= " or tilausrivin_lisatiedot.rekisterinumero like '%{$etsi}%') ";
  }
  else {
    $tilausrivin_lisatiedot_join = "";
  }

  if ($etsi != "" and $haku != "" and in_array($toim, array('', 'SUPER', 'KESKEN', 'HYPER', 'TOSI_KESKEN', 'ODOTTAA_SUORITUSTA', 'TEHDASPALAUTUKSET', 'SUPERTEHDASPALAUTUKSET', 'TAKUU', 'TAKUUSUPER', 'TARJOUS', 'TARJOUSSUPER', 'OSTO', 'OSTOSUPER', 'ENNAKKO', 'SIIRTOLISTA', 'SIIRTOLISTASUPER', 'REKLAMAATIO', 'REKLAMAATIOSUPER', 'VASTAANOTA_REKLAMAATIO'))) {
    $haku = substr($haku, 0, -2); // Poistetaan vika sulku $hausta
    $haku .= " or tilausrivi.tuoteno like '%{$etsi}%') ";
  }

  $sumhaku = '';

  if (isset($toimipaikka) and "{$toimipaikka}" != "kaikki") {

    $toimipaikka = (int) $toimipaikka;

    if ($toim == 'OSTO' or $toim == 'OSTOSUPER') {
      $haku .= " and lasku.vanhatunnus = '{$toimipaikka}' ";
    }
    else {
      $haku .= " and lasku.yhtio_toimipaikka = '{$toimipaikka}' ";
      $sumhaku = " and lasku.yhtio_toimipaikka = '{$toimipaikka}' ";
    }
  }

  if (!empty($pv_rajaus) and empty($etsi)) {
    $haku .= " and DATE(lasku.luontiaika) > DATE_SUB(CURRENT_DATE, INTERVAL {$pv_rajaus} DAY) ";
  }

  if (!empty($mt_order)) {
    $hakusarake = mysql_real_escape_string(key($mt_order));
    $hakusuunta = mysql_real_escape_string($mt_order[$hakusarake]);

    if ($hakusarake == "asiakas") {
      $hakusarake = "lasku.nimi";
    }

    $mt_order_by = "ORDER BY $hakusarake $hakusuunta";
  }
  else {

    if ($toim == 'EXTRANET') {
      $mt_order_by = "ORDER BY lasku.luontiaika ASC";
    }
    else {
      $mt_order_by = "ORDER BY lasku.luontiaika DESC";
    }

    if ($toim == "OSTO" or $toim == "OSTOSUPER") {
      $mt_order_by = "ORDER BY kuka_ext, lasku.luontiaika DESC";
    }
    elseif ($toim == "PROJEKTI") {
      $mt_order_by = "ORDER BY lasku.tunnusnippu DESC, tunnus ASC";
    }
  }

  $seuranta = "";
  $seurantalisa = "";

  $kohde = "";
  $kohdelisa = "";

  $toimaikalisa = "";

  if ($toim != "SIIRTOLISTA" and $toim != "SIIRTOLISTASUPER" and $toim != "MYYNTITILI" and $toim != "MYYNTITILISUPER" and $toim != "EXTRANET" and ($toim != 'TARJOUS' and $toim != 'EXTTARJOUS')) {
    $toimaikalisa = ' lasku.toimaika, ';
  }

  if ($limit == "" and $toim != "LAVAKERAYS") {
    $rajaus = "LIMIT 50";
  }
  else {
    $rajaus  = "";
  }
}

if ($toim == "LAVAKERAYS") {
  $asiakastiedot = "toimitus";
}
elseif (empty($asiakastiedot)) {
  $asiakastiedot = isset($_COOKIE["pupesoft_muokkaatilaus"]) ? $_COOKIE["pupesoft_muokkaatilaus"] : "";
}


if ($asiakastiedot == "toimitus") {
  $asiakasstring = "  concat_ws('<br>', lasku.ytunnus, concat_ws(' ', lasku.nimi, lasku.nimitark),
                      concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark, lasku.toim_postitp))";
  $assel1 = "";
  $assel2 = "CHECKED";
}
else {
  $asiakasstring = " concat_ws('<br>', lasku.ytunnus, lasku.nimi, lasku.nimitark) ";
  $assel1 = "CHECKED";
  $assel2 = "";
}

if (empty($naytetaanko_saldot)) {
  $naytetaanko_saldot = isset($_COOKIE["naytetaanko_saldot"]) ? $_COOKIE["naytetaanko_saldot"] : "";
}

if (empty($viitetiedot)) {
  $viitetiedot= isset($_COOKIE["viitetiedot"]) ? $_COOKIE["viitetiedot"] : "";
}

echo "  <script language=javascript>
    function lahetys_verify(pitaako_varmistaa) {
      msg = pitaako_varmistaa;

      if (confirm(msg)) {
        return true;
      }
      else {
        skippaa_tama_submitti = true;
        return false;
      }
    }
  </script>";

echo "<br>";

if ($toim != "LAVAKERAYS") {
  // Näytetään asiakastiedot linkki
  if ($asiakastiedot == 'toimitus') {
    echo " <a href='muokkaatilaus.php?toim={$toim}&asiakastiedot=laskutus&limit={$limit}&etsi={$etsi}&toimipaikka={$toimipaikka}'>" .t("Näytä vain laskutustiedot") . "</a>";
  }
  else {
    echo " <a href='muokkaatilaus.php?toim={$toim}&asiakastiedot=toimitus&limit={$limit}&etsi={$etsi}&toimipaikka={$toimipaikka}'>" . t("Näytä myös toimitusasiakkaan tiedot") . "</a>";
  }

  // Näytetäänkö saldot linkki
  if ($toim == '' and $naytetaanko_saldot == 'kylla') {
    echo " <a href='muokkaatilaus.php?toim={$toim}&limit={$limit}&etsi={$etsi}&naytetaanko_saldot=ei&toimipaikka={$toimipaikka}'>" . t("Piilota saldot keräypäivänä") . "</a>";
  }
  elseif (!empty($yhtiorow["saldo_kasittely"]) and $toim == '') {
    echo " <a href='muokkaatilaus.php?toim={$toim}&limit={$limit}&etsi={$etsi}&naytetaanko_saldot=kylla&toimipaikka={$toimipaikka}'>" .t("Näytä saldot keräyspäivänä") . "</a>";
  }

  // Näytetään viite ja asiakkaan tilausnumero "avoimet tilaukset" ja "muokkaa" näkymässä
  if ($toim == "") {
    $_tilviite_text = "tilausviite ja";
  }
  else {
    $_tilviite_text = "";
  }
  if (in_array($toim, array("", "SUPER")) and $viitetiedot == 'kylla') {
    echo " <br><a href='muokkaatilaus.php?toim={$toim}&viitetiedot=ei&limit={$limit}&etsi={$etsi}&toimipaikka={$toimipaikka}'>" .t("Piilota $_tilviite_text asiakkaan tilausnumero") . "</a>";
  }
  elseif (in_array($toim, array("", "SUPER"))) {
    echo " <br><a href='muokkaatilaus.php?toim={$toim}&viitetiedot=kylla&limit={$limit}&etsi={$etsi}&toimipaikka={$toimipaikka}'>" . t("Näytä myös $_tilviite_text asiakkaan tilausnumero") . "</a>";
  }

  echo "<br><br>";
}

$query_ale_lisa = generoi_alekentta('M');

if (strpos($toim, "TEHDASPALAUTUKSET") !== FALSE) {
  $tepalisa = " AND tilaustyyppi = '9' ";
}
elseif (strpos($toim, "TAKUU") !== FALSE) {
  $tepalisa = " AND tilaustyyppi = 'U' ";
}
else {
  $tepalisa = " AND tilaustyyppi != '9' ";
}

// Etsitään muutettavaa tilausta
if ($toim == 'HYPER') {

  $query = "  SELECT DISTINCT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, ";

  if ($kukarow['hinnat'] == 0) {
    $query .= " round(sum(tilausrivi.hinta
                  / if('$yhtiorow[alv_kasittely]'  = '' AND tilausrivi.alv < 500,
                    (1 + tilausrivi.alv / 100),
                    1)
                  * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                  * {$query_ale_lisa}), 2) AS arvo,
                round(sum(tilausrivi.hinta
                  * if('$yhtiorow[alv_kasittely]' != '' AND tilausrivi.alv < 500,
                    (1 + tilausrivi.alv / 100),
                    1)
                  * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                  * {$query_ale_lisa}), 2) AS summa, ";
  }

  $query .= "  $toimaikalisa alatila, tila, lasku.tunnus, lasku.mapvm, lasku.tilaustyyppi, lasku.varasto
        FROM lasku use index (tila_index)
        LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
        LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
        LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
        {$tilausrivin_lisatiedot_join}
        WHERE lasku.yhtio = '$kukarow[yhtio]' and
        (((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
        or (lasku.tila = '0' and lasku.alatila NOT in ('D'))
        or (lasku.tila = 'N' and lasku.alatila = 'F')
        or (lasku.tila = 'V' and lasku.alatila in ('','A','B','C','J'))
        or (lasku.tila = 'V' and lasku.alatila in ('','A','B','J'))
        or (lasku.tila = 'T' and lasku.tilaustyyppi = 'T' and lasku.alatila in ('','A'))
        or (lasku.tila = 'T' and lasku.tilaustyyppi = 'T' and lasku.alatila in ('','A','X'))
        or (lasku.tila in ('A','L','N') and lasku.tilaustyyppi = 'A' and lasku.alatila != 'X')
        or (lasku.tila in ('L','N') and lasku.alatila != 'X')
        or (lasku.tila in ('L','N') and lasku.alatila in ('A',''))
        or (lasku.tila in ('L','N','C') and tilaustyyppi = 'R' and alatila in ('','A','B','C','J','D'))
        or (lasku.tila in ('N','L') and lasku.alatila != 'X' and lasku.chn = '999')
        or (lasku.tila in ('R','L','N','A') and alatila NOT in ('X') and lasku.tilaustyyppi != '9')
        or (lasku.tila = 'E' and tilausrivi.tyyppi = 'E')
        or (lasku.tila = 'G' and lasku.alatila in ('','A','B','C','D','J','T'))
        or (lasku.tila = 'G' and lasku.alatila in ('','A','J'))
        or (lasku.tila = 'G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J'))
        or (lasku.tila = 'G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J'))
        or (lasku.tila = 'N' and lasku.alatila = 'U')
        or (lasku.tila = 'S' and lasku.alatila in ('','A','B','J','C'))
        or (lasku.tila in ('L','N','V') and lasku.alatila NOT in ('X','V'))
        or (lasku.tila = 'G' and lasku.tilaustyyppi = 'M' and lasku.alatila = 'V'))
        $haku
        GROUP BY lasku.tunnus
        $mt_order_by
        $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(if(lasku.alatila = 'X', 0, tilausrivi.hinta
                   / if('$yhtiorow[alv_kasittely]'  = '' AND tilausrivi.alv < 500,
                    (1 + tilausrivi.alv / 100),
                    1)
                   * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                   * {$query_ale_lisa})), 2) AS arvo,
                 round(sum(if(lasku.alatila = 'X', 0, tilausrivi.hinta
                   * if('$yhtiorow[alv_kasittely]' != '' AND tilausrivi.alv < 500,
                    (1 + tilausrivi.alv / 100),
                    1)
                   * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                   * {$query_ale_lisa})), 2) AS summa,
                 round(sum(if(lasku.alatila != 'X', 0, tilausrivi.hinta
                   / if('$yhtiorow[alv_kasittely]'  = '' AND tilausrivi.alv < 500,
                    (1 + tilausrivi.alv / 100),
                    1)
                   * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                   * {$query_ale_lisa})), 2) AS jt_arvo,
                 round(sum(if(lasku.alatila != 'X', 0, tilausrivi.hinta
                   * if('$yhtiorow[alv_kasittely]' != '' AND tilausrivi.alv < 500,
                    (1 + tilausrivi.alv / 100),
                    1)
                   * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                   * {$query_ale_lisa})), 2) AS jt_summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio
                 AND tilausrivi.otunnus  = lasku.tunnus
                 AND tilausrivi.tyyppi  != 'D')
                 WHERE lasku.yhtio       = '$kukarow[yhtio]'
                 AND lasku.tila          IN ('L', 'N')
                 AND lasku.alatila      != 'X'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 6;
}
elseif ($toim == 'SUPER' or $toim == 'SUPERTEHDASPALAUTUKSET' or $toim == "SUPER_EITYOM" or $toim == "SUPER_EILUONTITAPATYOM") {

  $_ei_ollenkaan_tyomaarayksia_arraylisa = "";

  if ($toim == "SUPER_EITYOM") {
    $_ei_ollenkaan_tyomaarayksia_arraylisa = "AND lasku.tilaustyyppi != 'A'";
  }

  $_ei_tyomaarays_tyomaarayksia_arraylisa = "";

  if ($toim == "SUPER_EILUONTITAPATYOM") {
    $_ei_tyomaarays_tyomaarayksia_arraylisa = "AND laskun_lisatiedot.luontitapa != 'tyomaarays'";
  }

  $_querylisa = "";
  if ($toim == 'SUPER' and $viitetiedot == 'kylla') {
    $_querylisa = " lasku.asiakkaan_tilausnumero astilno,";
  }

  $query = "  SELECT DISTINCT lasku.tunnus tilaus,
              $asiakasstring asiakas,
              lasku.luontiaika,
              if(kuka1.kuka is null,
                lasku.laatija,
                if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)
              ) laatija,
              $_querylisa
              lasku.viesti tilausviite,";

  if ($kukarow['hinnat'] == 0) {
    $query .= " round(sum(tilausrivi.hinta
                  / if('$yhtiorow[alv_kasittely]'  = '' AND tilausrivi.alv < 500,
                    (1 + tilausrivi.alv / 100),
                    1)
                  * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                  * {$query_ale_lisa}), 2) AS arvo,
                round(sum(tilausrivi.hinta
                  * if('$yhtiorow[alv_kasittely]' != '' AND tilausrivi.alv < 500,
                    (1 + tilausrivi.alv / 100),
                    1)
                  * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                  * {$query_ale_lisa}), 2) AS summa, ";
  }

  $query .= "  $toimaikalisa alatila, tila, lasku.tunnus, lasku.mapvm, lasku.tilaustyyppi, lasku.label, lasku.varasto
        FROM lasku use index (tila_index)
        LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
        LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
        LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
        LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus)
        {$tilausrivin_lisatiedot_join}
        WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L', 'N') and lasku.alatila != 'X' and lasku.clearing != 'EXTENNAKKO'
        {$_ei_ollenkaan_tyomaarayksia_arraylisa}
        {$_ei_tyomaarays_tyomaarayksia_arraylisa}
        $haku
        $tepalisa
        GROUP BY lasku.tunnus
        $mt_order_by
        $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(if(lasku.alatila = 'X', 0, tilausrivi.hinta
                   / if('$yhtiorow[alv_kasittely]'  = '' AND tilausrivi.alv < 500,
                     (1 + tilausrivi.alv / 100),
                     1)
                   * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                   * {$query_ale_lisa})), 2) AS arvo,
                 round(sum(if(lasku.alatila = 'X', 0, tilausrivi.hinta
                   * if('$yhtiorow[alv_kasittely]' != '' AND tilausrivi.alv < 500,
                     (1 + tilausrivi.alv / 100),
                     1)
                   * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                   * {$query_ale_lisa})), 2) AS summa,
                 round(sum(if(lasku.alatila != 'X', 0, tilausrivi.hinta
                   / if('$yhtiorow[alv_kasittely]'  = '' AND tilausrivi.alv < 500,
                     (1 + tilausrivi.alv / 100),
                     1)
                   * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                   * {$query_ale_lisa})), 2) AS jt_arvo,
                 round(sum(if(lasku.alatila != 'X', 0, tilausrivi.hinta
                   * if('$yhtiorow[alv_kasittely]' != '' AND tilausrivi.alv < 500,
                     (1 + tilausrivi.alv / 100),
                     1)
                   * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                   * {$query_ale_lisa})), 2) AS jt_summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio
                   AND tilausrivi.otunnus  = lasku.tunnus
                   AND tilausrivi.tyyppi  != 'D')
                 LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio
                   AND laskun_lisatiedot.otunnus = lasku.tunnus)
                 WHERE lasku.yhtio         = '{$kukarow['yhtio']}'
                 AND lasku.tila            IN ('L', 'N')
                 AND lasku.alatila        != 'X'
                 AND lasku.clearing       != 'EXTENNAKKO'
                 {$_ei_ollenkaan_tyomaarayksia_arraylisa}
                 {$_ei_tyomaarays_tyomaarayksia_arraylisa}
                 {$sumhaku}";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 7;
}
elseif ($toim == 'ENNAKKO') {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, viesti tilausviite, $toimaikalisa alatila, tila, lasku.tunnus, tilausrivi.tyyppi trivityyppi, lasku.tilaustyyppi, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN tilausrivi use index (yhtio_otunnus) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'E')
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio       = '$kukarow[yhtio]'
            and lasku.tila          in ('E','N')
            and lasku.tilaustyyppi  = 'E'
            and lasku.clearing     != 'EXTENNAKKO'
            $haku
            GROUP BY lasku.tunnus
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi = 'E')
                 WHERE lasku.yhtio   = '$kukarow[yhtio]' and lasku.tila = 'E'
                 and lasku.clearing != 'EXTENNAKKO'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 6;
}
elseif ($toim == 'EXTENNAKKO') {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, viesti tilausviite, $toimaikalisa alatila, tila, lasku.tunnus, tilausrivi.tyyppi trivityyppi, lasku.tilaustyyppi, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN tilausrivi use index (yhtio_otunnus) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi = 'E')
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio       = '$kukarow[yhtio]'
            and lasku.tila          in ('E','N')
            and lasku.tilaustyyppi  = 'E'
            and lasku.clearing      = 'EXTENNAKKO'
            and lasku.ytunnus      != ''
            $haku
            GROUP BY lasku.tunnus
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi = 'E')
                 WHERE lasku.yhtio  = '$kukarow[yhtio]' and lasku.tila IN ('E', 'N')
                 and lasku.clearing = 'EXTENNAKKO'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 6;
}
elseif ($toim == "SIIRTOLISTA") {
  $query = "SELECT DISTINCT lasku.tunnus tilaus, $asiakasstring varasto, lasku.luontiaika, lasku.toimaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            LEFT JOIN tilausrivi use index (yhtio_otunnus) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi != 'D')
            WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.alatila in ('','A','J')
            $haku
            $mt_order_by
            $rajaus";
  $miinus = 4;
}
elseif ($toim == "SIIRTOLISTASUPER") {
  $query = "SELECT DISTINCT lasku.tunnus tilaus, $asiakasstring varasto, lasku.luontiaika, lasku.toimaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            LEFT JOIN tilausrivi use index (yhtio_otunnus) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and tilausrivi.tyyppi != 'D')
            WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.alatila in ('','A','B','C','D','J','T')
            $haku
            $mt_order_by
            $rajaus";
  $miinus = 4;
}
elseif ($toim == "MYYNTITILI") {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J')
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J')";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }
  $miinus = 5;
}
elseif ($toim == "MYYNTITILISUPER") {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J')
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J')";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 5;
}
elseif ($toim == "MYYNTITILITOIMITA") {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D' and tilausrivi.kpl != 0)
            WHERE lasku.yhtio      = '$kukarow[yhtio]'
            and lasku.tila         = 'G'
            and lasku.tilaustyyppi = 'M'
            and lasku.alatila      = 'V'
            $haku
            GROUP BY lasku.tunnus
            $mt_order_by
            $rajaus";
  $miinus = 5;
}
elseif ($toim == "JTTOIMITA") {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio  = '$kukarow[yhtio]'
            and lasku.tila     = 'N'
            and lasku.alatila  in ('U','T')
            and lasku.clearing not in ('EXTENNAKKO','EXTTARJOUS')
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='N' and lasku.alatila='U' and lasku.clearing not in ('EXTENNAKKO','EXTTARJOUS')";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 4;
}
elseif ($toim == 'VALMISTUS') {
  $tilausrivi_join = "";
  $tilausrivi_join_ehto = "";
  if ($tuoteno != '') {
    $tilausrivi_join = "  JOIN tilausrivi
                ON ( tilausrivi.yhtio = lasku.yhtio
                  AND tilausrivi.otunnus = lasku.tunnus
                  AND tilausrivi.tuoteno = '{$tuoteno}')";
    $tilausrivi_join_ehto = "  AND tilausrivi.tuoteno = '{$tuoteno}'";
  }

  $valmistuslinja_where = "";
  if ($valmistuslinja != '') {
    $valmistuslinja_where = "  AND lasku.kohde = '{$valmistuslinja}'";
  }

  $kerayspaiva_where = "";
  if (!empty($pp) and !empty($kk) and !empty($vv)) {
    $paiva = "{$vv}-{$kk}-{$pp}";
    $valid = FormValidator::validateContent($paiva, 'paiva');

    if ($valid) {
      $kerayspaiva_where = "  AND lasku.kerayspvm = '{$paiva}'";
    }
  }

  $query = "SELECT lasku.tunnus tilaus, lasku.nimi varastoon, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            {$tilausrivi_join}
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tila    = 'V'
            and lasku.alatila in ('','A','B','J')
            {$valmistuslinja_where}
            {$kerayspaiva_where}
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi IN ('L','W') {$tilausrivi_join_ehto})
                 WHERE lasku.yhtio       = '$kukarow[yhtio]'
                 {$valmistuslinja_where}
                 {$kerayspaiva_where}
                 and lasku.tila          = 'V'
                 and lasku.alatila       in ('','A','B','J')
                 and lasku.tilaustyyppi != 'W'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 5;
}
elseif ($toim == "VALMISTUSSUPER") {
  $query = "SELECT lasku.tunnus tilaus, lasku.nimi varastoon, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tila    = 'V'
            and lasku.alatila in ('','A','B','C','J')
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
                 WHERE lasku.yhtio       = '$kukarow[yhtio]'
                 and lasku.tila          = 'V'
                 and lasku.alatila       in ('','A','B','C','J')
                 and lasku.tilaustyyppi != 'W'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 5;
}
elseif ($toim == "VALMISTUSMYYNTI") {
  $query = "SELECT lasku.tunnus tilaus, $seuranta $asiakasstring asiakas, $kohde lasku.viesti tilausviite, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka.extranet extra, lasku.tilaustyyppi, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            $seurantalisa
            $kohdelisa
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and ((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
            $haku
            HAVING extra = '' or extra is null
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
                 WHERE lasku.yhtio  = '$kukarow[yhtio]'
                 and ((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
                 and tilaustyyppi  != 'W'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 6;
}
elseif ($toim == "VALMISTUSMYYNTISUPER") {
  $query = "SELECT lasku.tunnus tilaus, $seuranta $asiakasstring asiakas, $kohde lasku.viesti tilausviite, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka.extranet extra, tilaustyyppi, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            $seurantalisa
            $kohdelisa
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and tila          in ('L','N','V')
            and alatila       not in ('X','V')
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
                 WHERE lasku.yhtio  = '$kukarow[yhtio]'
                 and tila           in ('L','N','V')
                 and alatila        not in ('X','V')
                 and tilaustyyppi  != 'W'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 6;
}
elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {

  if ($toim == "TYOMAARAYSSUPER") {
    $tyomalatlat = " and lasku.alatila != 'X' ";
  }
  else {
    $tyomalatlat = " and lasku.alatila in ('','A','B','C','J') ";
  }

  $query = "SELECT lasku.tunnus tilaus,
            concat_ws('<br>', lasku.ytunnus, lasku.nimi, if (lasku.tilausyhteyshenkilo='', NULL, lasku.tilausyhteyshenkilo), if (lasku.viesti='', NULL, lasku.viesti), concat_ws(' ', ifnull((SELECT selitetark_2 FROM avainsana WHERE avainsana.yhtio=tyomaarays.yhtio and avainsana.laji = 'sarjanumeron_li' and avainsana.selite = 'MERKKI' and avainsana.selitetark=tyomaarays.merkki LIMIT 1), tyomaarays.merkki), tyomaarays.mallivari)) asiakas, lasku.luontiaika,
            if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, ";

  if ($kukarow['hinnat'] == 0) $query .= " round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo, round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa, ";

  $query .= "  $toimaikalisa alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi, lasku.varasto
        FROM lasku use index (tila_index)
        LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
        LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
        LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
        LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
        WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('A','L','N') and lasku.tilaustyyppi='A' $tyomalatlat
        $haku
        GROUP BY lasku.tunnus
        $mt_order_by
        $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('A','L','N') and lasku.tilaustyyppi='A' $tyomalatlat";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 5;
}
elseif ($toim == "REKLAMAATIO" or $toim == "VASTAANOTA_REKLAMAATIO" or $toim == "REKLAMAATIOSUPER" or $toim == "TAKUU" or $toim == "TAKUUSUPER") {

  if ($toim == "REKLAMAATIOSUPER" or $toim == "TAKUUSUPER") {
    $rekla_tila = " and lasku.tila in ('N','C','L') and lasku.alatila in ('','A','B','C','J','D') ";
  }
  elseif ($toim == "VASTAANOTA_REKLAMAATIO") {
    $rekla_tila = " and lasku.tila = 'C' and lasku.alatila in ('A','B') ";
  }
  else {
    if ($yhtiorow['reklamaation_kasittely'] == 'U') {
      $rekla_tila = " and lasku.tila = 'C' and lasku.alatila = '' ";
    }
    else {
      $rekla_tila = " and tila in ('L','N','C') and alatila in ('','A') ";
    }
  }

  $tilaustyyppilisa = ($toim == "TAKUU" or $toim == "TAKUUSUPER") ? "U" : "R";

  $query = "SELECT DISTINCT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tilaustyyppi, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            LEFT JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
            {$tilausrivin_lisatiedot_join}
            WHERE lasku.yhtio      = '$kukarow[yhtio]'
            and lasku.tilaustyyppi = '{$tilaustyyppilisa}'
            $rekla_tila
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio      = '$kukarow[yhtio]'
                 and lasku.tilaustyyppi = '{$tilaustyyppilisa}'
                 $rekla_tila";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 5;
}
elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
  $query = "SELECT lasku.tunnus tilaus,
            concat_ws('<br>',lasku.nimi,lasku.tilausyhteyshenkilo,lasku.viesti, concat_ws(' ', ifnull((SELECT selitetark_2 FROM avainsana WHERE avainsana.yhtio=tyomaarays.yhtio and avainsana.laji = 'sarjanumeron_li' and avainsana.selite = 'MERKKI' and avainsana.selitetark=tyomaarays.merkki LIMIT 1), tyomaarays.merkki), tyomaarays.mallivari)) asiakas,
            lasku.ytunnus, lasku.luontiaika,
            if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa alatila, lasku.tila, lasku.tunnus, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN tyomaarays ON tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='S' and alatila in ('','A','B','J','C')
            $haku
            $mt_order_by
            $rajaus";
  $miinus = 4;
}
elseif ($toim == "TARJOUS") {
  $query = "SELECT DISTINCT if(tunnusnippu>0,tunnusnippu,lasku.tunnus) tarjous, $asiakasstring asiakas, $seuranta $kohde lasku.viesti tilausviite, concat_ws('<br>', lasku.luontiaika, lasku.muutospvm) Pvm,
            if(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, interval $yhtiorow[tarjouksen_voimaika] day)) >= now(), '<font class=\"green\">".t("Voimassa")."</font>', '<font class=\"red\">".t("Erääntynyt")."</font>') voimassa,
            DATEDIFF(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, INTERVAL $yhtiorow[tarjouksen_voimaika] day)), now()) pva,
            if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            $toimaikalisa alatila, tila, lasku.tunnus, tunnusnippu, lasku.liitostunnus, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            LEFT JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
            {$tilausrivin_lisatiedot_join}
            $seurantalisa
            $kohdelisa
            WHERE lasku.yhtio   = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A')
            AND lasku.clearing != 'EXTTARJOUS'
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila ='T' and lasku.tilaustyyppi='T' and lasku.alatila in ('','A') AND lasku.clearing != 'EXTTARJOUS'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 6;
}
elseif ($toim == "TARJOUSSUPER") {
  $query = "SELECT DISTINCT if(tunnusnippu>0,tunnusnippu,lasku.tunnus) tarjous, $asiakasstring asiakas, $seuranta $kohde lasku.viesti tilausviite, concat_ws('<br>', lasku.luontiaika, lasku.muutospvm) Pvm,
            if(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, interval $yhtiorow[tarjouksen_voimaika] day)) >= now(), '<font class=\"green\">".t("Voimassa")."</font>', '<font class=\"red\">".t("Erääntynyt")."</font>') voimassa,
            DATEDIFF(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, INTERVAL $yhtiorow[tarjouksen_voimaika] day)), now()) pva,
            if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            $toimaikalisa alatila, tila, lasku.tunnus, tunnusnippu, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            LEFT JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
            {$tilausrivin_lisatiedot_join}
            $seurantalisa
            $kohdelisa
            WHERE lasku.yhtio   = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A','X')
            AND lasku.clearing != 'EXTTARJOUS'
            $haku
            $mt_order_by
            $rajaus";

  // haetaan kaikkien avoimien tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila ='T' and lasku.tilaustyyppi='T' and lasku.alatila in ('','A') AND lasku.clearing != 'EXTTARJOUS'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 5;
}
elseif ($toim == "EXTTARJOUS") {
  $query = "SELECT DISTINCT if(tunnusnippu>0,tunnusnippu,lasku.tunnus) tarjous, $asiakasstring asiakas, $seuranta $kohde concat_ws('<br>', lasku.luontiaika, lasku.muutospvm) Pvm,
            if(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, interval $yhtiorow[tarjouksen_voimaika] day)) >= now(), '<font class=\"green\">".t("Voimassa")."</font>', '<font class=\"red\">".t("Erääntynyt")."</font>') voimassa,
            DATEDIFF(if(lasku.olmapvm != '0000-00-00', lasku.olmapvm, date_add(lasku.muutospvm, INTERVAL $yhtiorow[tarjouksen_voimaika] day)), now()) pva,
            if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            $toimaikalisa alatila, tila, lasku.tunnus, tunnusnippu, lasku.liitostunnus, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            $seurantalisa
            $kohdelisa
            WHERE lasku.yhtio   = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A')
            AND lasku.clearing  = 'EXTTARJOUS'
            AND lasku.ytunnus  != ''
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila ='T' and lasku.tilaustyyppi='T' and lasku.alatila in ('','A') AND lasku.clearing = 'EXTTARJOUS'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 6;
}
elseif ($toim == "EXTRANET") {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'N' and lasku.alatila = 'F'
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'N' and lasku.alatila = 'F'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 4;
}
elseif ($toim == "LAVAKERAYS") {

  $query = "SELECT DISTINCT lasku.toim_maa maa, lasku.tunnus tilaus, asiakkaan_tilausnumero as 'astilno', $asiakasstring asiakas, asiakas.asiakasnro, lasku.luontiaika, count(tilausrivi.tunnus) riveja, ";

  if ($kukarow['hinnat'] == 0) {
    $query .= " round(sum(tilausrivi.hinta
                  / if('$yhtiorow[alv_kasittely]'  = '' AND tilausrivi.alv < 500,
                    (1 + tilausrivi.alv / 100),
                    1)
                  * (tilausrivi.varattu + tilausrivi.jt + tilausrivi.kpl)
                  * {$query_ale_lisa}), 2) AS arvo, ";
  }

  $query .= "$toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.varasto
             FROM lasku use index (tila_index)
             JOIN asiakas ON (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus)
             LEFT JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
             WHERE lasku.yhtio = '$kukarow[yhtio]'
             and lasku.tila = 'N'
             and lasku.alatila = 'FF'
             $haku
             GROUP BY lasku.tunnus
             $mt_order_by
             $rajaus";


  $miinus = 4;
}
elseif ($toim == "LASKUTUSKIELTO") {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.mapvm, lasku.alatila, lasku.tila, lasku.tunnus, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('N','L') and lasku.alatila != 'X' and lasku.chn = '999'
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('N','L') and lasku.alatila != 'X' and lasku.chn = '999'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 5;
}
elseif ($toim == 'OSTO') {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, if(kuka1.extranet is null, 0, if(kuka1.extranet != '', 1, 0)) kuka_ext,
            lasku.tilaustyyppi, lasku.varasto,
            sum(if(tilausrivi.kpl is not null and tilausrivi.kpl != 0, 1, 0)) varastokpl,
            sum(if(tilausrivi.jaksotettu is not null and tilausrivi.jaksotettu != 0, 1, 0)) vahvistettukpl,
            count(*) rivit
            FROM lasku use index (tila_index)
            LEFT JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi = 'O'
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio       = '$kukarow[yhtio]'
            and lasku.tila          = 'O'
            and lasku.alatila       IN ('', 'G')
            and lasku.tilaustyyppi != 'O'
            $haku
            GROUP BY 1,2,3,4,5,6,7,8,9,10
            $mt_order_by
            $rajaus";
  $miinus = 9;
}
elseif ($toim == 'OSTOSUPER') {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            lasku.viesti tilausviite, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, if(kuka1.extranet is null, 0, if(kuka1.extranet != '', 1, 0)) kuka_ext,
            lasku.tilaustyyppi, lasku.varasto,
            sum(if(tilausrivi.kpl is not null and tilausrivi.kpl != 0, 1, 0)) varastokpl,
            sum(if(tilausrivi.jaksotettu is not null and tilausrivi.jaksotettu != 0, 1, 0)) vahvistettukpl,
            count(*) rivit
            FROM lasku use index (tila_index)
            LEFT JOIN tilausrivi ON tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi = 'O'
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio       = '$kukarow[yhtio]'
            and lasku.tila          = 'O'
            and lasku.alatila       in ('A','')
            and lasku.tilaustyyppi != 'O'
            $haku
            GROUP BY 1,2,3,4,5,6,7,8,9,10
            $mt_order_by
            $rajaus";
  $miinus = 9;
}
elseif ($toim == 'HAAMU') {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            WHERE lasku.yhtio      = '$kukarow[yhtio]'
            and lasku.tila         = 'O'
            and lasku.alatila      = ''
            and lasku.tilaustyyppi = 'O'
            $haku
            $mt_order_by
            $rajaus";
  $miinus = 5;
}
elseif ($toim == 'PROJEKTI') {
  $query = "SELECT if(lasku.tunnusnippu > 0 and lasku.tunnusnippu!=lasku.tunnus, concat(lasku.tunnus,',',lasku.tunnusnippu), lasku.tunnus) tilaus, $seuranta lasku.nimi asiakas, $kohde lasku.ytunnus, lasku.luontiaika, if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, lasku.tunnusnippu, lasku.liitostunnus, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            $seurantalisa
            $kohdelisa
            WHERE lasku.yhtio = '$kukarow[yhtio]' and tila IN ('R','L','N','A') and alatila NOT IN ('X') and lasku.tilaustyyppi!='9'
            $haku
            $mt_order_by
            $rajaus";
  $miinus = 6;
}
elseif ($toim == 'YLLAPITO') {
  $laitejoini = '';
  $laiteselecti = '';

  if ($yhtiorow['laiterekisteri_kaytossa'] != '') {
    $laiteselecti = " group_concat(distinct tilausrivin_lisatiedot.sopimuksen_lisatieto1 separator '<br>') sarjanumero,
                      group_concat(distinct tilausrivin_lisatiedot.sopimuksen_lisatieto2 separator '<br>') vasteaika, ";

    $laitejoini = " JOIN tilausrivin_lisatiedot on (tilausrivin_lisatiedot.yhtio = lasku.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
                    LEFT JOIN laitteen_sopimukset ON laitteen_sopimukset.sopimusrivin_tunnus = tilausrivi.tunnus
                    LEFT JOIN laite ON laite.tunnus = laitteen_sopimukset.laitteen_tunnus ";
  }

  $query = "SELECT lasku.tunnus tilaus,
            lasku.asiakkaan_tilausnumero 'asiak_tilno',
            $asiakasstring asiakas,
            lasku.luontiaika,
            if(kuka1.kuka != kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi) laatija,
            concat_ws('###', sopimus_alkupvm, sopimus_loppupvm) sopimuspvm,
            {$laiteselecti}
            lasku.alatila,
            lasku.tila,
            lasku.tunnus,
            lasku.varasto,
            tunnusnippu,
            group_concat(tilausrivi.tunnus) tilausrivitunnukset,
            sopimus_loppupvm,
            laskun_lisatiedot.sopimus_numero
            FROM lasku use index (tila_index)
            JOIN tilausrivi on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            LEFT JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus)
            {$laitejoini}
            WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
            AND lasku.tila    = '0'
            AND lasku.alatila NOT IN ('D')
            $haku
            GROUP BY 1,2,3,4,5,6
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus and (laskun_lisatiedot.sopimus_loppupvm >= now() or laskun_lisatiedot.sopimus_loppupvm = '0000-00-00'))
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('0') and lasku.alatila != 'D'";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 8;
}
elseif ($toim == 'KESKEN') {
  $query = "SELECT DISTINCT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika,
            if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            $seuranta $kohde  $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka1.extranet extra, lasku.mapvm, lasku.tilaustyyppi, lasku.label, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            LEFT JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
            {$tilausrivin_lisatiedot_join}
            $seurantalisa
            $kohdelisa
            WHERE lasku.yhtio  = '$kukarow[yhtio]'
            and lasku.tila     = 'N'
            and lasku.alatila  in ('A','','T','U','G')
            and lasku.clearing not in ('EXTENNAKKO','EXTTARJOUS')
            $haku
            $tepalisa
            HAVING extra = '' or extra is null
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
                 AND lasku.tila     = 'N'
                 AND lasku.alatila  IN ('A','','T','U','G')
                 AND lasku.clearing NOT IN ('EXTENNAKKO','EXTTARJOUS')
                 {$sumhaku}";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 8;
}
elseif ($toim == 'TOSI_KESKEN') {
  $query = "SELECT DISTINCT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika,
            if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            $seuranta $kohde  $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka1.extranet extra, lasku.mapvm, lasku.tilaustyyppi, lasku.label, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            LEFT JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
            {$tilausrivin_lisatiedot_join}
            WHERE lasku.yhtio  = '$kukarow[yhtio]'
            and lasku.tila     = 'N'
            and lasku.alatila  in ('','G')
            and lasku.clearing not in ('EXTENNAKKO','EXTTARJOUS')
            $haku
            $tepalisa
            HAVING extra = '' or extra is null
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio  = '$kukarow[yhtio]'
                 and lasku.tila     = 'N'
                 and lasku.alatila  in ('','G')
                 and lasku.clearing not in ('EXTENNAKKO','EXTTARJOUS')";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 8;
}
elseif ($toim == 'KESKEN_TAI_TOIMITETTAVISSA') {
  $query = "SELECT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika,
            if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            $seuranta $kohde  $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka1.extranet extra, lasku.mapvm, lasku.tilaustyyppi, lasku.label
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            $seurantalisa
            $kohdelisa
            WHERE lasku.yhtio  = '$kukarow[yhtio]'
            and lasku.tila     = 'N'
            and lasku.alatila  in ('A','')
            and lasku.clearing not in ('EXTENNAKKO','EXTTARJOUS')
            $haku
            $tepalisa
            HAVING extra = '' or extra is null
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
                 AND lasku.tila     = 'N'
                 AND lasku.alatila  IN ('A','','T','U','G')
                 AND lasku.clearing NOT IN ('EXTENNAKKO','EXTTARJOUS')
                 {$sumhaku}";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 7;
}
elseif ($toim == 'ODOTTAA_SUORITUSTA') {
  $query = "SELECT DISTINCT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika,
            if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija,
            $seuranta $kohde  $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka1.extranet extra, lasku.mapvm, lasku.tilaustyyppi, lasku.label, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            LEFT JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
            WHERE lasku.yhtio  = '$kukarow[yhtio]'
            and lasku.tila     = 'N'
            and lasku.alatila  = 'G'
            and lasku.clearing not in ('EXTENNAKKO','EXTTARJOUS')
            $haku
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'N' and lasku.alatila = 'G' and lasku.clearing not in ('EXTENNAKKO','EXTTARJOUS')";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 8;
}
else {

  $_querylisa = "";
  if ($viitetiedot == 'kylla') {
    $_querylisa = " lasku.asiakkaan_tilausnumero astilno, lasku.viesti tilausviite,";
  }

  $query = "SELECT DISTINCT lasku.tunnus tilaus, $asiakasstring asiakas, lasku.luontiaika,
            if(kuka1.kuka is null, lasku.laatija, if (kuka1.kuka!=kuka2.kuka, concat_ws('<br>', kuka1.nimi, kuka2.nimi), kuka1.nimi)) laatija, $_querylisa
            $seuranta $kohde  $toimaikalisa lasku.alatila, lasku.tila, lasku.tunnus, kuka1.extranet extra, lasku.mapvm, lasku.tilaustyyppi, lasku.label, lasku.kerayspvm, lasku.varasto
            FROM lasku use index (tila_index)
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
            LEFT JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi != 'D')
            {$tilausrivin_lisatiedot_join}
            $seurantalisa
            $kohdelisa
            WHERE lasku.yhtio  = '$kukarow[yhtio]'
            AND ((lasku.tila IN ('L','N')
              AND lasku.alatila IN ('A','T','U','G'))
            OR ((kuka1.extranet = '' OR kuka1.extranet is NULL) AND lasku.tila = 'N' AND lasku.alatila = ''))
            and lasku.clearing not in ('EXTENNAKKO','EXTTARJOUS')
            $haku
            $tepalisa
            $mt_order_by
            $rajaus";

  // haetaan tilausten arvo
  if ($kukarow['hinnat'] == 0) {
    $sumquery = "SELECT
                 round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                 round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                 count(distinct lasku.tunnus) kpl
                 FROM lasku use index (tila_index)
                 JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
                 JOIN kuka ON (kuka.yhtio = lasku.yhtio AND kuka.kuka = lasku.laatija)
                 WHERE lasku.yhtio  = '$kukarow[yhtio]'
                 AND ((lasku.tila IN ('L','N')
                  AND lasku.alatila IN ('A','T','U','G'))
                 OR ((kuka.extranet = '' OR kuka.extranet is NULL) AND lasku.tila = 'N' AND lasku.alatila = ''))
                 and lasku.clearing not in ('EXTENNAKKO','EXTTARJOUS')
                 $tepalisa";
    $sumresult = pupe_query($sumquery);
    $sumrow = mysql_fetch_assoc($sumresult);
  }

  $miinus = 9;
}

$result = pupe_query($query);

if (mysql_num_rows($result) != 0) {

  if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE and $tee_excel) {
    include 'inc/pupeExcel.inc';

    $worksheet    = new pupeExcel();
    $format_bold = array("bold" => TRUE);
    $excelrivi    = 0;
  }

  if ($toim == 'OSTO') {
    $ext_chk  = '';
    $temp_row = mysql_fetch_assoc($result);

    if ($temp_row['kuka_ext'] != '' and $temp_row['kuka_ext'] == 0) {
      echo "<br/><br/><font class='head'>", t("Myyjien ostotilaukset"), "</font><br/>";
      $ext_chk = $temp_row['kuka_ext'];
    }
    elseif ($temp_row['kuka_ext'] != '' and $temp_row['kuka_ext'] == 1) {
      echo "<br/><br/><font class='head'>", t("Extranet-käyttäjien ostotilaukset"), "</font><br/>";
      $ext_chk = $temp_row['kuka_ext'];
    }

    unset($temp_row);
    mysql_data_seek($result, 0);
  }

  echo "<table>";
  echo "<tr>";

  $tturllisa = "";

  if ($toim == "LAVAKERAYS") {
    $tturllisa = "&toimitustapa=$toimitustapa";
  }

  if ($toim == "LAVAKERAYS" and !empty($toimitustapa)) {
    echo "<th></th>";
  }

  $ii = 0;
  for ($i = 0; $i < mysql_num_fields($result)-$miinus; $i++) {

    if (isset($mt_order[mysql_field_name($result, $i)]) and $mt_order[mysql_field_name($result, $i)] == 'ASC') {
      echo "<th align='left'><a href='muokkaatilaus.php?toim=$toim&asiakastiedot=$asiakastiedot&limit=$limit&etsi=$etsi&toimipaikka=$toimipaikka{}&mt_order[".mysql_field_name($result, $i)."]=DESC{$tturllisa}'>".t(mysql_field_name($result, $i))."<img src='{$palvelin2}pics/lullacons/arrow-small-up-green.png' /></a></th>";
    }
    elseif (isset($mt_order[mysql_field_name($result, $i)]) and $mt_order[mysql_field_name($result, $i)] == 'DESC') {
      echo "<th align='left'><a href='muokkaatilaus.php?toim=$toim&asiakastiedot=$asiakastiedot&limit=$limit&etsi=$etsi&toimipaikka=$toimipaikka&mt_order[".mysql_field_name($result, $i)."]=ASC{$tturllisa}'>".t(mysql_field_name($result, $i))."<img src='{$palvelin2}pics/lullacons/arrow-small-down-green.png' /></a></th>";
    }
    else {
      echo "<th align='left'><a href='muokkaatilaus.php?toim=$toim&asiakastiedot=$asiakastiedot&limit=$limit&etsi=$etsi&toimipaikka=$toimipaikka&mt_order[".mysql_field_name($result, $i)."]=ASC{$tturllisa}'>".t(mysql_field_name($result, $i))."</a></th>";
    }

    if (isset($worksheet)) {

      if (mysql_field_name($result, $i) == "asiakas") {
        $worksheet->write($excelrivi, $ii, t("Ytunnus"), $format_bold);
        $ii++;
        $worksheet->write($excelrivi, $ii, t("Asiakas"), $format_bold);
        $ii++;
      }
      else {
        $worksheet->write($excelrivi, $ii, ucfirst(t(mysql_field_name($result, $i))), $format_bold);
        $ii++;
      }
    }
  }
  $excelrivi++;

  if ($toim != "LAVAKERAYS")  {
    echo "<th align='left'>".t("tyyppi")."</th>";
  }

  // Jos yhtiönparametri saldo_kasittely on asetettu tilaan
  // "myytävissä-kpl lasketaan keräyspäivän mukaan", näytetään onko tuotteita saldoilla
  // syötettynä keräyspäivänä.
  if (!empty($yhtiorow["saldo_kasittely"]) and $toim == '' and $naytetaanko_saldot == 'kylla') {
    echo "<th>".t("Riittääkö saldot keräyspäivänä")."?</th>";
  }

  echo "<th class='back'></th></tr>";

  $lisattu_tunnusnippu    = array();
  $toimitettavat_ennakot  = array();
  $nakyman_tunnukset     = array();

  $ostotil_tiltyyp_res = t_avainsana("OSTOTIL_TILTYYP");

  while ($row = mysql_fetch_assoc($result)) {

    $piilotarivi = "";

    if ($toim == "EXTRANET" and $row["tila"] == 'N' and $row["alatila"] == 'F') {
      // katsotaan onko muilla aktiivisena
      $query = "SELECT tunnus
                FROM kuka
                WHERE yhtio  = '{$kukarow['yhtio']}'
                AND kesken   = '{$row['tilaus']}'
                AND kesken  != 0
                AND kuka    != '{$kukarow['kuka']}'";
      $res_x = pupe_query($query);

      if (mysql_num_rows($res_x) != 0) $piilotarivi = "kylla";
    }

    if ($toim == 'OSTO' and $row['kuka_ext'] != '' and $ext_chk != '' and (int) $ext_chk != (int) $row['kuka_ext']) {
      echo "</table>";
      echo "<br/><br/><font class='head'>";

      if ((int) $row['kuka_ext'] == 1) {
        echo t("Extranet-käyttäjien ostotilaukset");
      }
      else {
        echo t("Myyjien ostotilaukset");
      }

      $ext_chk = '';
      echo "</font><br/>";

      echo "<table>";

      for ($i=0; $i < mysql_num_fields($result)-$miinus; $i++) {
        echo "<th align='left'>".t(mysql_field_name($result, $i))."</th>";
      }

      echo "<th align='left'>".t("tyyppi")."</th><td class='back'></td></tr>";
    }

    if ($toim == 'HYPER') {

      if ($row["tila"] == 'E' and $row["trivityyppi"] == 'E' and $row['clearing'] != "EXTENNAKKO") {
        $whiletoim = 'ENNAKKO';
      }
      elseif ($row["tila"] == 'E' and $row["trivityyppi"] == 'E' and $row['clearing'] == "EXTENNAKKO") {
        $whiletoim = 'EXTENNAKKO';
      }
      elseif ($row["tila"] == 'N' and $row["alatila"] == 'U') {
        $whiletoim = "JTTOIMITA";
      }
      elseif ($row["tila"] == 'N' and $row["alatila"] == 'F') {
        $whiletoim = "EXTRANET";
      }
      elseif (in_array($row["tila"], array('N', 'L')) and $row["alatila"] != 'X' and $row["chn"] == '999') {
        $whiletoim = "LASKUTUSKIELTO";
      }
      elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('', 'A', 'B', 'J')) and $row["tilaustyyppi"] == 'M') {
        $whiletoim = "MYYNTITILI";
      }
      elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('', 'A', 'B', 'C', 'J')) and $row["tilaustyyppi"] == 'M') {
        $whiletoim = "MYYNTITILISUPER";
      }
      elseif ($row["tila"] == 'G' and $row["alatila"] == 'V' and $row["tilaustyyppi"] == 'M') {
        $whiletoim = "MYYNTITILITOIMITA";
      }
      elseif ($row["tila"] == 'T' and $row["tilaustyyppi"] == 'T' and in_array($row["alatila"], array('', 'A')) and $row['clearing'] != 'EXTTARJOUS') {
        $whiletoim = "TARJOUS";
      }
      elseif ($row["tila"] == 'T' and $row["tilaustyyppi"] == 'T' and in_array($row["alatila"], array('', 'A', 'X')) and $row['clearing'] != 'EXTTARJOUS') {
        $whiletoim = "TARJOUSSUPER";
      }
      elseif ($row["tila"] == 'T' and $row["tilaustyyppi"] == 'T' and in_array($row["alatila"], array('', 'A', 'X')) and $row['clearing'] == 'EXTTARJOUS') {
        $whiletoim = "EXTTARJOUS";
      }
      elseif (in_array($row["tila"], array('A', 'L', 'N')) and $row["tilaustyyppi"] == 'A' and in_array($row["alatila"], array('', 'A', 'B', 'C', 'J'))) {
        $whiletoim = "TYOMAARAYS";
      }
      elseif (in_array($row["tila"], array('A', 'L', 'N')) and $row["tilaustyyppi"] == 'A' and $row["alatila"] != 'X') {
        $whiletoim = "TYOMAARAYSSUPER";
      }
      elseif (in_array($row["tila"], array('L', 'N', 'C')) and $row["tilaustyyppi"] == 'R' and in_array($row["alatila"], array('', 'A', 'B', 'C', 'J', 'D'))) {
        $whiletoim = "REKLAMAATIO";
      }
      elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('', 'A', 'J'))) {
        $whiletoim = "SIIRTOLISTA";
      }
      elseif ($row["tila"] == 'G' and in_array($row["alatila"], array('', 'A', 'B', 'C', 'D', 'J', 'T'))) {
        $whiletoim = "SIIRTOLISTASUPER";
      }
      elseif ($row["tila"] == 'V' and in_array($row["alatila"], array('', 'A', 'B', 'J'))) {
        $whiletoim = 'VALMISTUS';
      }
      elseif ($row["tila"] == 'V' and in_array($row["alatila"], array('', 'A', 'B', 'C', 'J'))) {
        $whiletoim = "VALMISTUSSUPER";
      }
      elseif (($row["tila"] == 'V' and in_array($row["alatila"], array('', 'A', 'B', 'J'))) or (in_array($row["tila"], array('L', 'N')) and in_array($row["alatila"], array('A', '')))) {
        $whiletoim = "VALMISTUSMYYNTI";
      }
      elseif (in_array($row["tila"], array('L', 'N', 'V')) and !in_array($row["alatila"], array('X', 'V'))) {
        $whiletoim == "VALMISTUSMYYNTISUPER";
      }
      elseif ($row["tila"] == 'S' and in_array($row["alatila"], array('', 'A', 'B', 'J', 'C'))) {
        $whiletoim = "SIIRTOTYOMAARAYS";
      }
      elseif ($row["tila"] == '0' and $row["alatila"] != 'D') {
        $whiletoim = 'YLLAPITO';
      }
      elseif (in_array($row["tila"], array('L', 'N')) and in_array($row["alatila"], array('A', ''))) {
        $whiletoim = '';
      }


      if (in_array($row["tila"], array('L', 'N')) and $row["alatila"] != 'X') {
        $whiletoim = 'SUPER';
      }
      elseif (in_array($row["tila"], array('R', 'L', 'N', 'A')) and $row["alatila"] != 'X' and $row["tilaustyyppi"] != '9') {
        $whiletoim = 'PROJEKTI';
      }
    }
    elseif ($toim == "VASTAANOTA_REKLAMAATIO" or $toim == "TAKUU" or $toim == "TAKUUSUPER") {
      $whiletoim = "REKLAMAATIO";
    }
    elseif ($toim == "TEHDASPALAUTUKSET" or $toim == "SUPERTEHDASPALAUTUKSET") {
      $whiletoim = "RIVISYOTTO";
    }
    else {
      $whiletoim = $toim;
    }

    $pitaako_varmistaa = "";

    // jos kyseessä on "odottaa JT tuotteita rivi"
    if ($row["tila"] == "N" and $row["alatila"] == "T") {
      $query = "SELECT tunnus from tilausrivi where yhtio='$kukarow[yhtio]' and tyyppi='L' and otunnus='$row[tilaus]'";
      $countres = pupe_query($query);

      // ja sillä ei ole yhtään riviä
      if (mysql_num_rows($countres) == 0) {
        $piilotarivi = "kylla";
      }
    }

    //  Nipuista vain se viimeisin jos niin halutaan
    if (isset($row["tunnusnippu"]) and $row["tunnusnippu"] > 0 and ($whiletoim == "PROJEKTI" or $whiletoim == "TARJOUS")) {

      //  Tunnusnipuista näytetään vaan se eka!
      // ja sillä ei ole yhtään riviä
      if (array_search($row["tunnusnippu"], $lisattu_tunnusnippu) !== false) {
        $piilotarivi = "kylla";
      }
      else {
        $lisattu_tunnusnippu[] = $row["tunnusnippu"];
      }
    }

    if ($piilotarivi == "") {

      // jos kyseessä on "odottaa JT tuotteita rivi ja kyseessa on toim=JTTOIMITA"
      if ($row["tila"] == "N" and $row["alatila"] == "U") {

        if ($yhtiorow["varaako_jt_saldoa"] != "") {
          $lisavarattu = " + tilausrivi.varattu";
        }
        else {
          $lisavarattu = "";
        }

        $query = "SELECT tilausrivi.tuoteno, tilausrivi.jt $lisavarattu jt
                  from tilausrivi
                  where tilausrivi.yhtio = '$kukarow[yhtio]'
                  and tilausrivi.tyyppi  = 'L'
                  and tilausrivi.otunnus = '$row[tilaus]'";
        $countres = pupe_query($query);

        $jtok = 0;

        while ($countrow = mysql_fetch_assoc($countres)) {
          list( , , $jtapu_myytavissa) = saldo_myytavissa($countrow["tuoteno"], "JTSPEC", $row['varasto'], "");

          if ($jtapu_myytavissa < $countrow["jt"]) {
            $jtok--;
          }
        }
      }

      $label_color = "";

      if (isset($row['label']) and $row['label'] != '') {
        $label_query = "SELECT selite
                        FROM avainsana
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = {$row['label']}
                        AND laji    = 'label'";
        $label_result = pupe_query($label_query);

        if (mysql_num_rows($label_result) == 1) {
          $label_row = mysql_fetch_assoc($label_result);
          $label_color = "style = 'background-color: {$label_row['selite']};'";
        }
      }

      echo "<tr class='aktiivi' {$label_color}>";

      if ($toim == "LAVAKERAYS" and !empty($toimitustapa)) {
        echo "<td><input type='checkbox' class='lavakerays_valitse_keraykseen shift' name = 'keraykseen[]' value='{$row['tilaus']}'></td>";
      }

      $zendesk_viesti = FALSE;
      $ii = 0;

      for ($i = 0; $i < mysql_num_fields($result)-$miinus; $i++) {

        $fieldname = mysql_field_name($result, $i);

        if ($whiletoim == "YLLAPITO" and $row["sopimus_loppupvm"] < date("Y-m-d") and $row["sopimus_loppupvm"] != '0000-00-00') {
          $class = 'tumma';
        }
        else {
          $class = '';
        }

        if ($fieldname == 'luontiaika' or $fieldname == 'toimaika') {
          echo "<td class='{$class}' valign='top' align='right'>";

          if (($whiletoim == '' or $whiletoim == 'SUPER' or $whiletoim == 'KESKEN' or $whiletoim == 'HYPER' or $whiletoim == 'TOSI_KESKEN' or $whiletoim == 'KESKEN_TAI_TOIMITETTAVISSA' or $whiletoim == 'ODOTTAA_SUORITUSTA' or $whiletoim == 'TEHDASPALAUTUKSET' or $whiletoim == 'SUPERTEHDASPALAUTUKSET' or $whiletoim == "TAKUU" or $whiletoim == "TAKUUSUPER") and $fieldname == 'toimaika' and $row['toimaika'] == '0000-00-00') echo t("Avoin");
          else echo tv1dateconv($row[$fieldname], "PITKA", "LYHYT");

          echo "</td>";
        }
        elseif ($fieldname == 'sopimuspvm') {

          list($sopalk, $soplop) = explode("###", $row[$fieldname]);

          if ($soplop == "0000-00-00") {
            $soplop = t("Toistaiseksi");
          }
          else {
            $soplop = tv1dateconv($soplop);
          }

          echo "<td class='$class' valign='top' align='right'>".tv1dateconv($sopalk)." - $soplop</td>";
        }
        elseif ($fieldname == 'Pvm') {
          list($aa, $bb) = explode('<br>', $row[$fieldname]);

          echo "<td class='$class' valign='top'>".tv1dateconv($aa, "PITKA", "LYHYT")."<br>".tv1dateconv($bb, "PITKA", "LYHYT")."</td>";
        }
        elseif ($fieldname == "tilaus" or $fieldname == "tarjous") {

          $query_comments = "SELECT group_concat(concat_ws('<br>', comments, sisviesti2) SEPARATOR '<br><br>') comments
                             FROM lasku use index (primary)
                             WHERE yhtio = '$kukarow[yhtio]'
                             AND tunnus  in (".$row[$fieldname].")
                             AND (comments != '' OR sisviesti2 != '')";
          $result_comments = pupe_query($query_comments);
          $row_comments = mysql_fetch_assoc($result_comments);

          if (trim($row_comments["comments"]) != "") {
            echo "<td class='$class' align='right' valign='top'>";
            echo "<div id='div_kommentti".$row[$fieldname]."' class='popup' style='width: 500px;'>";
            echo $row_comments["comments"];
            echo "</div>";
            echo "<a class='tooltip' id='kommentti".$row[$fieldname]."'>".str_replace(",", "<br>*", $row[$fieldname])."</a>";
          }
          else {
            echo "<td class='$class' align='right' valign='top'>".str_replace(",", "<br>*", $row[$fieldname]);
          }

          if ($kukarow["yhtio"] == "savt") {
            $query_comments = "SELECT viesti
                               FROM lasku use index (primary)
                               WHERE yhtio  = '$kukarow[yhtio]'
                               AND tunnus   in ({$row[$fieldname]})
                               AND viesti  != ''
                               LIMIT 1";
            $result_comments = pupe_query($query_comments);
            $row_comments = mysql_fetch_assoc($result_comments);

            $row_comments["viesti"] = preg_replace("/[^0-9]/", "", $row_comments["viesti"]);

            if ($row_comments["viesti"] != "") {
              echo "<br><a target='_blank' href='https://devlab.zendesk.com/tickets/{$row_comments["viesti"]}'>{$row_comments["viesti"]}</a>";
              $zendesk_viesti = TRUE;
            }
            else {
              // Haetaan tikettinumerot tilausriviltä
              $query_comments = "SELECT tilausrivi.kommentti
                                 FROM tilausrivi use index (yhtio_otunnus)
                                 WHERE tilausrivi.yhtio = '{$kukarow["yhtio"]}'
                                 AND tilausrivi.otunnus in ({$row[$fieldname]})
                                 AND left(tilausrivi.kommentti, 1) = '#'";
              $result_comments = pupe_query($query_comments);

              while ($row_comments = mysql_fetch_assoc($result_comments)) {
                list($row_ticket, $row_dummy) = explode(" ", $row_comments['kommentti'], 2);
                $row_ticket = preg_replace("/[^0-9]/", "", $row_ticket);

                if ($row_ticket != "") {
                  echo "<br><a target='_blank' href='https://devlab.zendesk.com/tickets/{$row_ticket}'>{$row_ticket}</a>";
                }
              }
            }
          }

          if ($yhtiorow['laiterekisteri_kaytossa'] != '' and $row['sopimus_numero'] != '') {
            echo "<br>".t('Sopimusnumero').": {$row['sopimus_numero']}";
          }
          echo "</td>";
        }
        elseif ($fieldname == "asiakas" and $kukarow["yhtio"] == "savt" and $zendesk_auth != "" and $zendesk_viesti) {

          echo "<td class='$class' valign='top'>".$row[$fieldname];

          list($ticket, $statukset, $priot) = zendesk_curl("https://devlab.zendesk.com/tickets/{$row_comments["viesti"]}.xml");

          if ($xml = simplexml_load_string($ticket)) {

            list($requester, $null, $null) = zendesk_curl("https://devlab.zendesk.com/users/".$xml->{"requester-id"}.".xml");
            list($assignee, $null, $null) = zendesk_curl("https://devlab.zendesk.com/users/".$xml->{"assignee-id"}.".xml");

            $requester = simplexml_load_string($requester);
            $assignee = simplexml_load_string($assignee);

            echo "<br><br><table><tr><th>Requester</th><td>".utf8_decode($requester->{"name"})."</td></tr>";
            echo "<tr><th>Subject</th><td>".utf8_decode($xml->{"subject"})."</td></tr>";
            echo "<tr><th>Status</th><td>".$statukset[(int) $xml->{"status-id"}]."</td></tr>";
            echo "<tr><th>Assignee</th><td>".utf8_decode($assignee->{"name"})."</td></tr></table>";
          }

          echo "</td>";
        }
        elseif ($fieldname == "seuranta") {

          $img = "mini-comment.png";
          $linkkilisa = "";
          $query_comments = "SELECT group_concat(tunnus) tunnukset
                             FROM lasku
                             WHERE yhtio      = '$kukarow[yhtio]'
                             AND lasku.tila  != 'S'
                             AND tunnusnippu  = '$row[tunnusnippu]' and tunnusnippu>0";
          $ares = pupe_query($query_comments);

          if (mysql_num_rows($ares) > 0) {
            $arow = mysql_fetch_assoc($ares);

            if ($arow["tunnukset"] != "") {
              //  Olisiko meillä kalenterissa kommentteja?
              $query_comments = "SELECT tunnus
                                 FROM kalenteri
                                 WHERE yhtio = '$kukarow[yhtio]'
                                 AND tyyppi  = 'Memo'
                                 AND otunnus IN ($arow[tunnukset])";
              $result_comments = pupe_query($query_comments);

              $nums="";
              if (mysql_num_rows($result_comments) > 0) {
                $img = "info.png";
                $linkkilisa = "onmouseover=\"popUp(event, 'asiakasmemo_".$row[$fieldname]."', '0', '0', '{$palvelin2}crm/asiakasmemo.php?tee=NAYTAMUISTIOT&liitostunnus=$row[liitostunnus]&tunnusnippu=$row[tunnusnippu]', false, true); return false;\" onmouseout=\"popUp(event, 'asiakasmemo_".$row[$fieldname]."'); return false;\"";
              }
            }
          }

          echo "<td class='$class' valign='top' NOWRAP>".$row[$fieldname]." <div style='float: right;'><img src='pics/lullacons/$img' class='info' $linkkilisa onclick=\"window.open('{$palvelin2}crm/asiakasmemo.php?tee=NAYTA&liitostunnus=$row[liitostunnus]&tunnusnippu=$row[tunnusnippu]&from=muokkaatilaus.php');\"> $nums</div></td>";
        }
        elseif ($fieldname == 'astilno') {
          echo "<td class='$class' align='left' valign='top'>".$row[$fieldname]."</td>";
        }
        elseif (is_numeric($row[$fieldname])) {
          echo "<td class='$class' align='right' valign='top'>".$row[$fieldname]."</td>";
        }
        elseif ($yhtiorow['laiterekisteri_kaytossa'] != '' and $whiletoim == "YLLAPITO" and $fieldname == 'sarjanumero') {
          // Haetaan sopimusriviin liitetyt sarjanumerot laiterekisteristä/laitteen_sopimuksista
          $query = "SELECT
                    group_concat(distinct laite.sarjanro SEPARATOR '<br>') sarjanumerot
                    FROM laitteen_sopimukset
                    JOIN laite ON laite.tunnus = laitteen_sopimukset.laitteen_tunnus
                    WHERE laitteen_sopimukset.sopimusrivin_tunnus IN ({$row['tilausrivitunnukset']})";
          $res = pupe_query($query);
          $sarjanumerotrivi = mysql_fetch_assoc($res);

          echo "<td class='$class' valign='top'>{$sarjanumerotrivi['sarjanumerot']}</td>";
        }
        elseif ($whiletoim == "YLLAPITO" and $fieldname == "asiakas") {
          list($_ytunnus, $_nimi) = explode('<br>', $row["asiakas"]);
          echo "<td class='$class' valign='top'>", tarkistahetu($_ytunnus), "<br>{$_nimi}", "</td>";
        }
        else {
          echo "<td class='$class' valign='top'>".$row[$fieldname]."</td>";
        }

        if (isset($worksheet)) {

          if ($fieldname == "asiakas") {
            $nimiosat = explode("<br>", $row[$fieldname]);

            $ytunnari = trim(array_shift($nimiosat));
            $lopnimit = trim(implode("\n", $nimiosat));

            $worksheet->writeString($excelrivi, $ii, $ytunnari);
            $ii++;
            $worksheet->writeString($excelrivi, $ii, $lopnimit);
            $ii++;

          }
          elseif (mysql_field_type($result, $i) == 'real') {
            $worksheet->writeNumber($excelrivi, $ii, sprintf("%.02f", $row[$fieldname]));
            $ii++;
          }
          else {
            $worksheet->writeString($excelrivi, $ii, $row[$fieldname]);
            $ii++;
          }
        }
      }

      if ($row["tila"] == "N" and $row["alatila"] == "U") {
        if ($jtok == 0) {
          echo "<td class='$class' valign='top'><font class='green'>".t("Voidaan toimittaa")."</font></td>";

          if (isset($worksheet)) {
            $worksheet->writeString($excelrivi, $ii, "Voidaan toimittaa");
            $ii++;
          }
        }
        else {
          echo "<td class='$class' valign='top'><font class='red'>".t("Ei voida toimittaa")."</font></td>";

          if (isset($worksheet)) {
            $worksheet->writeString($excelrivi, $ii, t("Ei voida toimittaa"));
            $ii++;
          }
        }
      }
      elseif ($toim != "LAVAKERAYS")  {

        $laskutyyppi = $row["tila"];
        $alatila   = $row["alatila"];

        //tehdään selväkielinen tila/alatila
        require "inc/laskutyyppi.inc";

        $tarkenne = " ";

        if ($row["tila"] == "V" and $row["tilaustyyppi"] == "V") {
          $tarkenne = " (".t("Asiakkaalle").") ";
        }
        elseif ($row["tila"] == "V" and  $row["tilaustyyppi"] == "W") {
          $tarkenne = " (".t("Varastoon").") ";
        }
        elseif (($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "R") {
          $tarkenne = " (".t("Reklamaatio").") ";
        }
        elseif (($row["tila"] == "N" or $row["tila"] == "L" or $row['tila'] == 'C') and $row["tilaustyyppi"] == "U") {
          $tarkenne = " (".t("Takuu").") ";
        }
        elseif (($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "A") {
          $laskutyyppi = "Työmääräys";
        }
        elseif ($row["tila"] == "N" and $row["tilaustyyppi"] == "E") {
          $laskutyyppi = "Ennakkotilaus kesken";
        }
        elseif ($row["tila"] == "G" and $row["tilaustyyppi"] == "M") {
          $laskutyyppi = "Myyntitili";
        }

        if ($row["tila"] == "G" and $row["tilaustyyppi"] == "M" and $row["alatila"] == "V") {
          $alatila = "Toimitettu asiakkaalle";
        }

        $varastotila = "";

        if (isset($row["varastokpl"]) and $row["varastokpl"] > 0) {
          $varastotila .= "<font class='info'><br>".t("Viety osittain varastoon")."</font>";
        }

        if (isset($row["vahvistettukpl"]) and $row["vahvistettukpl"] > 0) {
          $varastotila .= "<font class='info'><br>".t("Toimitusajat vahvistettu")." ({$row['vahvistettukpl']} / {$row['rivit']})</font>";
        }

        if (in_array($whiletoim, array('OSTO', 'OSTOSUPER')) and $row['tila'] == 'O') {

          if (mysql_num_rows($ostotil_tiltyyp_res) > 0) {

            mysql_data_seek($ostotil_tiltyyp_res, 0);

            // ensimmäinen rivi on ns. "oletusavainsana", ei haluta sitä
            $ostotil_tiltyyp_row = mysql_fetch_assoc($ostotil_tiltyyp_res);

            while ($ostotil_tiltyyp_row = mysql_fetch_assoc($ostotil_tiltyyp_res)) {

              if ($ostotil_tiltyyp_row['selite'] == $row['tilaustyyppi']) {
                $varastotila .= "<br /><font class='info'>".t($ostotil_tiltyyp_row['selitetark'])."</font>";
                break;
              }
            }
          }
          else {

            if ($row['tilaustyyppi'] == '1') {
              $varastotila .= "<br /><font class='info'>".t("Pikalähetys")."</font>";
            }
          }
        }

        echo "<td class='$class' valign='top'>".t("$laskutyyppi")."$tarkenne".t("$alatila")." $varastotila</td>";

        if (isset($worksheet)) {
          $worksheet->writeString($excelrivi, $ii, t("$laskutyyppi")."$tarkenne".t("$alatila")." $varastotila");
          $ii++;
        }
      }

      $excelrivi++;

      // tehdään aktivoi nappi.. kaikki mitä näytetään saa aktvoida, joten tarkkana queryn kanssa.
      if ($whiletoim == "" or $whiletoim == "SUPER" or $whiletoim == "SUPER_EITYOM" or $whiletoim == "SUPER_EILUONTITAPATYOM" or $whiletoim == "KESKEN" or $toim == "KESKEN_TAI_TOIMITETTAVISSA" or $toim == "LAVAKERAYS" or $toim == "TOSI_KESKEN" or $whiletoim == "EXTRANET" or $whiletoim == "JTTOIMITA" or $whiletoim == "LASKUTUSKIELTO"or (($whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] != "V")) {

        if (isset($pika_oikeu) and $pika_oikeu and !$rivi_oikeu) {
          $aputoim1 = "PIKATILAUS";
          $aputoim2 = "";

          $lisa1 = t("Pikatilaukseen");
          $lisa2 = "";
        }
        elseif (isset($pika_oikeu) and !$pika_oikeu and $rivi_oikeu) {
          $aputoim1 = "RIVISYOTTO";
          $aputoim2 = "";

          $lisa1 = t("Rivisyöttöön");
          $lisa2 = "";
        }
        else {
          $aputoim1 = "RIVISYOTTO";
          $aputoim2 = "PIKATILAUS";

          $lisa1 = t("Rivisyöttöön");
          $lisa2 = t("Pikatilaukseen");
        }
      }
      elseif (($whiletoim == "VALMISTUS" or $whiletoim == "VALMISTUSSUPER" or $whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] == "V" and $row["tilaustyyppi"] == "V") {
        $aputoim1 = "VALMISTAASIAKKAALLE";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif (($whiletoim == "VALMISTUS" or $whiletoim == "VALMISTUSSUPER" or $whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] == "V" and $row["tilaustyyppi"] != "V") {
        $aputoim1 = "VALMISTAVARASTOON";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($whiletoim == "MYYNTITILISUPER" or $whiletoim == "MYYNTITILITOIMITA") {
        $aputoim1 = "MYYNTITILI";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($whiletoim == "SIIRTOLISTASUPER") {
        $aputoim1 = "SIIRTOLISTA";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($whiletoim == "TARJOUSSUPER") {
        $aputoim1 = "TARJOUS";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($whiletoim == "ENNAKKO") {
        $aputoim1 = "ENNAKKO";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($whiletoim == "TYOMAARAYSSUPER") {
        $aputoim1 = "TYOMAARAYS";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($whiletoim == "OSTO" or $whiletoim == "OSTOSUPER") {
        $aputoim1 = "";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($whiletoim == "PROJEKTI") {
        if ($row["tila"] == "A") {
          $aputoim1 = "TYOMAARAYS";
        }
        elseif ($row["tila"] == "R") {
          $aputoim1 = "PROJEKTI";
        }
        else {
          $aputoim1 = "RIVISYOTTO";
        }

        $lisa1 = t("Rivisyöttöön");
      }
      elseif ($whiletoim == "REKLAMAATIO" or $whiletoim == "REKLAMAATIOSUPER" or $whiletoim == "TAKUU" or $whiletoim == "TAKUUSUPER") {
        $aputoim1 = "REKLAMAATIO";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "VASTAANOTA_REKLAMAATIO") {
        $aputoim1 = $whiletoim;
        $lisa1 = t("Vastaanota");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "ODOTTAA_SUORITUSTA") {
        $aputoim1 = "RIVISYOTTO";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      elseif ($toim == "TEHDASPALAUTUKSET" or $toim == 'SUPERTEHDASPALAUTUKSET') {
        $aputoim1 = "RIVISYOTTO";
        $lisa1 = t("Muokkaa");

        $aputoim2 = "";
        $lisa2 = "";
      }
      else {
        $aputoim1 = $whiletoim;
        $aputoim2 = "";

        $lisa1 = t("Muokkaa");
        $lisa2 = "";
      }

      // tehdään alertteja
      if ($row["tila"] == "L" and $row["alatila"] == "A") {
        $pitaako_varmistaa = t("Keräyslista on jo tulostettu! Oletko varma, että haluat vielä muokata tilausta?");
      }

      if ($row["tila"] == "G" and $row["alatila"] == "A") {
        $pitaako_varmistaa = t("Siirtolista on jo tulostettu! Oletko varma, että haluat vielä muokata siirtolistaa?");
      }

      // tehdään alertti jos sellanen ollaan määritelty
      $javalisa = "";

      if ($pitaako_varmistaa != "") {
        $javalisa = "onSubmit = \"return lahetys_verify('$pitaako_varmistaa')\"";
      }

      // Tarkastetaan riittääkö saldo keräyspäivänä.
      // Haetaan ensin tilauksen tilausrivit ja tarkistetaan jokaisen tuotteen saldot erikseen.
      if (!empty($yhtiorow["saldo_kasittely"]) and $toim == '' and $naytetaanko_saldot == 'kylla') {
        $_query = "SELECT tilausrivi.nimitys, tilausrivi.tuoteno, tilausrivi.tilkpl, tilausrivi.kerayspvm, tilausrivi.yksikko
                   FROM tilausrivi
                   JOIN tuote ON (tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno and tuote.ei_saldoa = '')
                   WHERE tilausrivi.yhtio='{$kukarow['yhtio']}'
                   AND tilausrivi.otunnus={$row['tunnus']}
                   AND tilausrivi.tyyppi='L'";
        $_result = pupe_query($_query);

        $riittaako_saldo = true;
        $puutteet = array();

        echo "<td>";

        while ($rivi = mysql_fetch_assoc($_result)) {
          list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($rivi["tuoteno"], '', '', '', '', '', '', '', '', $row['kerayspvm']);

          // saldo_myytavissa funktio ottaa huomioon kyseisen myyntitilauksen varaaman saldon.
          // Joten myyntitilauksen keräyspvm ylittäviin saldokyselyihin lisätään tilauksen vaarama saldo takaisin.
          if (strtotime($rivi['kerayspvm']) <= strtotime($row['kerayspvm'])) {
            $myytavissa = $myytavissa + $rivi['tilkpl']; // Lisätään myytavissa saldoon tämän tilauksen saldo
          }

          // Jos tilattu määrä on suurempi kuin myytävissä oleva määrä, niin lisätään tuote puutelistaan.
          if (($rivi['tilkpl']) > $myytavissa) {
            $puutteet[] = "{$rivi['tuoteno']} {$rivi['nimitys']} ({$myytavissa} {$rivi['yksikko']})";
            $riittaako_saldo = false;
          }
        }

        echo "<div id='div_{$row['tunnus']}' class='popup'>";
        echo t("Keräyspäivä") . ": {$row['kerayspvm']}<br>";

        foreach ($puutteet as $puute) {
          echo $puute . "<br>";
        }

        echo "</div>";

        if ($riittaako_saldo) {
          echo t("Kyllä");
        }
        else {
          echo t("Ei");
          echo " <img class='tooltip' id='{$row['tunnus']}' src='$palvelin2/pics/lullacons/info.png'>";
        }

        echo "</td>";
      }

      echo "<td class='back' nowrap>";

      $lopetus = "{$palvelin2}muokkaatilaus.php////toim={$toim}//asiakastiedot={$asiakastiedot}//viitetiedot={$viitetiedot}//limit={$limit}//etsi={$etsi}";

      if (isset($toimipaikka)) {
        $lopetus .= "//toimipaikka=$toimipaikka";
      }

      if ($toim == "LAVAKERAYS") {

        if (isset($toimitustapa)) {
          $lopetus .= "//toimitustapa=$toimitustapa";
        }
        if (isset($pv_rajaus)) {
          $lopetus .= "//pv_rajaus=$pv_rajaus";
        }
      }

      if ($whiletoim == "OSTO" or $whiletoim == "OSTOSUPER" or $whiletoim == "HAAMU") {
        echo "<form method='post' action='tilauskasittely/tilaus_osto.php' $javalisa>
            <input type='hidden' name='tee' value='AKTIVOI'>";
      }
      else {
        echo "<form method='post' class='myyntiformi' id='myyntiformi_{$row['tunnus']}' action='tilauskasittely/tilaus_myynti.php' $javalisa>";
      }

      //  Projektilla hypätään aina pääotsikolle..
      if ($whiletoim == "PROJEKTI") {
        echo "  <input type='hidden' name='projektilla' value='$row[tunnusnippu]'>";
      }

      echo "<input type='hidden' name='lopetus' value='$lopetus'>
            <input type='hidden' name='mista' value='muokkaatilaus'>
            <input type='hidden' name='toim' value='$aputoim1'>
            <input type='hidden' name='orig_tila' value='{$row["tila"]}'>
            <input type='hidden' name='orig_alatila' value='{$row["alatila"]}'>
            <input type='hidden' class='tilausnumero' name='tilausnumero' value='$row[tunnus]'>
            <input type='hidden' name='kaytiin_otsikolla' value='NOJOO!' />";

      if ($toim == "VASTAANOTA_REKLAMAATIO") {
        echo "  <input type='hidden' name='mista' value='vastaanota'>";
      }

      $_class = $whiletoim == "EXTRANET" ? "check_kesken" : "";

      if ($aputoim2 != "" and ($whiletoim == "" or $whiletoim == "SUPER" or $whiletoim == "SUPER_EITYOM" or $whiletoim == "SUPER_EILUONTITAPATYOM" or $whiletoim == "KESKEN" or $toim == "KESKEN_TAI_TOIMITETTAVISSA" or $toim == "LAVAKERAYS" or $toim == "TOSI_KESKEN" or $whiletoim == "EXTRANET" or $whiletoim == "JTTOIMITA" or $whiletoim == "LASKUTUSKIELTO" or (($whiletoim == "VALMISTUSMYYNTI" or $whiletoim == "VALMISTUSMYYNTISUPER") and $row["tila"] != "V"))) {
        echo "<input type='submit' class='{$_class}' name='$aputoim2' value='$lisa2' $button_disabled>";
      }

      echo "<input type='submit' class='{$_class}' name='$aputoim1' value='$lisa1' $button_disabled>";
      echo "</form></td>";

      if (((($whiletoim == "TARJOUS" or $whiletoim == "TARJOUSSUPER") and $deletarjous)
          or ($toim == 'SUPER' and $deletilaus)) and $kukarow["mitatoi_tilauksia"] == "") {

        echo "<td class='back'><form method='post' action='muokkaatilaus.php' onSubmit='return tarkista_mitatointi(1, \"{$whiletoim}\");'>";
        echo "<input type='hidden' name='toim' value='$whiletoim'>";
        echo "<input type='hidden' name='tee' value='MITATOI_TARJOUS'>";
        echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
        echo "<input type='hidden' name='kaytiin_otsikolla' value='NOJOO!'>";
        echo "<input type='submit' name='$aputoim1' value='".t("Mitätöi")."'>";
        echo "</form></td>";
      }

      //laitetaan tunnukset talteen mitatoi_tarjous_kaikki toiminnallisuutta varten
      $nakyman_tunnukset[] = $row['tunnus'];

      if ($whiletoim == "ENNAKKO" and in_array($yhtiorow["ennakkotilausten_toimitus"], array('M','K'))) {

        $toimitettavat_ennakot[] = $row["tunnus"];

        if ($yhtiorow["ennakkotilausten_toimitus"] == 'K') {
          $napin_teksti = t("Siirrä myyntitilaukseksi");
        }
        else {
          $napin_teksti = t("Toimita ennakkotilaus");
        }

        echo "<td class='back'><form method='post' action='muokkaatilaus.php' onSubmit='return verify();'>";
        echo "<input type='hidden' name='toim' value='$whiletoim'>";
        echo "<input type='hidden' name='tee' value='TOIMITA_ENNAKKO'>";
        echo "<input type='hidden' name='toimita_ennakko' value='$row[tunnus]'>";
        echo "<input type='hidden' name='kaytiin_otsikolla' value='NOJOO!'>";
        echo "<input type='submit' name='$aputoim1' value='{$napin_teksti}'>";
        echo "</form></td>";
      }
      echo "</tr>";
    }
  }

  echo "</table>";

  if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
    if ($toim == "LAVAKERAYS" and !empty($toimitustapa)) {
      echo "<input type='checkbox' id='lavakerays_valitsekaikki'> ".t("Valitse kaikki");

      echo "<br><br><form method='POST' id='keraa_kaikki_lavakerays_formi' name='keraa_kaikki_lavakerays_formi' action='muokkaatilaus.php' onSubmit='return tarkista_tulostus();'>";
      echo "<input type='hidden' name='toim' value='$toim' />";
      echo "<input type='hidden' name='toimitustapa' value='$toimitustapa' />";
      echo "<input type='hidden' name='tee' value='KERAA_KAIKKI_LAVAKERAYS' />";
      echo "<input type='hidden' id='lavakerays_keraykseen' name='lavakerays_keraykseen' value='' />";

      $query = "SELECT *
                FROM kirjoittimet
                WHERE yhtio  = '$kukarow[yhtio]'
                AND komento != 'EDI'
                ORDER by kirjoitin";
      $kirre = pupe_query($query);

      echo "<table>";
      echo "<tr>";
      echo "<th>".t("Keräyslistan tulostin")."</th>";
      echo "<td><select name='valittu_tulostin'>";
      echo "<option value=''>".t("Valitse tulostin")."</option>";

      if (empty($hb_kerayslista_tulostin)) {
        $hb_kerayslista_tulostin = isset($_COOKIE["hb_kerayslista_tulostin"]) ? $_COOKIE["hb_kerayslista_tulostin"] : "";
      }

      if (empty($hb_keraystarra_tulostin)) {
        $hb_keraystarra_tulostin = isset($_COOKIE["hb_keraystarra_tulostin"]) ? $_COOKIE["hb_keraystarra_tulostin"] : "";
      }

      while ($kirrow = mysql_fetch_array($kirre)) {
        $sel = '';

        if ($hb_kerayslista_tulostin == $kirrow['tunnus']) {
          $sel = "SELECTED";
        }
        elseif (($kirrow['tunnus'] == $kirjoitin and ($kukarow['kirjoitin'] == 0 or $lasku_yhtio_originaali != $kukarow["yhtio"])) or ($kirrow['tunnus'] == $kukarow['kirjoitin'])) {
          //tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
          $sel = "SELECTED";
        }

        echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
      }

      echo "</select></td></tr>";

      mysql_data_seek($kirre, 0);

      echo "<tr>";
      echo "<th>".t("Keräystarrojen tulostin")."</th>";
      echo "<td><select name='valittu_lavakeraystarra_tulostin'>";
      echo "<option value=''>".t("Valitse tulostin")."</option>";

      while ($kirrow = mysql_fetch_array($kirre)) {
        $sel = '';

        if ($hb_keraystarra_tulostin == $kirrow['tunnus']) {
          $sel = "SELECTED";
        }
        elseif (($kirrow['tunnus'] == $kirjoitin and ($kukarow['kirjoitin'] == 0 or $lasku_yhtio_originaali != $kukarow["yhtio"])) or ($kirrow['tunnus'] == $kukarow['kirjoitin'])) {
          //tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
          $sel = "SELECTED";
        }

        echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
      }

      echo "</select></td></tr></table><br>";

      echo "<input type='submit' id='lavakerays_siirra_keraykseen_nappi' name='lavakerays_siirra_keraykseen_nappi' value='".t("Siirrä kaikki valitut tilaukset keräykseen")."'/>";
      echo "</form><br>";
    }

    if (is_array($sumrow)) {
      echo "<br><table>";
      echo "<tr><th>".t("Arvo yhteensä")." ($sumrow[kpl] ".t("kpl")."): </th><td align='right'>$sumrow[arvo] $yhtiorow[valkoodi]</td></tr>";

      if (isset($sumrow["jt_arvo"]) and $sumrow["jt_arvo"] != 0) {
        echo "<tr><th>".t("Muu tilauskanta").":</th><td align='right'>$sumrow[jt_arvo] $yhtiorow[valkoodi]</td></tr>";


        echo "<tr><th>".t("Yhteensä")."</th><td align='right'>".sprintf('%.2f', $sumrow["jt_arvo"]+$sumrow["arvo"])." $yhtiorow[valkoodi]</td></tr>";

        echo "<tr><td class='back'><br></td></tr>";
      }

      echo "<tr><th>".t("Summa yhteensä").": </th><td align='right'>$sumrow[summa] $yhtiorow[valkoodi]</td></tr>";

      if (isset($sumrow["jt_summa"]) and $sumrow["jt_summa"] != 0) {
        echo "<tr><th>".t("Muu tilauskanta").":</th><td align='right'>$sumrow[jt_summa] $yhtiorow[valkoodi]</td></tr>";

        echo "<tr><th>".t("Yhteensä")."</th><td align='right'>".sprintf('%.2f', $sumrow["jt_summa"]+$sumrow["summa"])." $yhtiorow[valkoodi]</td></tr>";
      }

      echo "</table>";
    }

    if (mysql_num_rows($result) == 50) {
      // Näytetään muuten vaan sopivia tilauksia
      echo "<br>
          <form method='post'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='etsi' value='$etsi'>
          <input type='hidden' name='asiakastiedot' value='$asiakastiedot'>
          <input type='hidden' name='limit' value='NO'>
          <input type='hidden' name='kaytiin_otsikolla' value='NOJOO!'>
          <input type='hidden' name='pv_rajaus' value='{$pv_rajaus}'>
          <input type='hidden' name='toimipaikka' value='{$toimipaikka}'>
          <table>
          <tr><th>".t("Listauksessa näkyy 50 ensimmäistä")." $otsikko.</th>
          <td class='back'><input type='submit' value = '".t("Näytä kaikki")."'></td></tr>
          </table>
          </form>";
    }

    if (isset($worksheet)) {

      $excelnimi = $worksheet->close();

      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='toim' value='{$toim}'>";
      echo "<input type='hidden' name='kaunisnimi' value='Tilauslista.xlsx'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
      echo "<input type='hidden' name='kaytiin_otsikolla' value='NOJOO!'>";
      echo "<a name='tallennaexcel' />";
      echo "<br><table>";
      echo "<tr><th>".t("Tallenna").":</th>";
      echo "<td class='back'><input type='submit' value='".t("Tallenna Excel")."'></td></tr>";
      echo "</table></form><br>";
    }
    else {
      echo "<br />";
      echo "<form method='post' action='muokkaatilaus.php#tallennaexcel'>";
      echo "<input type='hidden' name='toim' value='{$toim}'>";
      echo "<input type='hidden' name='etsi' value='{$etsi}'>";
      echo "<input type='hidden' name='asiakastiedot' value='{$asiakastiedot}'>";
      echo "<input type='hidden' name='limit' value='{$limit}'>";
      echo "<input type='hidden' name='kaytiin_otsikolla' value='NOJOO!'>";
      echo "<input type='hidden' name='pv_rajaus' value='{$pv_rajaus}'>";
      echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}'>";
      echo "<table>";
      echo "<tr>";
      echo "<tr><th>".t("Excel").":</th>";
      echo "<td class='back'>";
      echo "<input type='submit' name='tee_excel' value='", t("Tee Excel"), "' />";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      echo "</form>";
    }

    if ($toim == 'TARJOUS' and tarkista_oikeus('tilaus_myynti.php', 'TARJOUS', 1)) {
      $tunnukset = implode(',', $nakyman_tunnukset);

      echo "<form method='POST' name='mitatoi_kaikki_formi' action='muokkaatilaus.php' onSubmit='return tarkista_mitatointi(".count($nakyman_tunnukset).");'>";
      echo "<input type='hidden' name='toim' value='$toim' />";
      echo "<input type='hidden' name='tee' value='MITATOI_TARJOUS_KAIKKI' />";
      echo "<input type='hidden' name='tunnukset' value='($tunnukset)' />";
      echo "<input type='hidden' name='kaytiin_otsikolla' value='NOJOO!'>";
      echo "<input type='submit' value='".t("Mitätöi kaikki näkymän tarjoukset")."'/>";
      echo "</form>";
    }

    if ($whiletoim == "ENNAKKO" and in_array($yhtiorow["ennakkotilausten_toimitus"], array('M','K')) and count($toimitettavat_ennakot) > 0) {

      if ($yhtiorow["ennakkotilausten_toimitus"] == 'K') {
        $napin_teksti = t("Siirrä kaikki yllälistatut myyntitilauksiksi");
      }
      else {
        $napin_teksti = t("Toimita kaikki yllälistatut ennakkotilaukset");
      }

      echo "<br><form method='post' action='muokkaatilaus.php' onSubmit='return verify();'>";
      echo "<input type='hidden' name='toim' value='$whiletoim'>";
      echo "<input type='hidden' name='tee' value='TOIMITA_ENNAKKO'>";
      echo "<input type='hidden' name='toimita_ennakko' value='".implode(",", $toimitettavat_ennakot)."'>";
      echo "<input type='hidden' name='kaytiin_otsikolla' value='NOJOO!'>";
      echo "<table><tr><th>".t("Toimita")."</th>";
      echo "<td><input type='submit' name='$aputoim1' value='{$napin_teksti}'></td>";
      echo "</table></form>";
    }
  }
}
else {
  echo t("Ei tilauksia")."...<br>";
}

if (strpos($_SERVER['SCRIPT_NAME'], "muokkaatilaus.php") !== FALSE) {
  require "inc/footer.inc";
}
