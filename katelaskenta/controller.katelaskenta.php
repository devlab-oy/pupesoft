<?php
/*
 * controller.katelaskenta.php
 *
 * Kontrolleri -tiedoston teht�v� on hoitaa tietojen alustaminen
 * katelaskenta toimintoa varten. My�s m��ritt�� oikeat toimenpiteet
 * kun tietoja l�hetet��n katelaskenta-toiminnosta k�sin.
 *
 * Ohjelmakoodissa on viel� osioita entisest� tiedostota, josta pohja
 * on otettu. Osittain pohjaa on refaktoroitu mutta isommat osiot,
 * joissa ollut enemm�n ty�t�, on j�tetty ennalleen. Osa entisest�
 * koodista siirretty katelaskenta_functions.php tiedostoon.
 *
 * Kontrolleri ohjaa kaikki tietojen tulostukset template.katelaskenta.php
 * tiedostolle. Template tiedostossa tulostetaan toistaiseksi vain
 * hakutulokset. Hakulaatikko piirret��n viel� entisill� koodeilla sen
 * monimutkaisuuden takia.
 *
 * Liittyv�t tiedostot:
 *
 * /controller.katelaskenta.php
 * /functions.katelaskenta.php
 * /katelaskenta_functions.php
 * /scripts.katelaskenta.js
 * /template.katelaskenta.php
 * /tietokantamuutokset-katelaskenta.sql
 *
 * Tiedossa olevat riippuvuudet muualle
 *
 * ../inc/katelaskenta_functions.php
 * ../tilauskasittely/monivalintalaatikot.inc
 *
 */
///* T�m� skripti k�ytt�� slave-tietokantapalvelinta *///
$useslave = 1;

require "../inc/parametrit.inc";

// Haetaan _funktiot.php -tiedosto, jossa katelaskennan toimintalogiikka.
require "katelaskenta_functions.php";
require "functions.katelaskenta.php";

// Tempalte array on luotu sivupohjan tietoja varten.
$template = array();

// Jos on painettu "Laske kaikki ja tallenna" -nappia, k�yd��n l�hetetyt
// tiedot l�pi ja tallennetaan muutokset tietokantaan. Mik�li virheit�
// ilmenee tiedoissa, virheelliset rivit palautetaan taulukkona.
$submit_katelaskenta = (isset($_POST["submit-katelaskenta"]) ? $_POST["submit-katelaskenta"] : "");

if (strlen($submit_katelaskenta) > 0) {
  // Tallennetaan post-tiedot omaan muuttujaan
  $post_array = $_POST;
  // Tallennetaan katemuutokset.
  $virheelliset_rivit = tallenna_valitut_katemuutokset($post_array);

  //Tiedot tallennettu onnistuneesti, ilmoitus k�ytt�j�lle
  $template["flash_success"] = "Katemuutokset tallennettu onnistuneesti.";

  // Jos virheellisi� rivej� ilmeni, tehd��n niist� ilmoitus k�ytt�j�lle.
  $virheiden_lkm = count($virheelliset_rivit);
  if ($virheiden_lkm > 0)
    $template["flash_error"] = "L�hetetyiss� tiedoissa oli {$virheiden_lkm} virhett�.";
}

/**
 * ALKUPER�IST� KOODIA KOPIOIDUISTA POHJISTA, JOTA
 * EI OLE PILKOTTU.
 *
 * SEURAAVASSA MUUTAMAN SADAN RIVIN AIKANA TULOSTETAAN
 * SIVULLE TUOTEHAKULOMAKE, JOKA ON SAMA KUIN ALKUPER�ISESS�
 * SIVUSSA.
 */


/**
 * Seuraavat kaksi if-lausetta liittyv�t poistetut valintaan tuotteita
 * hakiessa.
 */
if (!isset($poistetut)) {
  $poistetut = '';
}

if ($poistetut != "") {
  $poischeck = "CHECKED";
  $ulisa .= "&poistetut=checked";
  $poislisa = "";
}
else {
  $poislisa = " and (tuote.status not in ('P','X')
          or (SELECT sum(saldo)
              FROM tuotepaikat
              WHERE tuotepaikat.yhtio=tuote.yhtio
              AND tuotepaikat.tuoteno=tuote.tuoteno
              AND tuotepaikat.saldo > 0) > 0) ";
  $poischeck = "";
}

/**
 * Seuraavilla riveill� valitaan j�rjestys hakutuloksille.
 */
$jarjestys = "tuote.tuoteno";

$lisa = "";
$ulisa = "";
$toimtuotteet = "";
$poislisa_mulsel = "";
$lisa_parametri = "";

/**
 * Seuraavat kaksi if-lausetta liittyv�t "N�yt� vain saldolliset tuotteet"
 * -valintaan tuotehaussa.
 */
