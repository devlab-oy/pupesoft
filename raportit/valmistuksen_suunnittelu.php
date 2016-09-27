<?php

// Ei käytetä pakkausta
$compression = FALSE;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') {
    $lataa_tiedosto = 1;
  }
  if (isset($_POST["kaunisnimi"]) and $_POST["kaunisnimi"] != '') {
    $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
  }
}

require "../inc/parametrit.inc";
require 'valmistuslinjat.inc';

//Nämä ovat $lopetus varten. lopetus-muuttujaan serialisoidaan ja base64-enkoodataan arrayt
//ja ne pitää reverttaa tässä kohtaa kun tuotekyselystä tullaan takaisin.
if (isset($mul_try) and is_string($mul_try)) {
  $mul_try = unserialize(base64_decode($mul_try));
}
else {
  $mul_try = isset($mul_try) ? $mul_try : array();
}
if (isset($mul_osasto) and is_string($mul_osasto)) {
  $mul_osasto = unserialize(base64_decode($mul_osasto));
}
else {
  $mul_osasto = isset($mul_osasto) ? $mul_osasto : array();
}
if (isset($mul_tme) and is_string($mul_tme)) {
  $mul_tme = unserialize(base64_decode($mul_tme));
}
else {
  $mul_tme = isset($mul_tme) ? $mul_tme : array();
}
if (isset($multi_valmistuslinja) and is_string($multi_valmistuslinja)) {
  $multi_valmistuslinja = unserialize(base64_decode($multi_valmistuslinja));
}
else {
  $multi_valmistuslinja = isset($multi_valmistuslinja) ? $multi_valmistuslinja : array();
}
if (isset($multi_status) and is_string($multi_status)) {
  $multi_status = unserialize(base64_decode($multi_status));
}
else {
  $multi_status = isset($multi_status) ? $multi_status : array();
}

$ehdotetut_valmistukset = isset($ehdotetut_valmistukset) ? $ehdotetut_valmistukset : '';
$kohde_varasto = isset($kohde_varasto) ? $kohde_varasto : '';
$lahde_varasto = isset($lahde_varasto) ? $lahde_varasto : '';
$ehdotusnappi = isset($ehdotusnappi) ? $ehdotusnappi : '';
$tee = isset($tee) ? $tee : '';
$lisa = isset($lisa) ? $lisa : '';
$tilaustuotteiden_kasittely = isset($tilaustuotteiden_kasittely) ? $tilaustuotteiden_kasittely : 'A';

if (isset($tee) and $tee == 'lataa_tiedosto') {
  $filepath = "/tmp/".$tmpfilenimi;
  if (file_exists($filepath)) {
    readfile($filepath);
    unlink($filepath);
  }
  else {
    echo "<font class='error'>".t("Tiedostoa ei ole olemassa")."</font>";
  }
  exit;
}

?>
<style>
  .raaka_aineet_not_hidden {

  }
  .raaka_aineet_hidden {
    display: none;
  }
</style>
<script language="javascript">
$(document).ready(function() {
    $("#vain_numeroita").keydown(function(event) {
        // sallitaan backspace (8) ja delete (46)
        if ( event.keyCode == 46 || event.keyCode == 8 ) {
            // anna sen vaan tapahtua...
        }
        else {
            // 48-57 on norminäppäimistön numerot, numpad numerot on 96-105, piste on 190 ja pilkku 188
            if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 ) && event.keyCode != 188 && event.keyCode != 190) {
                event.preventDefault();
            }
        }
    });
  $("a.toggle_rivit").click(function(event) {
    event.preventDefault();
    $("tr.togglettava_rivi_"+$(this).attr("id")).toggle();
  });

  bind_ei_exceliin_checkbox();
  bind_raaka_aine_saldo_tr_toggle();
});

function bind_ei_exceliin_checkbox() {
  $('.ei_exceliin').on('click', function() {
    //jos riviä ei haluta tulostettavan exceliin, poistetaan exceliin inputista nimi, jolloin se ei lähde requestin mukana.
    if ($(this).is(':checked')) {
      $(this).parent().find('.exceliin').removeAttr('name');
    }
    else {
      var tuoteno = $(this).val();
      $(this).parent().find('.exceliin').attr('name', 'exceliin['+tuoteno+']');
    }
  });
}

function bind_raaka_aine_saldo_tr_toggle() {
  $('.raaka_aine_toggle').on('click', function(event) {
    event.preventDefault();

    var togglettava_rivi_id = $(this).attr('data');

    var $raaka_aine_tr = $(this).parent().parent().parent().find('.raaka_aineet_'+togglettava_rivi_id);

    if ($raaka_aine_tr.hasClass('raaka_aineet_not_hidden')) {
      $raaka_aine_tr.addClass('raaka_aineet_hidden');
      $raaka_aine_tr.removeClass('raaka_aineet_not_hidden');
    }
    else {
      $raaka_aine_tr.addClass('raaka_aineet_not_hidden');
      $raaka_aine_tr.removeClass('raaka_aineet_hidden');
    }
  });
}
</script>
<?php
echo "<font class='head'>".t("Valmistuksien suunnittelu")."</font><hr>";

// org_rajausta tarvitaan yhdessä selectissä joka triggeröi taas toisen asian.
$org_rajaus = isset($abcrajaus) ? $abcrajaus : '';
$abcrajaus = isset($abcrajaus) ? $abcrajaus : '';

if ($abcrajaus != '') {
  list($abcrajaus, $abcrajaustapa) = explode("##", $abcrajaus);
}

if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";

list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

$valmistuslinjat = hae_valmistuslinjat();

// Ehdotetaan oletuksena ehdotusta ensikuun valmistuksille sekä siitä plus 3 kk
if (!isset($ppa1)) $ppa1 = date("d", mktime(0, 0, 0, date("m")+1, 1, date("Y")));
if (!isset($kka1)) $kka1 = date("m", mktime(0, 0, 0, date("m")+1, 1, date("Y")));
if (!isset($vva1)) $vva1 = date("Y", mktime(0, 0, 0, date("m")+1, 1, date("Y")));
if (!isset($ppl1)) $ppl1 = date("d", mktime(0, 0, 0, date("m")+4, 0, date("Y")));
if (!isset($kkl1)) $kkl1 = date("m", mktime(0, 0, 0, date("m")+4, 0, date("Y")));
if (!isset($vvl1)) $vvl1 = date("Y", mktime(0, 0, 0, date("m")+4, 0, date("Y")));

// Päivämäärätarkistus
if (!checkdate($kka1, $ppa1, $vva1)) {
  echo "<font class='error'>".t("Virheellinen alkupäivä!")."</font><br><br>";
  $tee = "";
  $ehdotusnappi = "";
}
else {
  $nykyinen_alku  = date("Y-m-d", mktime(0, 0, 0, $kka1, $ppa1, $vva1));
}

if (!checkdate($kkl1, $ppl1, $vvl1)) {
  echo "<font class='error'>".t("Virheellinen loppupäivä!")."</font><br><br>";
  $tee = "";
  $ehdotusnappi = "";
}
else {
  $nykyinen_loppu  = date("Y-m-d", mktime(0, 0, 0, $kkl1, $ppl1, $vvl1));
}

if (isset($nykyinen_alku) and isset($nykyinen_loppu) and $nykyinen_alku > $nykyinen_loppu) {
  echo "<font class='error'>".t("Virheelliset kaudet!")." $nykyinen_alku > $nykyinen_loppu</font><br>";
  $tee = "";
  $ehdotusnappi = "";
}

$lopetus = "{$palvelin2}raportit/valmistuksen_suunnittelu.php////toim={$toim}//abcrajaus={$abcrajaus}//ehdotetut_valmistukset={$ehdotetut_valmistukset}
  //ppa1={$ppa1}//kka1={$kka1}//vva1={$vva1}//ppl1={$ppl1}//kkl1={$kkl1}//vvl1={$vvl1}
  //kohde_varasto={$kohde_varasto}//lahde_varasto={$lahde_varasto}//multi_status=".base64_encode(serialize($multi_status))."
  //ehdotusnappi={$ehdotusnappi}//tee={$tee}//mul_try=".base64_encode(serialize($mul_try))."
  //mul_osasto=".base64_encode(serialize($mul_osasto))."//mul_tme=".base64_encode(serialize($mul_tme))."//multi_valmistuslinja=".base64_encode(serialize($multi_valmistuslinja));

// Muuttujia
$ytunnus = isset($ytunnus) ? trim($ytunnus) : "";
$toimittajaid = isset($toimittajaid) ? trim($toimittajaid) : "";

// Tämä palauttaa tuotteen valmistuksen tiedot
function teerivi($tuoteno, $valittu_toimittaja, $abc_rajaustapa) {

  // Kukarow ja päivämäärät globaaleina
  global $kukarow, $nykyinen_alku, $nykyinen_loppu, $ryhmanimet, $tilaustuotteiden_kasittely;

  // Tehdään kaudet päivämääristä
  $alku_kausi  = substr(str_replace("-", "", $nykyinen_alku), 0, 6);
  $loppu_kausi = substr(str_replace("-", "", $nykyinen_loppu), 0, 6);

  // Haetaan tuotteen ABC luokka
  $query = "SELECT abc_aputaulu.luokka
            FROM abc_aputaulu
            WHERE abc_aputaulu.yhtio = '{$kukarow["yhtio"]}'
            AND abc_aputaulu.tyyppi  = '{$abc_rajaustapa}'
            AND abc_aputaulu.tuoteno = '{$tuoteno}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $abcluokka = isset($ryhmanimet[$row['luokka']]) ? $ryhmanimet[$row['luokka']] : t("Ei tiedossa");

  // Haetaan tuotteen varastosaldo
  $query = "SELECT ifnull(sum(tuotepaikat.saldo),0) saldo
            FROM tuotepaikat
            WHERE tuotepaikat.yhtio = '{$kukarow["yhtio"]}'
            AND tuotepaikat.tuoteno = '{$tuoteno}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $varastosaldo = $row['saldo'];

  // Haetaan tuotteen vuosikulutus (= myynti)
  $query = "SELECT ifnull(sum(tilausrivi.kpl), 0) vuosikulutus
            FROM tilausrivi
            WHERE tilausrivi.yhtio        = '{$kukarow["yhtio"]}'
            AND tilausrivi.tyyppi         = 'L'
            AND tilausrivi.tuoteno        = '{$tuoteno}'
            AND tilausrivi.toimitettuaika >= DATE_SUB(now(), INTERVAL 1 YEAR)";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $vuosikulutus = $row['vuosikulutus'];

  // Haetaan tuotteen valmistuksessa, ostettu, varattu sekä ennakkotilattu määrä
  $query = "SELECT
            ifnull(sum(if(tilausrivi.tyyppi = 'O', tilausrivi.varattu, 0)), 0) tilattu,
            ifnull(sum(if(tilausrivi.tyyppi = 'L', tilausrivi.varattu, 0)), 0) varattu,
            ifnull(sum(if(tilausrivi.tyyppi = 'E' and tilausrivi.var != 'O', tilausrivi.varattu, 0)), 0) ennakko,
            ifnull(sum(if(tilausrivi.tyyppi IN ('V','W'), tilausrivi.varattu, 0)), 0) valmistuksessa
            FROM tilausrivi
            WHERE tilausrivi.yhtio  = '{$kukarow["yhtio"]}'
            AND tilausrivi.tyyppi   IN ('O', 'L', 'E', 'V', 'W')
            AND tilausrivi.tuoteno  = '{$tuoteno}'
            AND tilausrivi.varattu != 0";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $tilattu = $row['tilattu'];
  $varattu = $row['varattu'];
  $ennakko = $row['ennakko'];
  $valmistuksessa = $row['valmistuksessa'];

  // Haetaan tuotteen toimittajatiedot
  $query = "SELECT if(tuotteen_toimittajat.toimitusaika > 0, tuotteen_toimittajat.toimitusaika, toimi.oletus_toimaika) toimitusaika,
            if(tuotteen_toimittajat.pakkauskoko > 0, tuotteen_toimittajat.pakkauskoko, 1) pakkauskoko,
            toimi.ytunnus,
            toimi.nimi,
            toimi.tunnus
            FROM tuotteen_toimittajat
            JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus and toimi.tunnus = '{$valittu_toimittaja}')
            WHERE tuotteen_toimittajat.yhtio = '{$kukarow["yhtio"]}'
            AND tuotteen_toimittajat.tuoteno = '{$tuoteno}'
            ORDER BY if(jarjestys = 0, 9999, jarjestys)
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {
    $toimittajarow = mysql_fetch_assoc($result);
  }
  else {
    // Toimittajaa ei löydy -> alustetaan defaulttiarvot (lisää tähän jos muutat queryä)
    $toimittajarow = array(
      "toimitusaika" => 0,
      "pakkauskoko" => 1,
      "toimittaja" => "",
      "nimi" => t("Ei toimittajaa"),
      "tunnus" => 0,
    );
  }

  // Haetaan tuotteen status
  $query = "SELECT tuote.status
            FROM tuote
            WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
            AND tuote.tuoteno = '{$tuoteno}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);
  $tuote_status = $row['status'];

  // Haetaan budjetoitu myynti
  $params = array(
    'tuoteno'                    => $tuoteno,
    'pvm_alku'                   => $nykyinen_alku,
    'pvm_loppu'                  => $nykyinen_loppu,
    'tilaustuotteiden_kasittely' => $tilaustuotteiden_kasittely,
    'tuote_status'               => $tuote_status,
  );

  list($budjetoitu_myynti, $budjetin_peruste) = tuotteen_budjetoitu_myynti($params);

  // Lasketaan reaalisaldo
  $reaalisaldo = $varastosaldo + $tilattu + $valmistuksessa - $varattu - $ennakko;

  // Lasketaan riittopäivät
  $paivakulutus = round($vuosikulutus / 240, 6);
  $riittopv = ($paivakulutus == 0) ? t("Ei tiedossa") : floor($reaalisaldo / $paivakulutus);

  // Toimitustuotteilla määräennuste on suoraan budjetoitu myynti
  // Mutta vain jos ollaan valittu: A tai C
  // 'A' - "Tilaustuotteiden määräennuste on jälkitoimitusrivit"
  // 'B' - "Tilaustuotteiden määräennuste on budjetti/myynti"
  // 'C' - "Tilaustuotteiden määräennuste on jälkitoimitusrivit + budjetti/myynti"
  if ($tuote_status == 'T' and ($tilaustuotteiden_kasittely == 'A' or $tilaustuotteiden_kasittely == 'C')) {
    $maaraennuste = $budjetoitu_myynti;
    $paivakulutus = t("ei käytössä");
    $toimittajarow['toimitusaika'] = t("ei käytössä");
  }
  else {
    // Lasketaan määräennuste (paljon kuluu toimittajan toimitusajan aikana + arvioitu myynti)
    $maaraennuste = ($paivakulutus * $toimittajarow['toimitusaika']) + $budjetoitu_myynti;
  }

  // Lasketaan paljon kannattaisi valmistaa
  $valmistussuositus = round($maaraennuste - $reaalisaldo);

  // Pyöristetään suositus ylöspäin seuraavaan pakkauskokoon
  $valmistusmaara = round($valmistussuositus / $toimittajarow['pakkauskoko']) * $toimittajarow['pakkauskoko'];

  // Palautettava array
  $tuoterivi = array();
  $tuoterivi['reaalisaldo']    = $reaalisaldo;
  $tuoterivi['varastosaldo']    = $varastosaldo;
  $tuoterivi['tilattu']      = $tilattu;
  $tuoterivi['valmistuksessa']  = $valmistuksessa;
  $tuoterivi['varattu']      = $varattu;
  $tuoterivi['ennakko']      = $ennakko;
  $tuoterivi['budjetoitu_myynti'] = $budjetoitu_myynti;
  $tuoterivi['vuosikulutus']    = $vuosikulutus;
  $tuoterivi['paivakulutus']    = $paivakulutus;
  $tuoterivi['riittopv']       = $riittopv;
  $tuoterivi['maaraennuste']    = $maaraennuste;
  $tuoterivi['toimitusaika']    = $toimittajarow['toimitusaika'];
  $tuoterivi['valmistussuositus'] = $valmistussuositus;
  $tuoterivi['pakkauskoko']    = $toimittajarow['pakkauskoko'];
  $tuoterivi['valmistusmaara']   = $valmistusmaara;
  $tuoterivi['abcluokka']      = $abcluokka;
  $tuoterivi['budjetin_peruste']  = $budjetin_peruste;

  return $tuoterivi;
}

// Jos saadaan muut parametrit tehdään niistä muuttujat
if (isset($muutparametrit)) {
  foreach (explode("##", $muutparametrit) as $muutparametri) {
    list($a, $b) = explode("=", $muutparametri);
    ${$a} = $b;
  }
  $tee = "";
}

// Toimittajahaku
if ($ytunnus != "" and $toimittajaid == "") {

  // Tehdään muut parametrit
  $muutparametrit = "";
  unset($_POST["toimittajaid"]);

  foreach ($_POST as $key => $value) {
    $muutparametrit .= $key."=".$value."##";
  }

  require "inc/kevyt_toimittajahaku.inc";

  if ($toimittajaid == 0) {
    $tee = "ÄLÄMEEMIHINKÄÄN";
  }
  else {
    $tee = "";
  }
}