if (!isset($saldotonrajaus)) {
  $saldotonrajaus = '';
}
if ($saldotonrajaus != '') {
  $saldotoncheck = "CHECKED";
  $ulisa .= "&saldotonrajaus=checked";
}
else {
  $saldotoncheck = "";
}

/**
 * Seuraavat kaksi if-lausetta liittyv�t "Lis�tiedot"
 * -valintaan tuotehaussa.
 */
if (!isset($lisatiedot)) {
  $lisatiedot = '';
}
if ($lisatiedot != "") {
  $lisacheck = "CHECKED";
  $ulisa .= "&lisatiedot=checked";
}
else {
  $lisacheck = "";
}

/**
 * Seuraavat kaksi if-lausetta liittyv�t "Nimitys"-hakuehtoon.
 */
if (!isset($nimitys)) {
  $nimitys = '';
}
if (trim($nimitys) != '') {
  $nimitys = mysql_real_escape_string(trim($nimitys));
  $lisa .= " and tuote.nimitys like '%$nimitys%' ";
  $ulisa .= "&nimitys=$nimitys";
}

/**
 * Seuraavat kaksi if-lausetta liittyv�t "Tuotenumero"-hakuehtoon.
 */
if (!isset($tuotenumero)) {
  $tuotenumero = '';
}
if (trim($tuotenumero) != '') {
  $tuotenumero = mysql_real_escape_string(trim($tuotenumero));

  if (isset($alkukoodilla) and $alkukoodilla != "") {
    $lisa .= " and tuote.tuoteno like '$tuotenumero%' ";
  }
  else {
    $lisa .= " and tuote.tuoteno like '%$tuotenumero%' ";
  }
  $ulisa .= "&tuotenumero=$tuotenumero";
}

/**
 * Seuraavat kaksi if-lausetta liittyv�t "Toimittajan tuotenumero"-hakuehtoon.
 */
if (!isset($toim_tuoteno)) {
  $toim_tuoteno = '';
}
if (trim($toim_tuoteno) != '') {
  $toim_tuoteno = mysql_real_escape_string(trim($toim_tuoteno));

  // Katsotaan l�ytyyk� tuotenumero toimittajan vaihtoehtoisista tuotenumeroista
  $query = "SELECT GROUP_CONCAT(DISTINCT toim_tuoteno_tunnus SEPARATOR ',') toim_tuoteno_tunnukset
            FROM tuotteen_toimittajat_tuotenumerot
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tuoteno = '{$toim_tuoteno}'";
  $vaih_tuoteno_res = pupe_query($query);
  $vaih_tuoteno_row = mysql_fetch_assoc($vaih_tuoteno_res);

  $vaihtoehtoinen_tuoteno_lisa = $vaih_tuoteno_row['toim_tuoteno_tunnukset'] != '' ? " OR tunnus IN ('{$vaih_tuoteno_row['toim_tuoteno_tunnukset']}')" : "";

  $query = "SELECT DISTINCT tuoteno
            FROM tuotteen_toimittajat
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND (toim_tuoteno LIKE '%{$toim_tuoteno}%' $vaihtoehtoinen_tuoteno_lisa)
            LIMIT 500";
  $pres = pupe_query($query);

  while ($prow = mysql_fetch_assoc($pres)) {
    $toimtuotteet .= "'" . $prow["tuoteno"] . "',";
  }

  $toimtuotteet = substr($toimtuotteet, 0, -1);

  if ($toimtuotteet != "") {
    $lisa .= " and tuote.tuoteno in ($toimtuotteet) ";
  }

  $ulisa .= "&toim_tuoteno=$toim_tuoteno";
}

echo "<font class='head'>".t("Katelaskenta").":</font><br/><br/>";

// Seuraavaksi aletaan piirt�m��n tuotehakulomaketta.
echo "<form action = '' method = 'post'>";
echo "<table style='display:inline-table; padding-right:4px; padding-top:4px;' valign='top'>";
echo "<tr><th>" . t("Tuotenumero") . "</th><td><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td></tr>";
echo "<tr><th>" . t("Toim tuoteno") . "</th><td><input type='text' size='25' name = 'toim_tuoteno' id='toim_tuoteno' value = '$toim_tuoteno'></td></tr>";
echo "<tr><th>" . t("Nimitys") . "</th><td><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td></tr>";
echo "<tr><th>" . t("Poistetut") . "</th>";
echo "<td><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td></tr>";
echo "<tr><th>" . t("Lis�tiedot") . "</th><td><input type='checkbox' name='lisatiedot' id='lisatiedot' $lisacheck></td></tr>";
echo "<tr>";
echo "<th>" . t("N�yt� vain saldolliset tuotteet") . "</th>";
echo "<td><input type='checkbox' name='saldotonrajaus' $saldotoncheck></td>";
echo "</tr>";
echo "</table><br/>";
echo "<br/>";