if (isset($tee) and $tee == "TEE_VALMISTUKSET") {

  $edellinen_valmistuslinja = "X";

  // Sortataan array uudestaan, jos käyttäjä on vaihtanut valmistuslinjoja
  // Sortataan 2 dimensoinen array. Pitää ensiksi tehdä sortattavista keystä omat arrayt
  $apusort_jarj0 = $apusort_jarj1 = array();

  foreach ($valmistettavat_tuotteet as $apusort_key => $apusort_row) {
    $apusort_jarj0[$apusort_key] = $apusort_row['valmistuslinja'];
    $apusort_jarj1[$apusort_key] = $apusort_row['riittopv'];
  }

  // Sortataan by valmistuslinja, riittopv
  array_multisort($apusort_jarj0, SORT_ASC, $apusort_jarj1, SORT_ASC, $valmistettavat_tuotteet);

  $lahde_varasto = mysql_real_escape_string($lahde_varasto);
  $kohde_varasto = mysql_real_escape_string($kohde_varasto);
  $valmistus_ajankohta = mysql_real_escape_string($valmistus_ajankohta);

  foreach ($valmistettavat_tuotteet as $tuoterivi) {

    $maara = (float) $tuoterivi["valmistusmaara"];
    $tuoteno = mysql_real_escape_string($tuoterivi["tuoteno"]);
    $valmistuslinja = mysql_real_escape_string($tuoterivi["valmistuslinja"]);
    $vakisin_hyvaksy = (isset($tuoterivi["hyvaksy"]) and $tuoterivi["hyvaksy"] != "") ? "H" : "";

    // Oikellisuustarkastus hoidetaan javascriptillä, ei voi tulla kun numeroita!
    if ($maara != 0) {

      if ($edellinen_valmistuslinja != $valmistuslinja) {

        $aquery = "SELECT *
                   FROM varastopaikat
                   WHERE yhtio = '{$kukarow["yhtio"]}'
                   and tunnus  = '{$kohde_varasto}'";
        $vtresult = pupe_query($aquery);
        $vtrow = mysql_fetch_array($vtresult);

        $query = "INSERT INTO lasku SET
                  yhtio         = '{$kukarow["yhtio"]}',
                  yhtio_nimi    = '{$yhtiorow["nimi"]}',
                  yhtio_osoite  = '{$yhtiorow["osoite"]}',
                  yhtio_postino = '{$yhtiorow["postino"]}',
                  yhtio_postitp = '{$yhtiorow["postitp"]}',
                  yhtio_maa     = '{$yhtiorow["maa"]}',
                  maa           = '{$vtrow["maa"]}',
                  nimi          = '{$vtrow["nimitys"]}',
                  nimitark      = '{$vtrow["nimi"]}',
                  osoite        = '{$vtrow["osoite"]}',
                  postino       = '{$vtrow["postino"]}',
                  postitp       = '{$vtrow["postitp"]}',
                  ytunnus       = '{$vtrow["nimitys"]}',
                  toimaika      = '$valmistus_ajankohta',
                  kerayspvm     = '$valmistus_ajankohta',
                  laatija       = '{$kukarow["kuka"]}',
                  luontiaika    = now(),
                  tila          = 'V',
                  kohde         = '{$valmistuslinja}',
                  varasto       = '{$lahde_varasto}',
                  clearing      = '{$kohde_varasto}',
                  tilaustyyppi  = 'W',
                  liitostunnus  = '9999999999'";
        $result = pupe_query($query);
        $otunnus = mysql_insert_id($GLOBALS["masterlink"]);

        $query = "SELECT *
                  FROM lasku
                  WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
                  AND lasku.tunnus  = '{$otunnus}'";
        $result = pupe_query($query);
        $laskurow = mysql_fetch_assoc($result);

        echo "<font class='message'>".t("Valmistus")." $otunnus ".t("luotu").".</font><br>";

        $edellinen_valmistuslinja = $valmistuslinja;
      }

      $query = "SELECT *
                FROM tuote
                WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
                AND tuote.tuoteno = '$tuoteno'";
      $result = pupe_query($query);
      $trow = mysql_fetch_assoc($result);

      $trow        = $trow;            // jossa on tuotteen kaikki tiedot
      $rivinumero      = "";              // kentässä on joko tilaajan rivinumero tai konserninsisäisissä kaupoissa sisäinen toimittajanumero
      $laskurow      = $laskurow;          // jossa on laskun kaikki tiedot
      $kukarow["kesken"]  = $otunnus;            // jossa on käyttäjällä keskenoleva tilausnumero
      $kpl        = $maara;            // jossa on tilattu kappalemäärä
      $tuoteno      = $trow["tuoteno"];        // jossa on tilattava tuotenumero
      $toimaika      = $laskurow["toimaika"];    // arvioitu toimitusaika
      $kerayspvm      = $laskurow["toimaika"];    // toivottu keräysaika
      $hinta        = "";              // käyttäjän syöttämä hinta
      $netto        = "";              // käyttäjän syöttämä netto
      $ale        = "";              // käyttäjän syöttämä ale (generoidaan yhtiön parametreistä)
      $ale2        = "";              // käyttäjän syöttämä ale2 (generoidaan yhtiön parametreistä)
      $ale3        = "";              // käyttäjän syöttämä ale3 (generoidaan yhtiön parametreistä)
      $var        = $vakisin_hyvaksy;        // H,J,P varrit
      $varasto      = "";              // myydään vain tästä/näistä varastosta
      $paikka        = "";              // myydään vain tältä paikalta
      $rivitunnus      = "";              // tietokannan tunnus jolle rivi lisätään
      $rivilaadittu    = "";              // vanhan rivin laadittuaika, säilytetään se
      $korvaavakielto    = "";              // Jos erisuuri kuin tyhjä niin ei myydä korvaavia
      $jtkielto      = "";              // Jos erisuuri kuin tyhjä niin ei laiteta JT:Seen
      $perhekielto    = "";              // Jos erisuuri kuin tyhjä niin ei etsitä ollenkaan perheitä
      $varataan_saldoa  = "";              // Jos == EI niin ei varata saldoa (tietyissä keisseissä), tai siis ei ainakan tehdä saldotsekkiä
      $kutsuja      = "";              // Kuka tätä skriptiä kutsuu
      $myy_sarjatunnus  = "";              // Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
      $osto_sarjatunnus  = "";              // Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
      $jaksotettu      = "";              // Kuuluuko tilausrivi mukaan jaksotukseen
      $perheid      = "";              // Tuoteperheen perheid
      $perheid2      = "";              // Lisävarusteryhmän perheid2
      $orvoteikiinnosta  = "";              // Meitä ei kiinnosta orvot jos tämä ei ole tyhjä.
      $osatoimkielto    = "";              // Jos saldo ei riitä koko riville niin ei lisätä riviä ollenkaan
      $olpaikalta      = "";              // pakotetaan myymään oletuspaikalta
      $tuotenimitys    = "";              // tuotteen nimitys jos nimityksen syötö on yhtiöllä sallittu
      $tuotenimitys_force  = "";              // tuotteen nimitys muutetaan systemitasolla

      require "tilauskasittely/lisaarivi.inc";
    }
  }

  echo "<br><br>";
  $tee = "";
}

if (isset($tee) and $tee == "GENEROI_EXCEL") {
  $excel_rivit = array();
  foreach ($_REQUEST['exceliin'] as $excel_rivi) {
    $excel_rivi = unserialize(base64_decode($excel_rivi));

    $excel_rivit[] = $excel_rivi;
  }

  $header_values = array(
    'tuoteno'           => array(
      'header' => t('Tuotenumero'),
      'order'   => 10
    ),
    'nimitys'           => array(
      'header' => t('Nimitys'),
      'order'   => 20
    ),
    'sisartuote'         => array(
      'header' => t('Sisartuotteet'),
      'order'   => 30
    ),
    'abcluokka'           => array(
      'header' => t('ABC-luokka'),
      'order'   => 40
    ),
    'reaalisaldo'         => array(
      'header' => t('Reaalisaldo'),
      'order'   => 50
    ),
    'valmistuksessa'       => array(
      'header' => t('Valmistuksessa'),
      'order'   => 60
    ),
    'riittopv'           => array(
      'header' => t('Riitto pv'),
      'order'   => 70
    ),
    'raakaaine_riitto'     => array(
      'header' => t('Raaka-aine riitto'),
      'order'   => 80
    ),
    'vuosikulutus'         => array(
      'header' => t('Vuosikulutus'),
      'order'   => 90
    ),
    'valmistuslinja'       => array(
      'header' => t('Valmistuslinja'),
      'order'   => 100
    ),
    'pakkauskoko'         => array(
      'header' => t('Pakkauskoko'),
      'order'   => 110
    ),
    'valmistussuositus'       => array(
      'header' => t('Valmistussuositus'),
      'order'   => 120
    ),
    'valmistusmaara'       => array(
      'header' => t('Valmistusmaara'),
      'order'   => 125
    ),
    'valmistusaika_sekunneissa'   => array(
      'header' => t('Valmistusaika (sek)'),
      'order'   => 130
    ),
    'valmistusaika'         => array(
      'header' => t('Valmistusaika yht.'),
      'order'   => 140
    ),
    'varaus_sekunneissa'     => array(
      'header' => t('Kumulatiivinen aika'),
      'order'   => 150
    ),
    'varaus_paivissa'       => array(
      'header' => t('Päivä'),
      'order'   => 160
    ),
  );
  $force_to_string = array(
    'tuoteno'
  );

  $excel_filename = generoi_excel_tiedosto($excel_rivit, $header_values, $force_to_string);

  echo_tallennus_formi($excel_filename, 'Valmistusraportti');
  unset($ehdotusnappi);
}

// Tehdään raportti
if (isset($ehdotusnappi) and $ehdotusnappi != "") {

  $tuote_where                = ""; // tuote-rajauksia
  $tuote_valmistuslinja_where = ""; // tuote valmistuslinja wherelle tarvitaan oma muuttuja
  $tuote_samankaltainen_where = ""; // samankaltaisille tuotteille rajauksia
  $toimittaja_join            = ""; // toimittaja-rajauksia
  $toimittaja_select          = ""; // toimittaja-rajauksia
  $lasku_where                = ""; // lasku-rajauksia
  $abc_join                   = ""; // abc-rajauksia
  $toggle_counter             = 0;

  if ($ehdotetut_valmistukset == 'valmistuslinjoittain') {
    $valmistukset_yhteensa = array();

    foreach ($valmistuslinjat as $valmistuslinja) {

      // Jos ollaan rajattu valmistuslinjoja, ei alusteta turhia linjoja arrayseen
      if (isset($multi_valmistuslinja) and count($multi_valmistuslinja) > 0 and array_search($valmistuslinja['selite'], $multi_valmistuslinja) === FALSE) {
        continue;
      }

      $valmistukset_yhteensa[$valmistuslinja['selite']] = array(
        'valmistuksessa' => 0,
        'valmistusmaara' => 0,
        'yhteensa_kpl' => 0,
        'valmistusaika_sekunneissa' => 0,
      );
    }

    // Jos ei olla rajattu valmistuslinjoja, tehdään myös "ei valmistuslinjaa" initialize
    if (!isset($multi_valmistuslinja) or count($multi_valmistuslinja) == 0) {
      $valmistukset_yhteensa[''] = array(
        'valmistuksessa' => 0,
        'valmistusmaara' => 0,
        'yhteensa_kpl' => 0,
        'valmistusaika_sekunneissa' => 0,
      );
    }
  }

  if (isset($mul_osasto) and count($mul_osasto) > 0) {
    $tuote_where .= " and tuote.osasto in (".implode(",", $mul_osasto).")";
    $tuote_samankaltainen_where .= " and samankaltainen_tuote.osasto in (".implode(",", $mul_osasto).")";
  }

  if (isset($mul_try) and count($mul_try) > 0) {
    $tuote_where .= " and tuote.try in (".implode(",", $mul_try).")";
    $tuote_samankaltainen_where .= " and samankaltainen_tuote.try in (".implode(",", $mul_try).")";
  }

  if (isset($mul_tme) and count($mul_tme) > 0) {
    $tuote_where .= " and tuote.tuotemerkki in ('".implode("','", $mul_tme)."')";
    $tuote_samankaltainen_where .= " and samankaltainen_tuote.tuotemerkki in ('".implode("','", $mul_tme)."')";
  }

  if (isset($multi_valmistuslinja) and count($multi_valmistuslinja) > 0) {
    $tuote_valmistuslinja_where .= " and tuote.valmistuslinja in ('".implode("','", $multi_valmistuslinja)."')";
    $tuote_samankaltainen_where .= " and samankaltainen_tuote.valmistuslinja in ('".implode("','", $multi_valmistuslinja)."')";
    $lasku_where .= " and lasku.kohde in ('".implode("','", $multi_valmistuslinja)."')";
  }

  if (isset($multi_status) and count($multi_status) > 0) {
    $tuote_where .= " and tuote.status in ('".implode("','", $multi_status)."')";
    $tuote_samankaltainen_where .= " and samankaltainen_tuote.status in ('".implode("','", $multi_status)."')";
  }
  else {
    $tuote_where .= " and tuote.status != 'P'";
    $tuote_samankaltainen_where .= " and samankaltainen_tuote.status != 'P'";
  }

  if ($toimittajaid != '') {
    // Jos ollaan rajattu toimittaja, niin otetaan vain sen toimittajan tuotteet ja laitetaan mukaan selectiin
    $toimittaja_join = "JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and tuotteen_toimittajat.liitostunnus = '$toimittajaid')";
    $toimittaja_select = "tuotteen_toimittajat.liitostunnus toimittaja, tuotteen_toimittajat.pakkauskoko";
  }
  else {
    // Jos toimittajaa ei olla rajattu, haetaan tuotteen oletustoimittaja subqueryllä
    $toimittaja_select = "(SELECT liitostunnus FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = ifnull(samankaltaiset.isatuoteno, tuote.tuoteno) ORDER BY if(jarjestys = 0, 9999, jarjestys), tunnus LIMIT 1) toimittaja,
                           (SELECT pakkauskoko FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = ifnull(samankaltaiset.isatuoteno, tuote.tuoteno) ORDER BY if(jarjestys = 0, 9999, jarjestys), tunnus LIMIT 1) pakkauskoko";
  }

  if ($abcrajaus != "") {

    if ($yhtiorow["varaako_jt_saldoa"] != "") {
      $lisavarattu = " + tilausrivi.varattu";
    }
    else {
      $lisavarattu = "";
    }

    // katotaan JT:ssä olevat tuotteet ABC-analyysiä varten, koska ne pitää includata aina!
    $query = "SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
              FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
              JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio
                AND tuote.tuoteno      = tilausrivi.tuoteno
                AND tuote.ei_saldoa    = ''
                AND tuote.ostoehdotus != 'E'
                {$tuote_where}
                {$tuote_valmistuslinja_where})
              WHERE tilausrivi.yhtio   = '{$kukarow["yhtio"]}'
              AND tilausrivi.tyyppi    IN ('L','G')
              AND tilausrivi.var       = 'J'
              AND tilausrivi.jt {$lisavarattu} > 0";
    $vtresult = pupe_query($query);
    $vrow = mysql_fetch_array($vtresult);

    $jt_tuotteet = "''";

    if ($vrow[0] != "") {
      $jt_tuotteet = $vrow[0];
    }

    // joinataan ABC-aputaulu
    $abc_join = "   JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio
            AND abc_aputaulu.tuoteno = tuote.tuoteno
            AND abc_aputaulu.tyyppi = '{$abcrajaustapa}'
            AND (luokka <= '{$abcrajaus}' or luokka_osasto <= '{$abcrajaus}' or luokka_try <= '{$abcrajaus}' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ({$jt_tuotteet}))) ";
  }
  else {
    $abc_join = "  LEFT JOIN abc_aputaulu ON (abc_aputaulu.yhtio = tuote.yhtio
            AND abc_aputaulu.tuoteno = tuote.tuoteno
            AND abc_aputaulu.tyyppi = '{$abcrajaustapa}')";
  }

  // Haetaan valmistukset kannasta, katotaan paljon niissä on vielä valmistettavaa
  $query = "SELECT
            lasku.kohde valmistuslinja,
            tilausrivi.tuoteno,
            tilausrivi.osasto,
            tilausrivi.try,
            if (lasku.toimaika >= '{$nykyinen_alku}' AND lasku.toimaika <= '{$nykyinen_loppu}', varattu, 0) maara,
            if (lasku.toimaika >= '{$nykyinen_alku}' AND lasku.toimaika <= '{$nykyinen_loppu}', varattu, 0) * tuote.valmistusaika_sekunneissa valmistusaika,
            tilausrivi.varattu valmistuksessa_nyt,
            tilausrivi.varattu * tuote.valmistusaika_sekunneissa valmistusaika_nyt,
            DATE_FORMAT(lasku.luontiaika, GET_FORMAT(DATE, 'EUR')) pvm,
            lasku.alatila tila
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus  = lasku.tunnus
              AND tilausrivi.tyyppi   = 'W'
              AND tilausrivi.var     != 'P')
            JOIN tuote ON (tuote.yhtio = lasku.yhtio
              AND tuote.tuoteno       = tilausrivi.tuoteno
              AND tuote.ostoehdotus  != 'E'
              AND tuote.ei_saldoa     = ''
              {$tuote_where})
            {$toimittaja_join}
            {$abc_join}
            WHERE lasku.yhtio         = '{$kukarow["yhtio"]}'
            AND lasku.tila            = 'V'
            AND lasku.alatila        != 'V'
            {$lasku_where}
            {$lisa}
            ORDER BY lasku.kohde, lasku.toimaika, tilausrivi.osasto, tilausrivi.try, tilausrivi.tuoteno";
  $res = pupe_query($query);

  if (mysql_num_rows($res) > 0) {

    // Ei ehcoteta, jos on yhteensänäkymä
    if ($ehdotetut_valmistukset != 'valmistuslinjoittain') {
      // Näytetään tehdyt ja suunnitellut valmistukset
      $EDlinja = false;
      $valmistettu_yhteensa = 0;

      echo t("Valmistukset aikavälillä").": $nykyinen_alku - $nykyinen_loppu <br>\n";
      echo t("Valmistuksia")." ".mysql_num_rows($res)." ".t("kpl").".<br>\n";

      echo "<table>";
    }

    while ($row = mysql_fetch_assoc($res)) {

      // Jos yhteensänäkymä, kerätään vaan data ja continue
      if ($ehdotetut_valmistukset == 'valmistuslinjoittain') {
        $valmistukset_yhteensa[$row['valmistuslinja']]['valmistuksessa'] += $row["valmistuksessa_nyt"];
        $valmistukset_yhteensa[$row['valmistuslinja']]['yhteensa_kpl'] += $row["valmistuksessa_nyt"];
        $valmistukset_yhteensa[$row['valmistuslinja']]['valmistusaika_sekunneissa'] += $row["valmistusaika_nyt"];
        continue;
      }

      // Jos tuotteittain-näkymä ei näytetä nolla rivejä
      if ($ehdotetut_valmistukset != 'valmistuslinjoittain' and $row["maara"] == 0) {
        continue;
      }

      // Valmistuslinja vaihtuu
      if ($row['valmistuslinja'] != $EDlinja or $EDlinja === false) {

        // Yhteensärivi
        if ($EDlinja !== false) {
          echo "<tr>";
          echo "<th colspan='3'>".t("Yhteensä")."</th>";
          echo "<th colspan='3' style='text-align: right;'>$valmistettu_yhteensa</th>";
          echo "</tr>";
          $valmistettu_yhteensa = 0;
        }

        $valmistuslinja = t_avainsana("VALMISTUSLINJA", "", "and avainsana.selite='{$row["valmistuslinja"]}'", "", "", "selitetark");
        $valmistuslinja = empty($valmistuslinja) ? t("Ei valmistuslinjaa") : $valmistuslinja;
        $toggle_counter++;

        echo "<tr>";
        echo "<td class='back' colspan='8'><font class='head'><br>$valmistuslinja &raquo; </font> <a href='#' class='toggle_rivit' id='$toggle_counter'>".t("Näytä tuotteet")."</a></td>";
        echo "</tr>";

        echo "<tr class='togglettava_rivi_$toggle_counter' style='display: none;'>";
        echo "<th>".t("Tuotenumero")."</th>";
        echo "<th>".t("Osasto")."</th>";
        echo "<th>".t("Tuoteryhmä")."</th>";
        echo "<th>".t("Määrä")."</th>";
        echo "<th>".t("Pvm")."</th>";
        echo "<th>".t("Tila")."</th>";
        echo "</tr>";
      }

      $EDlinja = $row['valmistuslinja'];
      $valmistettu_yhteensa += $row["maara"];

      echo "<tr class='aktiivi togglettava_rivi_$toggle_counter' style='display: none;'>";
      echo "<td>{$row["tuoteno"]}</td>";
      echo "<td>{$row["osasto"]}</td>";
      echo "<td>{$row["try"]}</td>";
      echo "<td style='text-align: right;'>{$row["maara"]}</td>";
      echo "<td>{$row["pvm"]}</td>";

      $laskutyyppi = "V";
      $alatila = $row["tila"];
      require "inc/laskutyyppi.inc";

      echo "<td>$laskutyyppi $alatila</td>";
      echo "</tr>";
    }

    // Ei ehcoteta, jos on yhteensänäkymä
    if ($ehdotetut_valmistukset != 'valmistuslinjoittain' and $valmistettu_yhteensa != 0) {
      echo "<tr>";
      echo "<th colspan='3'>".t("Yhteensä")."</th>";
      echo "<th style='text-align: right;'>$valmistettu_yhteensa</th>";
      echo "<th colspan='2'></th>";
      echo "</tr>";

      echo "</table>";
    }
  }
  else {
    echo t("Annetulle aikavälille ei löydy valmistuksia.");
  }

  // Haetaan valmistettavat isätuotteet, jotka osuvat hakuehtoihin
  // Jos tuotteella on samankaltaisia tuotteita, haetaan vain "samankaltaisuuden" isätuotteet mukaan
  $query = "SELECT DISTINCT
            ifnull(samankaltainen_tuote.tuoteno, tuote.tuoteno) tuoteno,
            ifnull(samankaltainen_tuote.nimitys, tuote.nimitys) nimitys,
            ifnull(samankaltainen_tuote.valmistuslinja, tuote.valmistuslinja) valmistuslinja,
            ifnull(samankaltainen_tuote.valmistusaika_sekunneissa, tuote.valmistusaika_sekunneissa) valmistusaika_sekunneissa,
            {$toimittaja_select}
            FROM tuote
            JOIN tuoteperhe ON (tuoteperhe.yhtio = tuote.yhtio
              AND tuoteperhe.isatuoteno             = tuote.tuoteno
              AND tuoteperhe.tyyppi                 = 'R')
            LEFT JOIN tuoteperhe AS samankaltaiset ON (samankaltaiset.yhtio = tuote.yhtio
              AND samankaltaiset.tuoteno            = tuote.tuoteno
              AND samankaltaiset.tyyppi             = 'S')
            LEFT JOIN tuote AS samankaltainen_tuote ON (samankaltainen_tuote.yhtio = tuote.yhtio
              AND samankaltainen_tuote.tuoteno      = samankaltaiset.isatuoteno
              AND samankaltainen_tuote.ei_saldoa    = ''
              AND samankaltainen_tuote.ostoehdotus != 'E'
              {$tuote_samankaltainen_where})
            {$toimittaja_join}
            {$abc_join}
            WHERE tuote.yhtio                       = '{$kukarow["yhtio"]}'
            AND tuote.ei_saldoa                     = ''
            AND tuote.ostoehdotus                  != 'E'
            {$tuote_where}
            {$tuote_valmistuslinja_where}";
  $res = pupe_query($query);

  // Jos yhteensänäkymä, ei ehcota mitään
  if ($ehdotetut_valmistukset != 'valmistuslinjoittain') {
    echo "<br/><br/><font class='head'>".t("Ehdotetut valmistukset")."</font><br/><hr>";
  }

  // Kerätään valmistettavien tuotteiden tiedot arrayseen
  $valmistettavat_tuotteet = array();

  while ($row = mysql_fetch_assoc($res)) {

    // Kerätään mahdolliset samankaltaiset yhteen arrayseen
    $kasiteltavat_tuotteet = array();
    $kasiteltavat_key = 0;

    // Haetaan tuotteen tiedot
    $kasiteltavat_tuotteet[$kasiteltavat_key] = teerivi($row["tuoteno"], $row["toimittaja"], $abcrajaustapa);
    $kasiteltavat_tuotteet[$kasiteltavat_key]["tuoteno"] = $row["tuoteno"];
    $kasiteltavat_tuotteet[$kasiteltavat_key]["nimitys"] = $row["nimitys"];
    $kasiteltavat_tuotteet[$kasiteltavat_key]["valmistuslinja"] = $row["valmistuslinja"];
    $kasiteltavat_tuotteet[$kasiteltavat_key]["isatuote"] = $row["tuoteno"];
    $kasiteltavat_tuotteet[$kasiteltavat_key]["valmistusaika_sekunneissa"] = $row["valmistusaika_sekunneissa"];
    $kasiteltavat_tuotteet[$kasiteltavat_key]["pakkauskoko"] = $row["pakkauskoko"];

    // Otetaan isätuotteen pakkauskoko talteen, sillä sen perusteella tulee laskea "samankaltaisten" valmistusmäärä
    $isatuotteen_pakkauskoko = $kasiteltavat_tuotteet[$kasiteltavat_key]["pakkauskoko"];
    $kasiteltavat_tuotteet[$kasiteltavat_key]["isatuotteen_pakkauskoko"] = $isatuotteen_pakkauskoko;

    // Katsotaan onko kyseessä "samankaltainen" isätuote ja haetaan lapsituotteiden infot
    $query = "SELECT tuote.tuoteno,
              tuote.nimitys,
              tuote.valmistusaika_sekunneissa,
              tuote.valmistuslinja
              FROM tuoteperhe
              JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio
                AND tuote.tuoteno        = tuoteperhe.tuoteno
                AND tuote.ei_saldoa      = ''
                AND tuote.ostoehdotus   != 'E'
                {$tuote_where}
                {$tuote_valmistuslinja_where})
              WHERE tuoteperhe.yhtio     = '{$kukarow["yhtio"]}'
              AND tuoteperhe.isatuoteno  = '{$row["tuoteno"]}'
              AND tuoteperhe.tyyppi      = 'S'";
    $samankaltainen_result = pupe_query($query);

    if (mysql_num_rows($samankaltainen_result) > 0) {
      $samankaltaiset_tuotteet = "{$row["tuoteno"]} ";
    }
    else {
      $samankaltaiset_tuotteet = "";
    }

    while ($samankaltainen_row = mysql_fetch_assoc($samankaltainen_result)) {
      $kasiteltavat_key++;
      $kasiteltavat_tuotteet[$kasiteltavat_key] = teerivi($samankaltainen_row["tuoteno"], $row["toimittaja"], $abcrajaustapa);
      $kasiteltavat_tuotteet[$kasiteltavat_key]["tuoteno"] = $samankaltainen_row["tuoteno"];
      $kasiteltavat_tuotteet[$kasiteltavat_key]["nimitys"] = $samankaltainen_row["nimitys"];
      $kasiteltavat_tuotteet[$kasiteltavat_key]["valmistuslinja"] = $samankaltainen_row["valmistuslinja"];
      $kasiteltavat_tuotteet[$kasiteltavat_key]["isatuote"] = $row["tuoteno"];
      $kasiteltavat_tuotteet[$kasiteltavat_key]["valmistusaika_sekunneissa"] = $samankaltainen_row["valmistusaika_sekunneissa"];
      // $kasiteltavat_tuotteet[$kasiteltavat_key]["pakkauskoko"] = $samankaltainen_row["pakkauskoko"];
      $kasiteltavat_tuotteet[$kasiteltavat_key]["isatuotteen_pakkauskoko"] = $isatuotteen_pakkauskoko;
      $samankaltaiset_tuotteet .= "{$samankaltainen_row["tuoteno"]} ";
    }

    // Loopataan käsitellyt tuotteet ja lasketaan yhteensä valmistettava määrä. Lisäksi poistetaan arraystä kaikki tuotteet, jota ei tule valmistaa
    $valmistettava_yhteensa = 0;
    foreach ($kasiteltavat_tuotteet as $key => $kasittelyssa) {
      if ($kasittelyssa["valmistussuositus"] <= 0) {
        unset($kasiteltavat_tuotteet[$key]);
      }
      else {
        $valmistettava_yhteensa += $kasittelyssa["valmistussuositus"];
      }
    }

    // Jos meille jäi jotain valmistettavaa
    if ($valmistettava_yhteensa != 0) {
      // Jos meillä oli joku poikkeava pakkauskoko tuotteelle, lasketaan valmistusmäärä uudestaan
      if ($isatuotteen_pakkauskoko != 1) {

        // Pyöristetään koko samankaltaisten nippu ylöspäin seuraavaan pakkauskokoon
        if ($isatuotteen_pakkauskoko != 0) {
          $samankaltaisten_valmistusmaara = round($valmistettava_yhteensa / $isatuotteen_pakkauskoko) * $isatuotteen_pakkauskoko;
        }
        else {
          $samankaltaisten_valmistusmaara = $valmistettava_yhteensa;
        }

        foreach ($kasiteltavat_tuotteet as $key => $kasittelyssa) {
          // Lasketaan paljonko tämän tuotteen valmistusmaara on koko valmistuksesta
          $kasiteltavat_tuotteet[$key]["valmistusmaara"] = round($kasittelyssa["valmistussuositus"] / $valmistettava_yhteensa * $samankaltaisten_valmistusmaara);
          $kasiteltavat_tuotteet[$key]["valmistusaika"] = $kasiteltavat_tuotteet[$key]["valmistusmaara"] * $kasiteltavat_tuotteet[$key]["valmistusaika_sekunneissa"];
        }
      }

      // Lisätään käsitellyt tuotteet valmistettavien tuotteiden arrayseen
      foreach ($kasiteltavat_tuotteet as $kasittelyssa) {
        $kasittelyssa["sisartuote"] = $samankaltaiset_tuotteet;
        $valmistettavat_tuotteet[] = $kasittelyssa;
      }
    }
  }

  // Loopataan läpi tehty array
  if (count($valmistettavat_tuotteet) > 0) {

    // Sortataan 2 dimensoinen array. Pitää ensiksi tehdä sortattavista keystä omat arrayt
    $apusort_jarj0 = $apusort_jarj1 = $apusort_jarj2 = array();

    foreach ($valmistettavat_tuotteet as $apusort_key => $apusort_row) {
      $apusort_jarj0[$apusort_key] = $apusort_row['valmistuslinja'];
      $apusort_jarj1[$apusort_key] = $apusort_row['isatuote'];
      $apusort_jarj2[$apusort_key] = $apusort_row['tuoteno'];
    }

    // Sortataan by valmistuslinja, riittopv
    array_multisort($apusort_jarj0, SORT_ASC, $apusort_jarj1, SORT_ASC, $apusort_jarj2, SORT_ASC, $valmistettavat_tuotteet);

    // Jos yhteensänäkymä, ei ehcota mitään
    if ($ehdotetut_valmistukset != 'valmistuslinjoittain') {
      // Kootaan raportti
      echo "<form method='post' autocomplete='off'>";
      echo "<input type='hidden' name='kohde_varasto' value='$kohde_varasto'>";
      echo "<input type='hidden' name='lahde_varasto' value='$lahde_varasto'>";
      echo "<input type='hidden' name='valmistus_ajankohta' value='$nykyinen_loppu'>";

      echo "<table>";
    }

    $EDlinja = false;
    $valmistaja_header_piirretty = false;
    $formin_pointteri = 0;

    // loopataan tuotteet läpi
    foreach ($valmistettavat_tuotteet as $tuoterivi) {

      // Jos yhteensänäkymä, kerätään vaan data ja continue
      if ($ehdotetut_valmistukset == 'valmistuslinjoittain') {
        $valmistukset_yhteensa[$tuoterivi['valmistuslinja']]['valmistusmaara'] += $tuoterivi["valmistussuositus"];
        $valmistukset_yhteensa[$tuoterivi['valmistuslinja']]['yhteensa_kpl'] += $tuoterivi["valmistussuositus"];
        $valmistukset_yhteensa[$tuoterivi['valmistuslinja']]['valmistusaika_sekunneissa'] += $tuoterivi['valmistusaika'];
        continue;
      }

      if ($tuoterivi['valmistuslinja'] != $EDlinja or $EDlinja === false) {
        $kumulatiivinen_valmistusaika = 0;
        $valmistuspaiva = 1;
        $kapasiteetti_varaus = 0;
      }

      // Haetaan valmistuslinjan tiedot (päiväkapasiteetti)
      $tuoterivin_valmistuslinja = search_array_key_for_value_recursive($valmistuslinjat, 'selite', $tuoterivi['valmistuslinja']);

      // Jos päiväkapasiteettiä ei ole syötetty, laitetaan 24h
      $paivakapasiteetti = $tuoterivin_valmistuslinja[0]['selitetark_2'] == 0 ? 86400 : $tuoterivin_valmistuslinja[0]['selitetark_2'];
      $valmistuksen_kokonaiskesto = $tuoterivi['valmistusaika'];

      // Lasketaan valmistuksien kumulatiivistä valmistusaikaa per linja
      $kumulatiivinen_valmistusaika += $valmistuksen_kokonaiskesto;

      // Lasketaan onko tällä päivällä vapaata aikaa
      $vapaa_paivakapasiteetti = $paivakapasiteetti - $kapasiteetti_varaus - $valmistuksen_kokonaiskesto;

      // Valmistus mahtuu tälle päivälle
      if ($vapaa_paivakapasiteetti >= 0) {
        $kapasiteetti_varaus += $valmistuksen_kokonaiskesto;
      }
      else {
        // Valmistus ei mahdu päivälle
        // Katsotaan varattu kapasiteetti päivissä, jotta tiedetään miltä päivältä tämä valmistus pitää aloittaa
        $kesto_paivissa = floor($kapasiteetti_varaus / $paivakapasiteetti);
        $kesto_paivissa = $kesto_paivissa == 0 ? 1 : $kesto_paivissa;

        $valmistuspaiva += $kesto_paivissa;
        $kapasiteetti_varaus = $valmistuksen_kokonaiskesto;
      }

      //Kumulatiivinen aika sek ja päivissä tuoteriville, jotta ne menevät myös exceliin
      $tuoterivi['varaus_sekunneissa'] = $kumulatiivinen_valmistusaika;
      $tuoterivi['varaus_paivissa'] = $valmistuspaiva;

      $tuoterivi['raakaaine_riitto'] = '';

      // Valmistuslinja vaihtuu
      if ($tuoterivi['valmistuslinja'] != $EDlinja or $EDlinja === false) {
        $valmistaja_header = "<tr>";
        $valmistuslinja = t_avainsana("VALMISTUSLINJA", "", "and avainsana.selite='{$tuoterivi["valmistuslinja"]}'", "", "", "selitetark");
        $valmistuslinja = empty($valmistuslinja) ? t("Ei valmistuslinjaa") : $valmistuslinja;
        $valmistaja_header .= "<td class='back' colspan='8'><font class='head'><br>$valmistuslinja</font></td>";
        $valmistaja_header .= "</tr>";
        $valmistaja_header .= "<tr>";
        $valmistaja_header .= "<th>".t("Tuotenumero")."</th>";
        $valmistaja_header .= "<th>".t("Nimitys")."</th>";
        $valmistaja_header .= "<th>".t("Sisar")."-<br>".t("tuotteet")."</th>";
        $valmistaja_header .= "<th>".t("ABC")."-<br>".t("luokka")."</th>";
        $valmistaja_header .= "<th>".t("Reaali")."-<br>".t("saldo")."</th>";
        $valmistaja_header .= "<th>".t("Pakkauskoko")."</th>";
        $valmistaja_header .= "<th>".t("Valmistusaika")."</th>";
        $valmistaja_header .= "<th>".t("Kumulatiivinen")."<br>".t("valmistusaika")."</th>";
        $valmistaja_header .= "<th>".t("Valmistusaika")."<br>".t("yhteensä")."</th>";
        $valmistaja_header .= "<th>".t("Päivä")."</th>";
        $valmistaja_header .= "<th>".t("Valmistuksessa")."</th>";
        $valmistaja_header .= "<th>".t("Riitto Pv")."</th>";
        $valmistaja_header .= "<th>".t("Raaka")."-<br>".t("aine")." ".t("riitto")."</th>";
        $valmistaja_header .= "<th>".t("Vuosi")."-<br>".t("kulutus")."</th>";
        $valmistaja_header .= "<th>".t("Valmistus")."-<br>".t("suositus")."</th>";
        $valmistaja_header .= "<th>".t("Valmistus")."-<br>".t("linja")."</th>";
        $valmistaja_header .= "<th></th>";
        $valmistaja_header .= "<th>".t("Valmistus")."-<br>".t("määrä")."</th>";
        $valmistaja_header .= "</tr>";
        $valmistaja_header_piirretty = false;
      }

      $EDlinja = $tuoterivi['valmistuslinja'];

      // Pitää säilyttää table-headeria muuttujassa, sillä voi olla että valmistuslinjalle ei tule yhtään tuoteriviä ehdotukseen (eikä haluta piirtää turhaa headeriä)
      if ($valmistaja_header_piirretty == false) {
        echo $valmistaja_header;
        $valmistaja_header_piirretty = true;
      }

      echo "<tr class='aktiivi'>";
      echo "<td>{$tuoterivi["tuoteno"]}</td>";
      echo "<td>{$tuoterivi["nimitys"]}</td>";
      echo "<td>{$tuoterivi["sisartuote"]}</td>";
      echo "<td>{$tuoterivi["abcluokka"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["reaalisaldo"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["pakkauskoko"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["valmistusaika_sekunneissa"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi['varaus_sekunneissa']}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["valmistusaika"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi['varaus_paivissa']}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["valmistuksessa"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["riittopv"]}</td>";

      // Tarkistetaanko moneenko valmisteeseen meillä on raaka-aineita
      $raaka_aineiden_riitto = raaka_aineiden_riitto($tuoterivi["tuoteno"], (int) $lahde_varasto);
      //raaka-aine riitto tuoterivi-muuttujaan talteen, jotta se tulee myös excelille
      $tuoterivi['raakaaine_riitto'] = $raaka_aineiden_riitto;
      echo "<td style='text-align: right;'>$raaka_aineiden_riitto</td>";

      echo "<td style='text-align: right;'>{$tuoterivi["vuosikulutus"]}</td>";
      echo "<td style='text-align: right;'>{$tuoterivi["valmistussuositus"]}</td>";

      echo "<td>";
      $result = t_avainsana("VALMISTUSLINJA");
      if ($toim == 'EXCEL') {
        while ($srow = mysql_fetch_array($result)) {
          if ($tuoterivi["valmistuslinja"] == $srow["selite"]) {
            echo $srow["selitetark"];
          }
        }
      }
      else {
        // jos avainsanoja on perustettu tehdään dropdown
        if (mysql_num_rows($result) > 0) {

          echo "<select name='valmistettavat_tuotteet[$formin_pointteri][valmistuslinja]' tabindex='-1'>";
          echo "<option value = ''>".t("Ei valmistuslinjaa")."</option>";

          while ($srow = mysql_fetch_array($result)) {
            $sel = ($tuoterivi["valmistuslinja"] == $srow["selite"]) ? "selected" : "";
            echo "<option value='{$srow["selite"]}' $sel>{$srow["selitetark"]}</option>";
          }

          echo "</select>";
        }
        else {
          echo "$valmistuslinja";
          echo "<input type='hidden' name='valmistettavat_tuotteet[$formin_pointteri][valmistuslinja]' value='{$tuoterivi["valmistuslinja"]}'>";

        }
      }
      echo "</td>";

      // Tehdään Toggle-nappi, jolla voidaan näyttää matikkainfo alla
      $toggle_counter++;
      echo "<td>";
      echo "<a href='#' style='text-decoration:none;' class='toggle_rivit' id='$toggle_counter'><img src='{$palvelin}pics/lullacons/info.png'></a>";
      echo "</td>";

      echo "<td style='text-align: right;'>";
      if ($toim == 'EXCEL') {
        echo $tuoterivi['valmistusmaara'];
      }
      else {
        echo "<input size='8' style='text-align: right;' type='text' name='valmistettavat_tuotteet[$formin_pointteri][valmistusmaara]' value='{$tuoterivi["valmistusmaara"]}' id='vain_numeroita' tabindex='".($formin_pointteri+1)."'>";
        echo "<input type='hidden' name='valmistettavat_tuotteet[$formin_pointteri][tuoteno]' value='{$tuoterivi["tuoteno"]}'>";
        echo "<input type='hidden' name='valmistettavat_tuotteet[$formin_pointteri][riittopv]' value='{$tuoterivi["riittopv"]}'>";
      }
      echo "</td>";
      echo "<td class='back' nowrap>";

      if ($raaka_aineiden_riitto < $tuoterivi["valmistusmaara"]) {
        echo "<font class='error'>".t("Raaka-aineiden saldo ei riitä")."!</font>";
        echo " <a data='{$toggle_counter}' href='#' class='raaka_aine_toggle'><img src='{$palvelin}pics/lullacons/info.png'></a>";
        echo "<br>";
        if ($toim != 'EXCEL') {
          echo "<input type='checkbox' name='valmistettavat_tuotteet[$formin_pointteri][hyvaksy]'>";
          echo "<font class='errir'> ".t("Hyväksy väkisin")."</font><br>";
        }
      }

      if ($toim == 'EXCEL') {
        echo "<input type='hidden' class='exceliin' name='exceliin[$formin_pointteri]' value='".base64_encode(serialize($tuoterivi))."'>";
        echo "<input type='checkbox' class='ei_exceliin' value='$formin_pointteri'>";
        echo "<font class='errir'> ".t("Ei valmisteta")."</font><br>";
      }

      echo "</td>";
      echo "</tr>";

      // Tehdään yks hidden rivi tähän alle, jossa on kaikki luvut ja kaavat, jota on tehty valmistustarpeen laskemiseksi
      echo "<tr class='togglettava_rivi_$toggle_counter' style='display: none;'>";
      echo "<td colspan='4'>";

      echo "<table>";
      echo "<tr><td>".t("Reaalisaldo")."    </td><td>{$tuoterivi['reaalisaldo']}    </td></tr>";
      echo "<tr><td>".t("Varastosaldo")."    </td><td>{$tuoterivi['varastosaldo']}    </td></tr>";
      echo "<tr><td>".t("Tilattu")."      </td><td>{$tuoterivi['tilattu']}      </td></tr>";
      echo "<tr><td>".t("Valmistuksessa")."  </td><td>{$tuoterivi['valmistuksessa']}    </td></tr>";
      echo "<tr><td>".t("Varattu")."      </td><td>{$tuoterivi['varattu']}      </td></tr>";
      echo "<tr><td>".t("Ennakkotilaukset")."  </td><td>{$tuoterivi['ennakko']}      </td></tr>";
      echo "<tr><td>".t("Myyntitavoite")."  </td><td>{$tuoterivi['budjetoitu_myynti']}  </td></tr>";
      echo "<tr><td>".t("Vuosikulutus")."    </td><td>{$tuoterivi['vuosikulutus']}    </td></tr>";
      echo "<tr><td>".t("Päiväkulutus")."    </td><td>{$tuoterivi['paivakulutus']}    </td></tr>";
      echo "<tr><td>".t("Riitto päivät")."  </td><td>{$tuoterivi['riittopv']}      </td></tr>";
      echo "<tr><td>".t("Määräennuste")."    </td><td>{$tuoterivi['maaraennuste']}    </td></tr>";
      echo "<tr><td>".t("Toimitusaika")."    </td><td>{$tuoterivi['toimitusaika']}    </td></tr>";
      echo "<tr><td>".t("Valmistussuositus")."</td><td>{$tuoterivi['valmistussuositus']}  </td></tr>";
      echo "<tr><td>".t("Pakkauskoko")."    </td><td>{$tuoterivi['pakkauskoko']}    </td></tr>";
      echo "<tr><td>".t("Isän Pakkauskoko")."  </td><td>{$tuoterivi['isatuotteen_pakkauskoko']}</td></tr>";
      echo "<tr><td>".t("Valmistusmäärä")."  </td><td>{$tuoterivi['valmistusmaara']}    </td></tr>";
      echo "</table>";

      echo "</td><td colspan='14'>";

      echo t("Reaalisaldo")." = ".t("Varastosaldo")." + ".t("Tilattu")." + ".t("Valmistuksessa")." - ".t("Varattu")." - ".t("Ennakkotilaukset")."<br>";
      echo "{$tuoterivi["reaalisaldo"]} = {$tuoterivi["varastosaldo"]} + {$tuoterivi["tilattu"]} + {$tuoterivi["valmistuksessa"]} - {$tuoterivi["varattu"]} - {$tuoterivi["ennakko"]}<br><br>";
      echo t("Päiväkulutus")." = round(".t("Vuosikulutus")." / 240)<br>";
      echo "{$tuoterivi["paivakulutus"]} = round({$tuoterivi["vuosikulutus"]} / 240)<br><br>";
      echo t("Riitto päivät")." = floor(".t("Reaalisaldo")." / ".t("Päiväkulutus").")<br>";
      echo "{$tuoterivi["riittopv"]} = floor({$tuoterivi["reaalisaldo"]} / {$tuoterivi["paivakulutus"]})<br><br>";

      echo "<font class='info'>";
      echo t("Myyntitavoite").":<br>";

      foreach ($tuoterivi['budjetin_peruste'] as $budjetin_peruste) {
        echo "Tuotteen status: ".$budjetin_peruste['status']."<br>";
        echo "Budjetin peruste: ".$budjetin_peruste['syy']."<br>";
        echo "Myyntitavoite: ".$budjetin_peruste['budjetoitu_myynti']."<br><br>";
      }
      echo "</font>";

      echo t("Määräennuste")." = (".t("Päiväkulutus")." * ".t("Toimitusaika").") + ".t("Myyntitavoite")."<br>";
      echo "{$tuoterivi["maaraennuste"]} = ({$tuoterivi["paivakulutus"]} * {$tuoterivi["toimitusaika"]}) + {$tuoterivi["budjetoitu_myynti"]}<br><br>";
      echo t("Valmistussuositus")." = round(".t("Määräennuste")." - ".t("Reaalisaldo").")<br>";
      echo "{$tuoterivi["valmistussuositus"]} = round({$tuoterivi["maaraennuste"]} - {$tuoterivi["reaalisaldo"]})<br><br>";
      echo t("Valmistusmäärä")." = round(".t("Valmistussuositus")." / ".t("Pakkauskoko").") * Pakkauskoko<br>";
      echo "{$tuoterivi["valmistusmaara"]} = round({$tuoterivi["valmistussuositus"]} / {$tuoterivi["isatuotteen_pakkauskoko"]}) * {$tuoterivi["isatuotteen_pakkauskoko"]}";

      echo "</td></tr>";

      $formin_pointteri++;

      $raaka_aineet = raaka_aineiden_riitto($tuoterivi['tuoteno'], (int) $lahde_varasto, 'X');

      echo "<tr class='raaka_aineet_{$toggle_counter} raaka_aineet_hidden'>";
      echo "<td colspan='18'>";

      echo "<table>";
      echo "<thead>";
      echo "<th>".t('Tuotenumero')."</th>";
      echo "<th>".t('Nimitys')."</th>";
      echo "<th>".t('Saldo')."</th>";
      echo "<th>".t('Riitto')."</th>";
      echo "</thead>";
      echo "<tbody>";
      foreach ($raaka_aineet as $raaka_aine) {
        if ($raaka_aine['riitto'] < $tuoterivi['valmistusmaara']) {
          echo "<tr class='aktiivi'>";

          echo "<td>";
          echo "<a href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($raaka_aine["tuoteno"])."&lopetus=$lopetus'>".$raaka_aine['tuoteno']."</a>";
          echo "</td>";

          echo "<td>";
          echo $raaka_aine['nimitys'];
          echo "</td>";

          echo "<td>";
          echo $raaka_aine['saldo'];
          echo "</td>";

          echo "<td>";
          echo $raaka_aine['riitto'];
          echo "</td>";

          echo "</tr>";
        }
      }
      echo "</tbody>";
      echo "</table>";

      echo "</td>";
      echo "</tr>";
    }

    // Jos yhteensänäkymä, ei ehcota mitään
    if ($ehdotetut_valmistukset != 'valmistuslinjoittain') {
      echo "</table>";

      echo "<br>";
      if ($toim == 'EXCEL') {
        echo "<input type='hidden' name='tee' value='GENEROI_EXCEL' />";
        echo "<input type='hidden' name='lopetus' value='{$lopetus}' />";
        echo "<input type='submit' name='generoi_excel' value='".t('Tulosta excel')."' />";
      }
      else {
        echo "<input type='hidden' name='tee' value='TEE_VALMISTUKSET' />";
        echo "<input type='submit' name='muodosta_valmistukset' value='".t('Muodosta valmistukset')."' />";
      }
      echo "<br><br>";

      echo "</form>";
    }

    $tee = "";
  }

  // Jos yhteensänäkymä, ja meillä on dataa
  if (isset($valmistukset_yhteensa) and count($valmistukset_yhteensa) > 0 and $ehdotetut_valmistukset == 'valmistuslinjoittain') {
    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    echo "<th style='text-align:right;'>".t('Valmistuslinja')."</th>";
    echo "<th style='text-align:right;'>".t('Valmistuksessa kpl')."<br>".t('nyt')."</th>";
    echo "<th style='text-align:right;'>".t('Valmistussuositus kpl')."<br>".t('ajanjaksolle')."</th>";
    echo "<th style='text-align:right;'>".t('Yhteensä kpl')."</th>";
    echo "<th style='text-align:right;'>".t('Valmistusaika yhteensä')."</th>";
    echo "</tr>";
    echo "</thead>";

    echo "<tbody>";

    // Yhteensäluvut
    $valmistuksessa = 0;
    $valmistusmaara = 0;
    $yhteensa_kpl   = 0;
    $valmistusaika  = 0;

    foreach ($valmistukset_yhteensa as $valmistuslinja => $luvut) {
      echo "<tr class='aktiivi'>";
      echo "<td style='text-align:right;'>{$valmistuslinja}</td>";
      echo "<td style='text-align:right;'>{$luvut['valmistuksessa']}</td>";
      echo "<td style='text-align:right;'>{$luvut['valmistusmaara']}</td>";
      echo "<td style='text-align:right;'>{$luvut['yhteensa_kpl']}</td>";
      echo "<td style='text-align:right;'>".round($luvut['valmistusaika_sekunneissa'])."</td>";
      echo "</tr>";

      // Yhteensäluvut
      $valmistuksessa += $luvut['valmistuksessa'];
      $valmistusmaara += $luvut['valmistusmaara'];
      $yhteensa_kpl   += $luvut['yhteensa_kpl'];
      $valmistusaika  += $luvut['valmistusaika_sekunneissa'];
    }

    echo "<tr>";
    echo "<td class='tumma' style='text-align:right;'>".t('Yhteensä')."</td>";
    echo "<td class='tumma' style='text-align:right;'>{$valmistuksessa}</td>";
    echo "<td class='tumma' style='text-align:right;'>{$valmistusmaara}</td>";
    echo "<td class='tumma' style='text-align:right;'>{$yhteensa_kpl}</td>";
    echo "<td class='tumma' style='text-align:right;'>".round($valmistusaika)."</td>";
    echo "</tr>";

    echo "</tbody>";
    echo "</table>";

    echo "<br><br>";
  }

  if (count($valmistettavat_tuotteet) == 0) {
    echo "<br><br>";
    echo "<font class='error'>".t("Antamallasi rajauksella ei löydy yhtään tuotetta ehdotukseen").".</font><br>";
    echo "<br>";
    $tee = "";
  }
}