// Oletus
$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "MALLI", "MALLI/MALLITARK", "<br>DYNAAMINEN_TUOTE");
$monivalintalaatikot_normaali = array();

/**
 * REFACTOR: Include tiedosto joudutaan hakemaan toisesta kansiosta.
 *
 * Saattaa auheuttaa ongelmia jossain vaiheessa, laatikoita muutellaan.
 */
require "../tilauskasittely/monivalintalaatikot.inc";

echo "<input type='submit' name='submit_button' id='submit_button' class='hae_btn' value = '" . t("Etsi") . "'></form>";
echo "&nbsp;<form action = '' method = 'post'>
  <input type='submit' name='submit_button2' id='submit_button2' value = '" . t("Tyhjenn�") . "'>
  </form>";

/**
 *  ALKUPER�INEN KOPIOITU KOODI P��TTYY.
 *
 *  SEURAAVAT RIVIT OSITTAIN KATELASKENNAN OMAA TOIMINTAA
 *  VARTEN MUUTAMAA RIVI� LUKUUNOTTAMATTA.
 */

/**
 * Seuraava if l�hett�� hakukyselyn tietokantaan.
 *
 * Lis�ksi if-lohkon sis�ll� k�sitell��n tuotteiden tulostus omassa
 * if-lohkossa. Jos tuotteita ei l�ydy yht��n, tulostetaan siit� ilmoitus.
 */
if (!isset($submit_button)) {
  $submit_button = '';
}
if ($submit_button != '' and ($lisa != '' or $lisa_parametri != '')) {

  // Hakukysely tuotehakuun.
  $query = "SELECT
            if (tuote.tuoteno = '$tuotenumero', 1, if(left(tuote.tuoteno, length('$tuotenumero')) = '$tuotenumero', 2, 3)) jarjestys,
            tuote.tuoteno,
            tuote.nimitys,
            tuote.osasto,
            tuote.try,
            tuote.myyntihinta,
            tuote.myymalahinta,
            tuote.nettohinta,
            tuote.aleryhma,
            tuote.status,
            tuote.ei_saldoa,
            tuote.yksikko,
            tuote.tunnus,
            tuote.epakurantti25pvm,
            tuote.epakurantti50pvm,
            tuote.epakurantti75pvm,
            tuote.epakurantti100pvm,
            tuote.kehahin,
            tuote.myyntikate,
            tuote.myymalakate,
            tuote.nettokate,
            (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
            tuote.sarjanumeroseuranta,
            tuote.status
            FROM tuote use index (tuoteno, nimitys)
            $lisa_parametri
            WHERE tuote.yhtio     = '$kukarow[yhtio]'
            and tuote.tuotetyyppi NOT IN ('A', 'B')
            $kieltolisa
            $lisa
            $extra_poislisa
            $poislisa
            ORDER BY jarjestys, $jarjestys
            LIMIT 500";
  $result = pupe_query($query);

  // T�ytet��n template muuttuja tiedoilla, jotka halutaan
  // tulostaa template.katelaskenta.php -tiedostossa.
  $template["tuotteet"] = array();
  // Sort tietoja, joita k�ytet��n kun tietoja l�hetet��n ja
  // samat hakutulokset tulevat n�kyviin my�s sivun uudelleen
  // latauksen j�lkeen.
  $template["edsort"] = (isset($edsort) ? $edsort : "");
  $template["ojarj"] = (isset($ojarj) ? $orarj : "");
  $template["ulisa"] = (isset($ulisa) ? $ulisa : "");
  $template["variaatio_query_param"] = (isset($variaatio_query_param) ? $variaatio_query_param : "");

  // Jos tuotteita ei l�ydy, tulostetaan ilmoitus
  if (mysql_num_rows($result) <= 0)
    $template["ilmoitus"] = t("Yht��n tuotetta ei l�ytynyt");

  // Jos tuotteita yli 500, tulostetaan ilmoitus
  if (mysql_num_rows($result) >= 500)
    $template["ilmoitus"] = t("L�ytyi yli 500 tuotetta, tarkenna hakuasi");

  // Jos aikaisemmat tarkistukset on l�p�isty, eik� ilmoitusta ole
  // taulukossa, voidaan jatkaa hakurivien k�sittely�.
  if (!array_key_exists("ilmoitus", $template)) {
    $rows = array();

    while ($mrow = mysql_fetch_assoc($result)) {
      $rows[$mrow["tuoteno"]] = $mrow;
    }

    // Valmistelee hakutulokset templatea varten.
    $template["tuotteet"] = valmistele_hakutulokset($rows);
    $template["yhtio"] = $yhtiorow;

  }
  // _hakutulokset.php template k�ytet��n tulostaulukon tulostamiseen.
  require_once 'template.katelaskenta.php';
}


/**
 * Tulostetaan sivuston footer osio.
 */
require "inc/footer.inc";