// Näytetään käyttöliittymä
if (!isset($tee) or $tee == "") {

  echo "<form method='post' autocomplete='off'>";
  echo "<table>";

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio = '{$kukarow["yhtio"]}' AND tyyppi != 'P'
            ORDER BY tyyppi, nimitys";
  $result = pupe_query($query);

  echo "<tr><th>".t("Valmisteiden kohdevarasto")."</th>";

  echo "<td><select name='kohde_varasto'>";

  while ($row = mysql_fetch_assoc($result)) {
    $sel = (isset($kohde_varasto) and $row['tunnus'] == $kohde_varasto) ? "selected" : "";
    echo "<option value='{$row["tunnus"]}' $sel>{$row["nimitys"]}</option>";
  }
  echo "</select></td></tr>";

  mysql_data_seek($result, 0);

  echo "<tr><th>".t("Käytä raaka-aineita varastosta")."</th>";

  echo "<td><select name='lahde_varasto'>";
  echo "<option value=''>".t("Käytä kaikista")."</option>";

  while ($row = mysql_fetch_assoc($result)) {
    $sel = (isset($lahde_varasto) and $row['tunnus'] == $lahde_varasto) ? "selected" : "";
    echo "<option value='{$row["tunnus"]}' $sel>{$row["nimitys"]}</option>";
  }
  echo "</select></td></tr>";

  echo "<tr>";
  echo "<th>".t("Tuoterajaus")."</th>";
  echo "<td>";

  $monivalintalaatikot = array('OSASTO', 'TRY', 'TUOTEMERKKI');
  $monivalintalaatikot_normaali = array();
  require "tilauskasittely/monivalintalaatikot.inc";

  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t('Valmistuslinja')."</th>";
  echo "<td>";
  echo "<select multiple='multiple' class='multipleselect' name='multi_valmistuslinja[]' onchange='submit();'>";
  echo "<option value=''>".t('Ei valintaa')."</option>";
  foreach ($valmistuslinjat as $_valmistuslinja) {
    $sel = in_array($_valmistuslinja['selite'], $multi_valmistuslinja) ? " SELECTED" : "";
    echo "<option value='{$_valmistuslinja['selite']}'{$sel}>{$_valmistuslinja['selitetark']}</option>";
  }
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  $sel = array_fill_keys($multi_status, 'selected');

  echo "<tr>";
  echo "<th>".t("Tuotteen status")."</th>";
  echo "<td>";
  echo "<select multiple='multiple' class='multipleselect' name='multi_status[]' onchange='submit();'>";
  echo "<option value=''>".t("Ei valintaa")."</option>";
  echo product_status_options($sel);
  echo "</select>";

  echo "</td>";
  echo "</tr>";

  $sel = array(
    'A' => $tilaustuotteiden_kasittely == 'A' ? 'SELECTED' : '',
    'B' => $tilaustuotteiden_kasittely == 'B' ? 'SELECTED' : '',
    'C' => $tilaustuotteiden_kasittely == 'C' ? 'SELECTED' : '',
  );

  echo "<tr><th>".t("Tilaustuotteiden käsittely")."</th><td>";
  echo "<select name='tilaustuotteiden_kasittely'>";
  echo "<option value='A' {$sel['A']}>".t("Tilaustuotteiden määräennuste on jälkitoimitusrivit")."</option>";
  echo "<option value='B' {$sel['B']}>".t("Tilaustuotteiden määräennuste on budjetti/myynti/toimitusaika")."</option>";
  echo "<option value='C' {$sel['C']}>".t("Tilaustuotteiden määräennuste on jälkitoimitusrivit + budjetti/myynti")."</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr><th>".t("ABC-luokkarajaus ja rajausperuste")."</th><td>";

  echo "<select name='abcrajaus' onchange='submit()'>";
  echo "<option  value=''>".t("Valitse")."</option>";

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

    echo "<option  value='$i##TM' $selabc>".t("Myynti").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

    echo "<option  value='$i##TK' $selabc>".t("Myyntikate").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

    echo "<option  value='$i##TR' $selabc>".t("Myyntirivit").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

    echo "<option  value='$i##TP' $selabc>".t("Myyntikappaleet").": {$ryhmanimet[$i]} $teksti</option>";
  }

  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr><th>".t("Toimittaja")."</th><td>";
  if ($toimittajaid == "") {
    echo "<input type='text' size='20' name='ytunnus' value='$ytunnus'>";
  }
  else {
    $query = "SELECT *
              from toimi
              where yhtio = '{$kukarow["yhtio"]}'
              and tunnus  = '{$toimittajaid}'";
    $result = pupe_query($query);
    $toimittaja = mysql_fetch_assoc($result);

    echo "$toimittaja[nimi] $toimittaja[nimitark]";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  }
  echo "</td></tr>";

  $sel = $ehdotetut_valmistukset == "valmistuslinjoittain" ? " SELECTED" : "";

  echo "<tr>";
  echo "<th>".t('Esitysmuoto')."</th>";
  echo "<td>";
  echo "<select name='ehdotetut_valmistukset'>";
  echo "<option value='tuotteittain'>".t('Näytä tuotteittain')."</option>";
  echo "<option value='valmistuslinjoittain'{$sel}>".t('Näytä valmistuslinjoittain')."</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Alkupäivämäärä (pp-kk-vvvv)")."</th>";
  echo "<td>";
  echo "<input type='text' name='ppa1' value='$ppa1' size='5'>";
  echo "<input type='text' name='kka1' value='$kka1' size='5'>";
  echo "<input type='text' name='vva1' value='$vva1' size='5'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Loppupäivämäärä (pp-kk-vvvv)")."</th>";
  echo "<td>";
  echo "<input type='text' name='ppl1' value='$ppl1' size='5'>";
  echo "<input type='text' name='kkl1' value='$kkl1' size='5'>";
  echo "<input type='text' name='vvl1' value='$vvl1' size='5'>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";

  echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Suunnittele valmistus")."'></form>";
}

require "inc/footer.inc";
