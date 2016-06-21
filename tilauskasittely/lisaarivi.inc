<?php

// Tarvitsemme:
// $trow        --> jossa on tuotteen kaikki tiedot
// $rivinumero      --> kent‰ss‰ on joko tilaajan rivinumero tai konserninsis‰isiss‰ kaupoissa sis‰inen toimittajanumero
// $laskurow      --> jossa on laskun kaikki tiedot
// $kukarow[kesken]    --> jossa on k‰ytt‰j‰ll‰ keskenoleva tilausnumero
// $kpl          --> jossa on tilattu kappalem‰‰r‰
// $tuoteno        --> jossa on tilattava tuotenumero
// $toimaika      --> arvioitu toimitusaika
// $kerayspvm      --> toivottu ker‰ysaika
// $hinta        --> k‰ytt‰j‰n syˆtt‰m‰ hinta
// $netto        --> k‰ytt‰j‰n syˆtt‰m‰ netto
// $ale1,2,3,4,5...    --> k‰ytt‰j‰n syˆtt‰m‰ ale
// $var          --> H,J,P varrit
// $varasto        --> myyd‰‰n vain t‰st‰/n‰ist‰ varastosta
// $paikka        --> myyd‰‰n vain t‰lt‰ paikalta
// $rivitunnus      --> tietokannan tunnus jolle rivi lis‰t‰‰n
// $rivilaadittu    --> vanhan rivin laadittuaika, s‰ilytet‰‰n se
// $korvaavakielto    --> Jos erisuuri kuin tyhj‰ niin ei myyd‰ korvaavia
// $jtkielto      --> Jos erisuuri kuin tyhj‰ niin ei laiteta JT:Seen
// $perhekielto      --> Jos erisuuri kuin tyhj‰ niin ei etsit‰ ollenkaan perheit‰
// $varataan_saldoa    --> Jos == EI niin ei varata saldoa (tietyiss‰ keisseiss‰), tai siis ei ainakan tehd‰ saldotsekki‰
// $kutsuja        --> Kuka t‰t‰ skripti‰ kutsuu
// $myy_sarjatunnus    --> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
// $osto_sarjatunnus  --> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
// $jaksotettu      --> Kuuluuko tilausrivi mukaan jaksotukseen
// $perheid        --> Tuoteperheen perheid
// $perheid2      --> Lis‰varusteryhm‰n perheid2
// $orvoteikiinnosta  --> Meit‰ ei kiinnosta orvot jos t‰m‰ ei ole tyhj‰.
// $osatoimkielto    --> Jos saldo ei riit‰ koko riville niin ei lis‰t‰ rivi‰ ollenkaan
// $olpaikalta      --> pakotetaan myym‰‰n oletuspaikalta
// $tuotenimitys    --> tuotteen nimitys jos nimityksen syˆtˆ on yhtiˆll‰ sallittu
// $tuotenimitys_force  --> tuotteen nimitys muutetaan systemitasolla

// Palauttaa arrayn $lisatyt_rivit1 jossa on kaikkien rivien tunnukset jotka t‰‰ systeemi lis‰si myyntihaarassa
// Palauttaa arrayn $lisatyt_rivit2 jossa on kaikkien rivien tunnukset jotka t‰‰ systeemi lis‰si puute-jt-osto-haarassa

if ($kukarow['toimipaikka'] != $laskurow['yhtio_toimipaikka'] and $yhtiorow['myyntitilauksen_toimipaikka'] == 'A') {
  $kukarow['toimipaikka'] = $laskurow['yhtio_toimipaikka'];
  $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);
}

// debuggia varten, laita 1 jos haluut messuja
$lisaarivi_debug = 0;

// haluttaessa ei varata saldoa extranet-tilauksissa
if ($laskurow['tilaustyyppi'] == 'H') {
  $varataan_saldoa = "EI";
}

if (!isset($rivitunnus)) $rivitunnus = 0;
else $rivitunnus = (int) $rivitunnus;

if ($laskurow["tila"] == 'G' and !isset($kohde_alue) and isset($tuoteno)) {

  //Laitetaan tuote oletusvaraston tuotepaikalle
  $tuotteen_oletuspaikka = hae_tuotteen_tuotepaikat($tuoteno, $laskurow['clearing']);

  if (empty($tuotteen_oletuspaikka) or ($laskurow['tilaustyyppi'] == 'M' and $kohde_taso != $laskurow['liitostunnus'])) {
    // Kun kyseess‰ myyntitili, niin hyllytaso = laskun liitostunnus
    if ($laskurow['tilaustyyppi'] == 'M') {
      $kohde_taso = $laskurow['liitostunnus'];
    }
    else {
      $kohde_taso = '';
    }

    //Jos tuotteella ei ole tuotepaikkaa kohdevarastossa, luodaan se
    $tuotteen_oletuspaikka[0] = lisaa_tuotepaikka($tuoteno, "", "", "", $kohde_taso, "", "", 0, 0, $laskurow['clearing']);
  }

  // otetaan eka lˆytynyt tuotepaikka kohdetuotepaikaksi
  $kohde_alue   = $tuotteen_oletuspaikka[0]['hyllyalue'];
  $kohde_nro    = $tuotteen_oletuspaikka[0]['hyllynro'];
  $kohde_vali   = $tuotteen_oletuspaikka[0]['hyllyvali'];
  $kohde_taso   = $tuotteen_oletuspaikka[0]['hyllytaso'];
}

if (!isset($indeksi)) {
  $indeksi = 0;
}

if (!isset($osto_sarjatunnus)) {
  $osto_sarjatunnus = 0;
}

if (!isset($myy_sarjatunnus)) {
  $myy_sarjatunnus = 0;
}

if (!isset($rivitunnus)) {
  $rivitunnus = 0;
}
else {
  $rivitunnus = (int) $rivitunnus;
}

if (!isset($perheid)) {
  $perheid = 0;
}
else {
  $perheid = (int)   $perheid;
}

if (!isset($perheid2)) {
  $perheid2 = 0;
}
else {
  $perheid2 = (int)   $perheid2;
}

if (!isset($rivinumero)) {
  $rivinumero = "";
}

if (!isset($rivilaadittu)) {
  $rivilaadittu = "";
}

if (!isset($kommentti)) {
  $kommentti = "";
}

if (!isset($ale_peruste)) {
  $ale_peruste = "";
}

if (!isset($jaksotettu)) {
  $jaksotettu = "";
}

if (!isset($korvaavakielto)) {
  $korvaavakielto = "";
}

if (!isset($orvoteikiinnosta)) {
  $orvoteikiinnosta = "";
}

if (!isset($jtspec)) {
  $jtspec = "";
}

if (!isset($paikka)) {
  $paikka = "";
}

if (!isset($perhekielto)) {
  $perhekielto = "";
}

if (!isset($valmistuslinja)) {
  $valmistuslinja = "";
}

if (!isset($rekisterinumero)) {
  $rekisterinumero = "";
}

if (!isset($jo_syotetty_rekisterinumero)) {
  $jo_syotetty_rekisterinumero = "";
}

// Haetaan asiakkaan kieli erilaisten systeemikommenttien k‰‰nt‰mist‰ varten
$query = "SELECT kieli
          FROM asiakas
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tunnus  = '{$laskurow['liitostunnus']}'";
$kielirow = mysql_fetch_assoc(pupe_query($query));
$kieli = $kielirow["kieli"];

if ($rekisterinumero == '') {
  //Kun yhtion_parametri myyntitilausrivi_rekisterinumero == K, k‰yttˆliittym‰st‰ on mahdollisuus syˆtt‰‰ tilausriville rekisterinumero.
  //K‰yttˆliittym‰ss‰ on 2 valintaa. input rekisterinumero ja dropdown jo_syotetty_rekisterinumero. Jos rekisterinumero on syˆtetty, yliajaa se dropdownin
  $rekisterinumero = $jo_syotetty_rekisterinumero;
}

$rekisterinumero = strtoupper($rekisterinumero);

if ($toim == "REKLAMAATIO" and $yhtiorow['reklamaation_kasittely_tuoteperhe'] == 'U') {
  $perhekielto = "Y";
}

// Jos valmistuksien_kasittely on Y, voidaan k‰yttˆliittym‰st‰ antaa valmistuslinja
if ($toim == "VALMISTAVARASTOON" and $yhtiorow['valmistuksien_kasittely'] == 'Y' and $valmistuslinja == '') {
  // Jos ei olla pakotettu valmistuslinjaa, otetaan default tuotteelta tai sitten otsikolta
  $valmistuslinja = $laskurow['kohde'] == "" ? $trow['valmistuslinja'] : $laskurow['kohde'];
}

unset($korvaavan_hinta_ylaraja_halvin_alehinta);
unset($korvaavan_hinta_ylaraja_halvin_hinta);
unset($korvaavan_hinta_ylaraja_halvin_ale);
unset($korvaavan_hinta_ylaraja_halvin_netto);

if ($toim == 'EXTENNAKKO' or (isset($toim_kutsu) and $toim_kutsu == 'EXTENNAKKO')) {
  // Tarkistetaan onko hinta annettu k‰sin, jos on niin k‰ytet‰‰n sit‰, muussa tapauksessa haetaan ennakkohinta
  $hinta = (empty($hinta)) ? laske_ext_ennakko_hinta($trow, $laskurow) : $hinta;

  if (!empty($kukarow['extranet']) and $hinta == '') {
    $hinta = 0.000001;
    $ale1 = 100;
    $netto = '';
  }
  else {
    $netto = '';
    $ale1 = 0;
  }
}

if (!function_exists("tuoteperhe_reku")) {
  function tuoteperhe_reku($esiisatuoteno, $isatuoteno, $tuoteperhe, $kaikkituotteet, $perhelisa, $kieltolisa, $lisaarivi_debug) {
    global $yhtiorow, $kukarow;

    //Haetaan tuoteperheen lapset ja lis‰t‰‰n ne $tuoteperhe ja $kaikkituotteet arraysiin
    $query = "SELECT tuoteperhe.*
              FROM tuoteperhe
              JOIN tuote ON tuoteperhe.yhtio = tuote.yhtio and tuoteperhe.tuoteno = tuote.tuoteno
              WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
              and tuoteperhe.isatuoteno = '$isatuoteno'
              $perhelisa
              $kieltolisa
              ORDER BY tuoteperhe.tuoteno, tuoteperhe.tunnus";
    $perheresult = pupe_query($query);

    while ($perherow = mysql_fetch_assoc($perheresult)) {

      $perherow["tuoteno"] = strtoupper($perherow["tuoteno"]);

      if ($lisaarivi_debug==1) echo "Tuoteperheen lapsi: $perherow[tuoteno]<br>";

      //T‰t‰ tuoteperhett‰ halutaan myyd‰
      if (!in_array($perherow["tuoteno"], $tuoteperhe)) $tuoteperhe[] = $perherow["tuoteno"]; // lis‰t‰‰n tuoteno arrayseen
      if (!in_array($perherow["tuoteno"], $kaikkituotteet)) $kaikkituotteet[] = $perherow["tuoteno"]; // lis‰t‰‰n tuoteno arrayseen
    }

    return array($tuoteperhe, $kaikkituotteet);
  }
}

if (!function_exists("tuoteperhe_reku2")) {
  function tuoteperhe_reku2($tuoteno, $isat_array, $kaikki_array, $kerroin_array, $perhelisa, $kieltolisa, $lisaarivi_debug, $ohita_kerays_array) {
    global $yhtiorow, $kukarow;

    $perhearray = array();
    $tuoteno   = strtoupper($tuoteno);

    if (!in_array($tuoteno, $isat_array)) {
      $isat_array[] = $tuoteno;

      // Haetaan tuoteperheen lapset ja lis‰t‰‰n ne $isa_array arrayseen
      $query = "SELECT tuoteperhe.*
                FROM tuoteperhe
                JOIN tuote ON tuoteperhe.yhtio = tuote.yhtio and tuoteperhe.tuoteno = tuote.tuoteno
                WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                and tuoteperhe.isatuoteno = '$tuoteno'
                $perhelisa
                $kieltolisa
                ORDER BY tuoteperhe.tuoteno, tuoteperhe.tunnus";
      $perheresult = pupe_query($query);

      while ($perherow = mysql_fetch_assoc($perheresult)) {
        $perherow["tuoteno"] = strtoupper($perherow["tuoteno"]);

        $kaikki_array[]      = $perherow["tuoteno"]; // lis‰t‰‰n tuoteno arrayseen
        $kerroin_array[]    = $tuoteno."#!°!#".$perherow["kerroin"];
        $ohita_kerays_array[] = $perherow['ohita_kerays'];

        $mikaisa = $perherow["tuoteno"];
        $tmp_kaikki_array  = $kaikki_array;
        $tmp_kerroin_array = $kerroin_array;

        krsort($tmp_kaikki_array);
        krsort($tmp_kerroin_array);

        $perherow["kerroin"] = 1;

        // Lasketaan kerroin rekursiivisesti taaksep‰in
        foreach ($tmp_kerroin_array as $ke_ind => $ke_kerr) {
          list($ker_isa, $ke_ker) = explode("#!°!#", $ke_kerr);

          if ($ke_ker <= 0) {
            $ke_ker = 1;
          }

          if ($mikaisa == $tmp_kaikki_array[$ke_ind]) {
            $mikaisa = $ker_isa;
            $perherow["kerroin"] *= $ke_ker;
          }
        }

        $perhearray[] = $perherow;
      }
    }
    return array($perhearray, $isat_array, $kaikki_array, $kerroin_array, $ohita_kerays_array);
  }
}

if (isset($varasto) and $lisaarivi_debug == 1) echo "Myyd‰‰n varastoista: ", var_dump($varasto), "<br>";

// katotaan oletuspaikka ja myyd‰‰n tietenkin sielt‰ jos niin halutaan
if (isset($olpaikalta) and $olpaikalta != '') {
  $olquery = "SELECT concat_ws('#!°!#',hyllyalue,hyllynro,hyllyvali,hyllytaso) olpaikka
              FROM tuotepaikat
              WHERE yhtio  = '$kukarow[yhtio]'
              AND tuoteno  = '$trow[tuoteno]'
              AND oletus  != ''
              LIMIT 1";
  $olpares = pupe_query($olquery);
  $olparow = mysql_fetch_assoc($olpares);

  $paikka  = $olparow["olpaikka"];
}

// P‰iv‰m‰‰rien oikeellisuustsekki
list($aikatsk_v, $aikatsk_k , $aikatsk_p) = explode("-", $toimaika);

$aikatsk_k = (int) $aikatsk_k;
$aikatsk_p = (int) $aikatsk_p;
$aikatsk_v = (int) $aikatsk_v;

if ($toimaika != "0000-00-00" and !checkdate($aikatsk_k, $aikatsk_p, $aikatsk_v)) {
  $toimaika = date("Y-m-d");
  if ($toim == "YLLAPITO") {
    $toimaika = "0000-00-00"; // Yll‰pitosoppareissa toimaika tarkoittaa sopimuksen poikkeavaa alkup‰iv‰‰ ja nolla on default
  }
}

list($aikatsk_v, $aikatsk_k , $aikatsk_p) = explode("-", $kerayspvm);

$aikatsk_k = (int) $aikatsk_k;
$aikatsk_p = (int) $aikatsk_p;
$aikatsk_v = (int) $aikatsk_v;

if ($kerayspvm != "0000-00-00" and !checkdate($aikatsk_k, $aikatsk_p, $aikatsk_v)) {
  $kerayspvm = date("Y-m-d");
  if ($toim == "YLLAPITO") {
    $kerayspvm = "0000-00-00"; // Yll‰pitosoppareissa kerayspvm tarkoittaa sopimuksen poikkeavaa loppup‰iv‰‰ ja nolla on default
  }
}

// Jos yhtiˆn saldok‰sittely on ajasta riippuvainen
if (!empty($yhtiorow["saldo_kasittely"])) {
  $saldoaikalisa = $kerayspvm;
}
else {
  $saldoaikalisa = "";
}

// Muutetaan pilkut pisteiksi
$kpl  = str_replace(',', '.', $kpl);
$hinta  = str_replace(',', '.', $hinta);

// lis‰t‰‰n alvit (jos hintamuunnos p‰‰ll‰)
if ($trow['alv'] < 500 and isset($tilausrivi_alvillisuus) and $tilausrivi_alvillisuus == "E" and $yhtiorow["alv_kasittely"] == '' and $yhtiorow["alv_kasittely_hintamuunnos"] == 'o' and !is_array($hyvityssaanto_hinta_array)) {
  $hinta = round($hinta * (1+$trow['alv']/100), $yhtiorow['hintapyoristys']);
}
// poistetaan alvit (jos hintamuunnos p‰‰ll‰)
if ($trow['alv'] < 500 and isset($tilausrivi_alvillisuus) and $tilausrivi_alvillisuus == "K" and $yhtiorow["alv_kasittely"] == 'o' and $yhtiorow["alv_kasittely_hintamuunnos"] == 'o' and !is_array($hyvityssaanto_hinta_array)) {
  $hinta = round($hinta / (1+$trow['alv']/100), $yhtiorow['hintapyoristys']);
}

if ($laskurow['tila'] == 'O' or $laskurow['tila'] == 'K') {
  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    ${'ale'.$alepostfix} = str_replace(',', '.', ${'ale'.$alepostfix});
    ${'alkupera_ale'.$alepostfix} = ${'ale'.$alepostfix};
  }
}
else {
  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    ${'ale'.$alepostfix} = str_replace(',', '.', ${'ale'.$alepostfix});
    ${'alkupera_ale'.$alepostfix} = ${'ale'.$alepostfix};
  }
}

// Alkuper‰iset tiedot talteen
$alkupera_trow  = $trow;
$alkupera_kpl   = $kpl;
$alkupera_hinta = $hinta;
$alkupera_netto = $netto;
$alkupera_alv   = $alv;

$saldoyhteensa           = 0; // t‰h‰n summaillaan saldoa yhteens‰
$lapsiyhteensa           = 0;
$korvaavan_isaa_myyty    = 0;
$perheid_lisaarivi       = 0;
$lop                     = "";
$tuoteperhe              = array(); // Tuoteperheiden lapset menev‰t t‰nne
$kaikkituotteet          = array(strtoupper($tuoteno)); // t‰h‰n arrayseen lis‰t‰‰n kaikki tuotteet ketjusta
$isa_array               = array();
$var                     = strtoupper($var); // varri isoks
$netto                   = strtoupper($netto); // netto isoks
$myyntierahuomatus       = FALSE;
$ostoerahuomatus         = FALSE;
$myy_erikoistoim_ok      = TRUE;
$org_kpl                 = "";
$suoratoimitus_splittaus = FALSE;
$paivita_myyntitilaus    = FALSE;
$tlisatied_lisa          = "";

// N‰ihin arraysiin tulee sitten myyt‰viss‰ olevat tuotteet, kappaleet ja varastopaikat
$myy_tuoteno         = array();
$myy_kpl             = array();
$myy_hyllyalue       = array();
$myy_hyllynro        = array();
$myy_hyllytaso       = array();
$myy_hyllyvali       = array();
$myy_hyllyalue_temp  = "";
$myy_hyllynro_temp   = "";
$myy_hyllyvali_temp  = "";
$myy_hyllytaso_temp  = "";
$myy_varasto         = array();
$myy_erikoistoimitus = array();
$myy_kpl_myyty       = array();
$myy_hintakerr       = array();
$myy_alekerroi       = array();
$myy_era             = array();
$myy_tyyppi          = array();
$myy_lapsi_ein       = array();
$myy_ohita_kerays    = array();
$lisatyt_rivit1      = array();
$lisatyt_rivit2      = array();
$lisatyt_perheet     = array();
$korkieltokommi      = array();
$korlisakommi        = array();
$myyntierahuom       = array();
$ostoerahuom         = array();

// Kun tullaan jt-/ennakko-selauksesta, niin kytket‰‰n erikoistoimitus_alarajasumma-ominaisuus pois
if (isset($from_tee_jt_tilaus) and $from_tee_jt_tilaus === TRUE) {
  $myy_erikoistoim_ok = FALSE;
}

if ($laskurow['tila'] == 'O' or $laskurow['tila'] == 'K') {
  $rivityyppi = 'O';  // Ostotilausrivi
}
elseif ($laskurow["tilaustyyppi"] == 'E') {
  $rivityyppi = 'E'; // Ennakkotilausrivi
}
elseif ($laskurow["tila"] == 'T') {
  $rivityyppi = 'T'; // Tarjousrivi
}
elseif ($laskurow["tila"] == 'G') {
  $rivityyppi = 'G';  // Siirtolistatilausrivi
}
elseif ($laskurow["tila"] == 'S') {
  $rivityyppi = 'G';  // Sis‰inen tyˆm‰‰r‰ysrivi
}
elseif ($laskurow["tila"] == 'V' and $perheid2 == -100) {
  $rivityyppi = 'W';  // Valmistustilausrivi (Valmiste)
  $perheid2 = 0;
}
elseif ($laskurow["tila"] == 'V') {
  $rivityyppi = 'V';  // Valmistustilausrivi
}
elseif ($laskurow["tila"] == 'Z') {
  $rivityyppi = 'Z';  // Terminaalitoimitus
}
elseif ($laskurow["tila"] == 'B') {
  $rivityyppi = 'B';  // Ostoskori
}
elseif ($laskurow["tila"] == 'F') {
  $rivityyppi = 'F';  // Tallennettu web-tilaus/-tarjous
}
elseif ($laskurow["tila"] == '0') {
  $rivityyppi = '0';  // Soppari
}
else {
  $rivityyppi = 'L'; // Myyntitilausrivi
}

// Onko p‰‰ss‰tty varasto josta myyd‰‰n
if (isset($varasto) and is_array($varasto) and count($varasto) > 0) {
  $varasto = $varasto;
}
elseif (isset($varasto) and (int) $varasto > 0) {
  $varasto = array((int) $varasto);
}
elseif (isset($laskurow["varasto"]) and (int) $laskurow["varasto"] > 0) {
  $varasto = array((int) $laskurow["varasto"]);
}
elseif ($rivityyppi == 'L' and $laaja_toim_kasittely) {
  $params = array(
    'asiakas_tunnus' => $laskurow['liitostunnus'],
    'toimipaikka_tunnus' => $laskurow['yhtio_toimipaikka'],
  );
  $varasto = sallitut_varastot($params);
}
elseif ($laskurow["tila"] != "G" and isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0) {
  $varasto = explode(",", $kukarow["varasto"]);
}
else {
  unset($varasto);
}

if ((!isset($from_tee_jt_tilaus) or !$from_tee_jt_tilaus) and
  $kpl > 0 and
  (($trow['myynti_era'] > 0 and in_array($yhtiorow['myyntiera_pyoristys'], array('K', 'S'))) or $trow['minimi_era'] > 0) and
  (((in_array($rivityyppi, array('L', 'E')) and in_array($yhtiorow['myyntiera_pyoristys'], array('K', 'S'))) or ($rivityyppi == 'G' and $yhtiorow['myyntiera_pyoristys'] == 'S')) and (!isset($varastosiirtotyyppi) or !in_array($varastosiirtotyyppi, array('P', 'K')))) and
  $rivitunnus == 0 and
  (in_array($toim, array('RIVISYOTTO', 'PIKATILAUS', 'ENNAKKO', 'EXTRANET', 'SIIRTOLISTA')) or ($kutsuja == "EDITILAUS" and $edi_tyyppi != "magento"))
) {

  $org_kpl = $kpl;

  if ($kpl < $trow['minimi_era']) {
    $kpl = $trow['minimi_era'];
  }

  if (in_array($yhtiorow['myyntiera_pyoristys'], array('K', 'S'))) {
    if ($kpl <= $trow['myynti_era']) {
      $kpl = $trow['myynti_era'];
    }
    else {
      $kokonaisluku = round($kpl / $trow['myynti_era']);

      $test = $kokonaisluku * $trow['myynti_era'];

      if ($kpl > $test) {
        $test = ($kokonaisluku + 1) * $trow['myynti_era'];
      }

      $kpl = (float) $test;
    }
  }

  if ($org_kpl != $kpl) $myyntierahuomatus = TRUE;
}

if ($kpl > 0 and $rivityyppi == 'O' and $yhtiorow['ostoera_pyoristys'] == 'K') {
  $org_kpl = $kpl;

  // haetaan tuotteen toimittajan takaata pakkauskoko tai jotain
  // Otetaan $trow laajemmin, tuotteen_toimittaja tiedot ja 2 avainsanaa
  $query = "SELECT
            tuote.*,
            tt.tunnus tt_tunnus,
            tt.toim_tuoteno,
            if(tt.osto_era = 0, 1, tt.osto_era) AS osto_era,
            tt.pakkauskoko,
            tt.ostohinta,
            tt.valuutta,
            tt.osto_alv
            FROM tuote
            JOIN tuotteen_toimittajat AS tt ON (tt.yhtio = tuote.yhtio
              AND tuote.tuoteno   = tt.tuoteno
              AND tt.liitostunnus = '$laskurow[liitostunnus]')
            WHERE tuote.yhtio     = '{$kukarow["yhtio"]}'
            AND tuote.tuoteno     = '{$trow["tuoteno"]}'";
  $ttresult = pupe_query($query);
  $ttrow = mysql_fetch_assoc($ttresult);

  if ($kpl <= $ttrow['osto_era']) {
    $kpl = $ttrow['osto_era'];
  }
  else {

    $jaannos = 0;
    $pakkauskoko = 0;

    $_pakkaukset = tuotteen_toimittajat_pakkauskoot($ttrow['tt_tunnus'], 'desc');

    foreach ($_pakkaukset as $_pak) {
      $pakkausarray[] = $_pak[0];
    }

    $pakkausarray[] = $ttrow["osto_era"]; // Oletus jos ei muuta lˆydy.

    foreach ($pakkausarray as $pak) {
      (float) $jaannos = fmod($kpl, $pak);

      if ($jaannos == 0 or $kpl > $pak) {
        $pakkauskoko = $pak;
        break;
      }
      if ($kpl > $pak) $pakkauskoko = $pak;
    }

    if ($jaannos != 0) {
      // pyˆristet‰‰n pakkauskokokoon
      $kpl = ceil($kpl / $pakkauskoko) * $pakkauskoko;
    }

  }

  if ($org_kpl != $kpl) $ostoerahuomatus = TRUE;
}

$toimittamatta = $kpl; //N‰in paljon ollaan toimittamassa

if ($lisaarivi_debug == 1) echo "Tilattu m‰‰r‰: $toimittamatta<br>";

// Jos kyseess‰ on rivi joka halutaan siirt‰‰ varastosiirtona (siirtolista) var = S
$kohde_varasto_update = "";
$paivita_hyllyt_varastosiirto = false;

if (substr($paikka, 0, 3) == "°°S") {
  $paivita_hyllyt_varastosiirto = true;
  $var = "S";

  //$varasto_tunnukset: 0 = tyhj‰, 1 = avainsana.tunnus, 2 = lahdevarasto.tunnus, 3 = kohdevarasto.tunnus
  $varasto_tunnukset = explode('°°S', $paikka);

  $toimittajan_tunnus = $varasto_tunnukset[1];
  $kohde_ja_lahde_varasto = hae_mahdolliset_siirto_varastot(array($varasto_tunnukset[3]));
  $kohde_ja_lahde_varasto = search_array_key_for_value_recursive($kohde_ja_lahde_varasto, 'tunnus', $varasto_tunnukset[1]);
  $kohde_ja_lahde_varasto = $kohde_ja_lahde_varasto[0];

  $tuotepaikkojen_saldot = varaston_tuotepaikkojen_saldo($tuoteno, array($kohde_ja_lahde_varasto['kohde_tunnus']));
  $tuotepaikka = $tuotepaikkojen_saldot[0];
  $kohde_varasto_update = " tilausrivin_lisatiedot.kohde_hyllyalue = '{$tuotepaikka['hyllyalue']}',
                            tilausrivin_lisatiedot.kohde_hyllynro = '{$tuotepaikka['hyllynro']}',
                            tilausrivin_lisatiedot.kohde_hyllytaso = '{$tuotepaikka['hyllytaso']}',
                            tilausrivin_lisatiedot.kohde_hyllyvali = '{$tuotepaikka['hyllyvali']}',";

  $tuotepaikkojen_saldot = varaston_tuotepaikkojen_saldo($tuoteno, array($kohde_ja_lahde_varasto['lahde_tunnus']));
  $tuotepaikka = $tuotepaikkojen_saldot[0];

  $myy_hyllyalue_temp = $tuotepaikka['hyllyalue'];
  $myy_hyllynro_temp  = $tuotepaikka['hyllynro'];
  $myy_hyllytaso_temp = $tuotepaikka['hyllytaso'];
  $myy_hyllyvali_temp = $tuotepaikka['hyllyvali'];

  if (!empty($perheid)) {
    $query = "SELECT *
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = {$laskurow['tunnus']}
              AND perheid = {$perheid}";
    $result = pupe_query($query);

    while ($lapsirivi = mysql_fetch_assoc($result)) {
      $tuotepaikkojen_saldot = varaston_tuotepaikkojen_saldo($tuoteno, array($varasto_tunnukset[3]));
      $kohdevarasto_tuotepaikka = $tuotepaikkojen_saldot[0];

      $tuotepaikkojen_saldot = varaston_tuotepaikkojen_saldo($tuoteno, array($varasto_tunnukset[2]));
      $lahdevarasto_tuotepaikka = $tuotepaikkojen_saldot[0];

      $updatelisa_lapsi = $lapsirivi['var'] == 'P' ? "varattu = tilkpl," : "";

      $query = "UPDATE tilausrivi
                SET var     = 'S',
                {$updatelisa_lapsi}
                hyllyalue   = '{$lahdevarasto_tuotepaikka['hyllyalue']}',
                hyllynro    = '{$lahdevarasto_tuotepaikka['hyllynro']}',
                hyllyvali   = '{$lahdevarasto_tuotepaikka['hyllyvali']}',
                hyllytaso   = '{$lahdevarasto_tuotepaikka['hyllytaso']}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = {$lapsirivi['tunnus']}";
      pupe_query($query);

      $query = "UPDATE tilausrivin_lisatiedot
                SET toimittajan_tunnus = '{$toimittajan_tunnus}',
                kohde_hyllyalue      = '{$kohdevarasto_tuotepaikka['hyllyalue']}',
                kohde_hyllynro       = '{$kohdevarasto_tuotepaikka['hyllynro']}',
                kohde_hyllyvali      = '{$kohdevarasto_tuotepaikka['hyllyvali']}',
                kohde_hyllytaso      = '{$kohdevarasto_tuotepaikka['hyllytaso']}'
                WHERE yhtio          = '{$kukarow['yhtio']}'
                AND tilausrivitunnus = {$lapsirivi['tunnus']}";
      pupe_query($query);
    }
  }
}
elseif ($var == "S" and substr($paikka, 0, 3) != "°°S" and substr($paikka, 0, 3) != "°°S") {
  $paivita_hyllyt_varastosiirto = false;
  $var = "";
  $toimittajan_tunnus = "";
  $kohde_varasto_update = " tilausrivin_lisatiedot.kohde_hyllyalue = '0',
                            tilausrivin_lisatiedot.kohde_hyllynro  = '0',
                            tilausrivin_lisatiedot.kohde_hyllytaso = '0',
                            tilausrivin_lisatiedot.kohde_hyllyvali = '0',";


  if (!empty($perheid) and !empty($paikka) and substr($paikka, 0, 3) != "°°°") {
    $paikka_temp = explode('#!°!#', $paikka);
    $_varasto = kuuluukovarastoon($paikka_temp[0], $paikka_temp[1]);
    $toimittajan_tunnus = '';
    $_var = '';
    $_hae_tuotepaikka = true;
  }
  elseif (!empty($perheid) and empty($paikka) and substr($paikka, 0, 3) != "°°°") {
    $_varasto = $laskurow['varasto'];
    $toimittajan_tunnus = '';
    $_var = 'P';
    $_hae_tuotepaikka = true;
  }
  elseif (substr($paikka, 0, 3) == "°°°") {
    $toimittajan_tunnus  = substr($paikka, 3);
    $lahdevarasto_tuotepaikka = array(
      'hyllyalue' => '',
      'hyllynro' => '',
      'hyllyvali' => '',
      'hyllytaso' => '',
    );
    $_var = 'T';
    $_hae_tuotepaikka = false;
  }

  if (!empty($perheid)) {
    $query = "SELECT *
              FROM tilausrivi
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = {$laskurow['tunnus']}
              AND perheid = {$perheid}";
    $result = pupe_query($query);

    while ($lapsirivi = mysql_fetch_assoc($result)) {
      if ($_hae_tuotepaikka) {
        $tuotepaikkojen_saldot = varaston_tuotepaikkojen_saldo($lapsirivi['tuoteno'], array($_varasto));
        $lahdevarasto_tuotepaikka = $tuotepaikkojen_saldot[0];
      }

      $updatelisa_lapsi = $_var == 'P' ? "varattu = 0," : "";

      $query = "UPDATE tilausrivi
                SET var = '{$_var}',
                {$updatelisa_lapsi}
                hyllyalue   = '{$lahdevarasto_tuotepaikka['hyllyalue']}',
                hyllynro    = '{$lahdevarasto_tuotepaikka['hyllynro']}',
                hyllyvali   = '{$lahdevarasto_tuotepaikka['hyllyvali']}',
                hyllytaso   = '{$lahdevarasto_tuotepaikka['hyllytaso']}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = {$lapsirivi['tunnus']}";
      pupe_query($query);

      $query = "UPDATE tilausrivin_lisatiedot
                SET toimittajan_tunnus = '{$toimittajan_tunnus}',
                kohde_hyllyalue      = '0',
                kohde_hyllynro       = '0',
                kohde_hyllyvali      = '0',
                kohde_hyllytaso      = '0'
                WHERE yhtio          = '{$kukarow['yhtio']}'
                AND tilausrivitunnus = {$lapsirivi['tunnus']}";
      pupe_query($query);
    }
  }
}

// Jos kyseess‰ on rivi joka halutaan tilata ihan normaalisti joltain toimittajalta
if (substr($paikka, 0, 3) == "°°°") {
  $var          = "T";
  $toimittajan_tunnus  = substr($paikka, 3);
  $paikka         = "#!°!#";
}
elseif ($var == "T" and substr($paikka, 0, 3) != "°°°" and substr($paikka, 0, 3) != "!!!") {
  $var         = "";
  $toimittajan_tunnus  = "";
}

if (substr($paikka, 0, 3) == "!!!") {
  $var          = "U";
  $toimittajan_tunnus  = substr($paikka, 3);
  $paikka         = "";
}
elseif ($var == "U" and substr($paikka, 0, 3) != "!!!") {
  $var         = "";
  $toimittajan_tunnus  = "";
}

// Jos kyseess‰ on rivi joka halutaan tilata ihan normaalisti joltain toimittajalta
if (substr($paikka, 0, 3) == "°°V") {
  $var          = "R";
  $paikka         = substr($paikka, 3);
}
elseif ($var == "R" and substr($paikka, 0, 3) != "°°V" and substr($paikka, 0, 3) != "°°V") {
  $var         = "";
  $toimittajan_tunnus  = "";
}

$perhelisa = "";
$tuoteperhe_tyyppi = "P";

//Haetaan myˆs tuoteperheet tai reseptit, t‰ss‰ parametrit niille.
if ($laskurow['tila'] == 'V' and $var != "W") {
  $perhelisa = " and tuoteperhe.tyyppi='R' ";
  $tuoteperhe_tyyppi = "R";
}
elseif ($var == "W" and $laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V') {
  // Asiaakkaallevalmistus ja halutaan myyd‰ rivi varastosta
  $perhelisa = " and tuoteperhe.tyyppi in ('P','') ";
  $var     = "";

  if ($perheid == 0 or $perheid == $rivitunnus) {
    $rivityyppi = 'L'; // Myyntitilausrivi
  }
}
else {
  $perhelisa = " and tuoteperhe.tyyppi in ('P','') ";
}

// vientikieltok‰sittely:
// +maa tarkoittaa ett‰ myynti on kielletty t‰h‰n maahan ja sallittu kaikkiin muihin
// -maa tarkoittaa ett‰ ainoastaan t‰h‰n maahan saa myyd‰
// eli n‰ytet‰‰n vaan tuotteet jossa vienti kent‰ss‰ on tyhj‰‰ tai -maa.. ja se ei saa olla +maa
$kieltolisa = "";
$uusin_korvaava_tuote = '';

if ($laskurow["toim_maa"] != "" and $laskurow["tila"] != 'V') {
  $kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$laskurow[toim_maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$laskurow[toim_maa]%' ";
}

//Tutkitaan onko t‰m‰ hyvitysrivi
if ((float) $kpl < 0 and trim($var) == "") {
  //Laitetaan varriksi Y jotta ei tarvitse menn‰ myyntihaaraan
  $var = "Y";
}

// Jos varastosiirto tehd‰‰n saman varaston sis‰ll‰, niin siiret‰‰n aina oletupaikalle
// Eli otetaan tuote joltain muulta paikalta kuin oletuspaikalta
if ($laskurow["tila"] == 'G' and $laskurow["clearing"] == $laskurow["varasto"]) {
  $notfromvarasto = " and tuotepaikat.oletus = '' ";
}
else {
  $notfromvarasto = "";
}

// Jos skripti (kululaskut.inc) p‰‰ss‰‰ tilausrivilinkin, niin laitetaan se mukaan kyselyyn
if (isset($tilausrivilinkki) and $tilausrivilinkki > 0) {
  $tlisatied_lisa = " , tilausrivilinkki = '".mysql_real_escape_string($tilausrivilinkki)."' ";
}

if ($rivityyppi != 'O' and trim($var) == "" and $trow["tuoteno"] != "") {

  // Katsotaan onko maksuehto j‰lkivaatimus, koska silloin ei toimiteta erikoisvarastoista vaikka erikoistoimitus_alarajasumma ylittyisi
  if (!isset($jvtsekres) and $myy_erikoistoim_ok) {
    $apuqu = "SELECT tunnus
              FROM maksuehto
              WHERE yhtio  = '$kukarow[yhtio]'
              AND tunnus   = '$laskurow[maksuehto]'
              AND jv      != ''";
    $jvtsekres = pupe_query($apuqu);

    if (mysql_num_rows($jvtsekres) > 0) {
      $myy_erikoistoim_ok = FALSE;
    }
  }

  // Vientiasiakkaille ei myyd‰ erikoisvarastoista jos toimitustapa on nouto
  if (!isset($tntsekres) and $myy_erikoistoim_ok) {
    $apuqu = "SELECT tunnus
              FROM toimitustapa
              WHERE yhtio  = '$kukarow[yhtio]'
              AND selite   = '$laskurow[toimitustapa]'
              AND nouto   != ''";
    $tntsekres = pupe_query($apuqu);

    if (mysql_num_rows($tntsekres) > 0) {
      $myy_erikoistoim_ok = FALSE;
    }
  }

  if ($lisaarivi_debug==1) echo "Menn‰‰n myyntihaaraan: $tuoteno<br>";

  $kvkasittely = in_array($yhtiorow["tee_automaattinen_osto_myyntitilaukselta"], array('H', 'W')) ? false : true;

  // Etsit‰‰n korvaavia tuotteita
  if ($tuoteno != "" and $trow["ei_saldoa"] == "" and $korvaavakielto == "" and $rivityyppi == 'L' and $tapa != "MYYVASTAAVA" and $laskurow['tilaustyyppi'] != '9' and $kvkasittely) {
    $korvaavat_kiellot   = ""; // Korvaavia tuotteita myyd‰‰n, vastaavia ei
    $krkaikkimyytavissa  = 0;

    // Tutkitaan ensin saako t‰lle asiakkaalle myyd‰ korvaavia t‰st‰ tuoteryhm‰st‰
    $kk_laji = korvaavat_kiellot($laskurow['ytunnus'], $trow['osasto'], $trow['try']);

    // Ei myyd‰ mit‰‰n korvaavia
    if ($kk_laji == 'K') {
      $korvaavat_kiellot = "KIELTO";
    }
    // Korvaavia tuotteita myyd‰‰n, mik‰li korvaavuusketjusta lˆytyy tilattu m‰‰r‰
    elseif ($kk_laji == 'O') {
      $korvaavat_kiellot = "KORVAAVAT";
    }
    // Korvaavia ja Vastaavia tuotteita myyd‰‰n
    elseif ($kk_laji == 'X') {
      $korvaavat_kiellot = "VASTAAVAT";
    }
    // Korvaavia ja Vastaavuusketjun p‰‰tuotetta myyd‰‰n
    elseif ($kk_laji == 'Y') {
      $korvaavat_kiellot = "PAA_VASTAAVA";
    }

    // Otetaan alkuper‰isen tuotteen hinta talteen
    list($korva_isa_hinta, , $korva_isa_ale_kaikki, , ) = alehinta($laskurow, $trow, $kpl, '', '', '', '', '', ($myy_tyyppi[$indeksi] == 'LAPSITUOTE' ? 'X' : ''));

    // Tehd‰‰n homma siten, ett‰ etsit‰‰n kaikki korvaavat jo t‰ss‰ vaiheessa arrayseen
    // Haetaan korvaavuusketju tuotteelle
    require_once 'korvaavat.class.php';
    $korvaavat = new Korvaavat($tuoteno);

    // Korvaavuusketjun j‰rjestyksess‰ pienemp‰‰n p‰in, eli p‰‰tuotetta kohden
    $options = array('korvaavuusketjun_jarjestys' => $yhtiorow['korvaavuusketjun_jarjestys']);
    $korvaavat_tuotteet = $korvaavat->tuotteet($options);

    // Vastaavien automaattinen myynti, eli haetaanko vastaavat tai Vastaavuusketjun p‰‰tuotteen automaattinen myynti
    if ($korvaavat_kiellot == "VASTAAVAT" or $korvaavat_kiellot == "PAA_VASTAAVA") {

      // Haetaan vastaavuusketjut korvaavat ketjun p‰‰tuotteella
      require_once 'vastaavat.class.php';

      // Jos tilatulla tuotteella ei korvaavia lˆydy, haetaan vastaavat myyt‰v‰ll‰ tuotteella.
      // Muulloin etsit‰‰n vastaavat lˆydetyn korvaavuusketjun p‰‰tuotteen mukaan.
      $options = array('skippaa_vaihtoehtoiset' => TRUE);

      if (empty($korvaavat_tuotteet)) {
        $vastaavat = new Vastaavat($tuoteno, $options);
      }
      else {
        $vastaavat = new Vastaavat($korvaavat->paatuote(), $options);
      }

      if ($vastaavat->onkovastaavia()) {

        $options = array('vastaavuusketjun_jarjestys' => $yhtiorow['vastaavuusketjun_jarjestys'], 'skippaa_vaihtoehtoiset' => TRUE);

        // Loopataan kaikki tuotteen vastaavuusketjut
        foreach (explode(",", $vastaavat->getIDt()) as $ketju) {

          // Haetaan tuotteet ketjukohtaisesti
          $_tuotteet = $vastaavat->tuotteet($ketju, $options);

          // Lis‰t‰‰n lˆydetyt vastaavat mahdollisten myyt‰vien joukkoon
          foreach ($_tuotteet as $_tuote) {

            if (!isset($korvaavat_tuotteet[$_tuote['tuoteno']])) {
              $korvaavat_tuotteet[$_tuote['tuoteno']] = $_tuote;
            }

            if ($korvaavat_kiellot == "PAA_VASTAAVA") {
              // Jos eka natsaa niin ok.
              break 2;
            }
          }
        }
      }
    }

    // Tuotteen korvaavat ja vastaavat ketju, tsekataan paljonko on myyt‰viss‰ ja yhteens‰ myyt‰viss‰
    foreach ($korvaavat_tuotteet as $kindeksi => $krow2) {

      list(, , $krmyytavissa) = saldo_myytavissa($krow2["tuoteno"], $jtspec, '', '', '', '', '', '', '', $saldoaikalisa);

      $korvaavat_tuotteet[$kindeksi]["myytavissa"] = $krmyytavissa;
      $krkaikkimyytavissa += $krmyytavissa;
    }

    // Korvaavia tuotteita myyd‰‰n, mik‰li korvaavuusketjusta lˆytyy tilattu m‰‰r‰
    if ($korvaavat_kiellot == "KORVAAVAT" and $krkaikkimyytavissa < $kpl) {
      // Skipataan kaikki korvaavat
      $korvaavat_tuotteet = array();
    }

    // Tuotteen korvaavat ja vastaavat ketju
    foreach ($korvaavat_tuotteet as $krow2) {

      //katotaan t‰ss‰ v‰liss‰ saako tuotetta myyd‰ asiakkaalle - eli jos ei saa myyd‰ siirryt‰‰n seuraavaan tuotteeseen (jos on)...
      if (!saako_myyda_private_label($laskurow["liitostunnus"], $krow2["tuoteno"])) {
        continue;
      }

      list($korva_hinta, $korva_netto, $korva_ale_kaikki, , ) = alehinta($laskurow, $krow2, $kpl, '', '', '', '', '', ($myy_tyyppi[$indeksi] == 'LAPSITUOTE' ? 'X' : ''));

      $if_ale_korva_isa_chk1 = $korva_isa_hinta;
      $if_ale_korva_isa_chk2 = $korva_hinta;

      $korva_isa_ale_kaikki['netto'] = $netto;
      $korva_isa_ale_kaikki['erikoisale'] = $laskurow['erikoisale'];
      $if_ale_korva_isa_chk1 *= generoi_alekentta_php($korva_isa_ale_kaikki, 'M', 'kerto');

      $korva_ale_kaikki['netto'] = $netto;
      $korva_ale_kaikki['erikoisale'] = $laskurow['erikoisale'];
      $if_ale_korva_isa_chk2 *= generoi_alekentta_php($korva_ale_kaikki, 'M', 'kerto');

      // Ei saa myyd‰ korvaavia tuotteita eik‰ vastaavia
      if ($korvaavat_kiellot == "KIELTO") {
        if ($krow2["myytavissa"] > 0 and strtoupper($krow2["tuoteno"]) != strtoupper($tuoteno)) {
          $korkieltokommi[] = strtoupper($krow2["tuoteno"]." (".sprintf("%.2f", $krow2["myyntihinta"]).")");
        }
      }
      else {

        // Myyd‰‰nkˆ aina halvin tuote
        // Asiakas saa aina halvemman hinnan mukaan tuotteen, mik‰li alkuper‰inen tuote on P toistuva tai puute.
        if ($yhtiorow["korvaavan_hinta_ylaraja"] == 'MIN') {

          // T‰h‰n hintaan asiakas saisi tuotteen
          if ($korva_hinta > 0) {
            $korvaavan_hinta_ylaraja_hinta = $korva_hinta * generoi_alekentta_php($korva_ale_kaikki, 'M', 'kerto');
          }
          else {
            unset($korvaavan_hinta_ylaraja_hinta);
          }

          // Otetaan koko ketjun halvin hinta/alennus talteen (ei poistetut tuotteet)
          if (isset($korvaavan_hinta_ylaraja_hinta) and $krow2["status"] != "P" and (!isset($korvaavan_hinta_ylaraja_halvin_alehinta) or $korvaavan_hinta_ylaraja_halvin_alehinta > $korvaavan_hinta_ylaraja_hinta)) {
            $korvaavan_hinta_ylaraja_halvin_alehinta = $korvaavan_hinta_ylaraja_hinta;
            $korvaavan_hinta_ylaraja_halvin_hinta = $korva_hinta;
            $korvaavan_hinta_ylaraja_halvin_ale = $korva_ale_kaikki;
            $korvaavan_hinta_ylaraja_halvin_netto = $korva_netto;
          }

          if ($krow2["myytavissa"] > 0 and !in_array(strtoupper($krow2["tuoteno"]), $kaikkituotteet)) {
            $kaikkituotteet[] = strtoupper($krow2["tuoteno"]); // lis‰t‰‰n tuoteno arrayseen
            if ($krow2["tyyppi"] == "vastaava") $korlisakommi[strtoupper($krow2["tuoteno"])] = t("HUOM: Tuote %s on tilap‰isesti loppu. Toimitetaan vastaava tuote.", $kieli, $trow["tuoteno"]);
            else $korlisakommi[strtoupper($krow2["tuoteno"])] = t("HUOM: Korvaa tuotteen", $kieli)." $alkupera_trow[tuoteno]";
          }
        }
        elseif ($yhtiorow["korvaavan_hinta_ylaraja"] != '' and $yhtiorow["korvaavan_hinta_ylaraja"] != 'MIN' and ($if_ale_korva_isa_chk1) != 0 and (($if_ale_korva_isa_chk2) / ($if_ale_korva_isa_chk1)) > (1+($yhtiorow["korvaavan_hinta_ylaraja"]/100))) {
          //Korvaavien myynti kielletty koska korvaava tuote on liian kallis
          if ($krow2["myytavissa"] > 0 and strtoupper($krow2["tuoteno"]) != strtoupper($tuoteno)) {
            $korkieltokommi[] = strtoupper($krow2["tuoteno"]." (".sprintf("%.2f", $krow2["myyntihinta"]).")");
          }
        }
        else {
          //Yritet‰‰n myyd‰ korvaava tuote
          $korvattiin_korvaavalla = "HIIOHEI!";

          if (!in_array(strtoupper($krow2["tuoteno"]), $kaikkituotteet)) {
            $kaikkituotteet[]=strtoupper($krow2["tuoteno"]); // lis‰t‰‰n tuoteno arrayseen

            if ($krow2["tyyppi"] == "vastaava") $korlisakommi[strtoupper($krow2["tuoteno"])] = t("HUOM: Tuote %s on tilap‰isesti loppu. Toimitetaan vastaava tuote.", $kieli, $trow["tuoteno"]);
            else $korlisakommi[strtoupper($krow2["tuoteno"])] = t("HUOM: Korvaa tuotteen", $kieli)." $alkupera_trow[tuoteno]";
          }
        }
      }

      $uusin_korvaava_tuote = $krow2['tuoteno'];
    }

    if (count($korkieltokommi) == 1) {
      $korkieltokommi = t("Voit tilata korvaavaa tuotetta", $kieli).": ".$korkieltokommi[0];
    }
    elseif (count($korkieltokommi) > 1) {
      $korkieltokommi = t("Voit tilata korvaavia tuotteita", $kieli).": ".implode(", ", $korkieltokommi);
    }
  }

  // Haetaan tuoteperheet, jos $paikka on erisuuri kuin tyhj‰ niin vaihdetaan vain jonkun tuotteen paikkaa eik‰ p‰ivitet‰ tuoteperheit‰
  // Tai jos muokataan tuoteperheen lasta ei menn‰ uudestaan t‰h‰n haaraan
  if ($perheid == 0 and $perhekielto == "") {

    foreach ($kaikkituotteet as $isatuoteno) {
      $riikoko = 1;
      $isa_array[] = $isatuoteno;

      for ($isa=0; $isa < $riikoko; $isa++) {
        list($tuoteperhe, $kaikkituotteet) = tuoteperhe_reku($isatuoteno, $isa_array[$isa], $tuoteperhe, $kaikkituotteet, $perhelisa, $kieltolisa, $lisaarivi_debug);

        // Ollaanko rekursiivisia vai ei
        if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y" and ($laskurow['tila'] != 'V' or $var == "W")) {
          $isa_array   = array_merge($isa_array, $tuoteperhe);
          $isa_array   = array_unique($isa_array);
          $riikoko   = count($isa_array);
        }
      }

      if (count($tuoteperhe) == 0 and $laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V' and $rivityyppi == "V") {
        // Asiakkaallevalmistuskeissi, resepit‰ ei lˆydy joten teemme t‰st‰ myyntirivin
        $rivityyppi = 'L'; // Myyntitilausrivi
      }
    }
  }
  elseif ($perheid != 0) {
    $query = "SELECT ei_nayteta
              FROM tuoteperhe
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND isatuoteno = (SELECT tuoteno FROM tilausrivi WHERE tunnus = $perheid) LIMIT 1";
    $einayres = mysql_fetch_assoc(pupe_query($query));
  }

  //Kaikkien arrayden indeksien ‰iti
  $i = 0;

  // Sitten k‰yd‰‰n kaikkien tuotteiden saldot l‰pi kaikilta varastopaikoilta
  foreach ($kaikkituotteet as $tuoteno) {

    $query = "SELECT ei_saldoa, status
              FROM tuote
              WHERE yhtio = '$kukarow[yhtio]'
              and tuoteno = '$tuoteno'";
    $zres = pupe_query($query);
    $zrow = mysql_fetch_assoc($zres);

    if ($zrow["ei_saldoa"] == "") {

      if ($orvoteikiinnosta != '') {
        $orvot = 0;
      }
      else {
        // katotaan jos meill‰ on tuotteita varaamassa saldoa joiden varastopaikkaa ei en‰‰ ole olemassa...
        list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($tuoteno, 'ORVOT', '', '', '', '', '', '', $laskurow["toim_maa"], $saldoaikalisa);

        $orvot *= -1;
      }

      // K‰yd‰‰n l‰pi tuotepaikat
      if (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and $paikka != "EIERAAVIELA#!°!#EIEI" and $rivityyppi != 'W' and ($laskurow["tila"] != 'V' or $paikka != "")) {
        $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                  tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
                  sarjanumeroseuranta.sarjanumero era,
                  concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
                  varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi, varastopaikat.erikoistoimitus_alarajasumma
                  FROM tuote
                  JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                  JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                    AND varastopaikat.tunnus                = tuotepaikat.varasto)
                  JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
                  and sarjanumeroseuranta.tuoteno           = tuote.tuoteno
                  and sarjanumeroseuranta.hyllyalue         = tuotepaikat.hyllyalue
                  and sarjanumeroseuranta.hyllynro          = tuotepaikat.hyllynro
                  and sarjanumeroseuranta.hyllyvali         = tuotepaikat.hyllyvali
                  and sarjanumeroseuranta.hyllytaso         = tuotepaikat.hyllytaso
                  and sarjanumeroseuranta.myyntirivitunnus  = 0
                  and sarjanumeroseuranta.era_kpl          != 0
                  WHERE tuote.yhtio                         = '$kukarow[yhtio]'
                  and tuote.tuoteno                         = '$tuoteno'
                  $notfromvarasto
                  GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16
                  ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
      }
      else {

        $limit = "";

        if (isset($varasto) and !is_array($varasto) and $rivityyppi == 'E') {
          $limit = " LIMIT 1 ";
        }

        $query = "SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
                  tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso, tuotepaikat.prio,
                  concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
                  varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi, varastopaikat.erikoistoimitus_alarajasumma, '' era
                  FROM tuote
                  JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
                  JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
                    AND varastopaikat.tunnus = tuotepaikat.varasto)
                  WHERE tuote.yhtio          = '$kukarow[yhtio]'
                  and tuote.tuoteno          = '$tuoteno'
                  $notfromvarasto
                  ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta
                  $limit";
      }

      $zres = pupe_query($query);

      //N‰hd‰‰n vaivaa jottaa saadaan paikat sortattua oletuspaikka ensin ja sitten myyt‰viss‰olevan m‰‰r‰n mukaan laskevassa j‰rjestyksess‰
      $zzrow     = array();
      $zzrowlask   = 0;

      while ($zrow = mysql_fetch_assoc($zres)) {

        // Saldolliset tuotteet
        list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($tuoteno, $jtspec, '', '', $zrow["hyllyalue"], $zrow["hyllynro"], $zrow["hyllyvali"], $zrow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $zrow["era"]);

        if ($lisaarivi_debug==1) echo "Saldo_myyt‰viss‰ funktio sanoo $tuoteno: $zrow[hyllyalue]-$zrow[hyllynro]-$zrow[hyllyvali]-$zrow[hyllytaso]: $saldo, $hyllyssa, $myytavissa kappaletta (Er‰: $zrow[era]) PVM: $saldoaikalisa<br>";

        // Tutkitaan myyd‰‰nkˆ tietty‰ jo varattua lis‰varustetta, silloin annetaan AINA riitt‰v‰ saldo
        if ($perheid2 > 0 and $perheid2 != $rivitunnus and strtoupper($zrow["hyllyalue"]."#!°!#".$zrow["hyllynro"]."#!°!#".$zrow["hyllyvali"]."#!°!#".$zrow["hyllytaso"]) == strtoupper($paikka)) {
          $myytavissa = $toimittamatta;
        }

        // hoidetaan pois problematiikka jos meill‰ on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
        if ($orvot > 0) {
          if ($myytavissa >= $orvot) {
            // poistaan orpojen varaamat tuotteet t‰lt‰ paikalta
            $myytavissa = $myytavissa - $orvot;
            $orvot = 0;
          }
          elseif ($orvot > $myytavissa) {
            // poistetaan niin paljon orpojen saldoa ku voidaan
            $orvot = $orvot - $myytavissa;
            $myytavissa = 0;
          }
        }

        if ($zrow['varasto'] == $kukarow['oletus_varasto'] and $zrow['oletus'] != '') {
          // K‰ytt‰j‰n oletusvarasto ja oletuspaikka, prio 1
          $zrow['oletus'] = 3;
        }
        elseif ($zrow['varasto'] == $kukarow['oletus_varasto']) {
          // K‰ytt‰j‰n oletusvarasto ja oletuspaikka, prio 2
          $zrow['oletus'] = 2;
        }
        elseif ($zrow['oletus'] != '') {
          // K‰ytt‰j‰n oletusvarasto ja oletuspaikka, prio 1 jos k‰ytt‰j‰lle ei oo oletusvarastoa, muuten prio 3
          $zrow['oletus'] = 1;
        }

        $zzrow[$zzrowlask]["myytavissa"]  = $myytavissa;
        $zzrow[$zzrowlask]["ei_saldoa"]    = $zrow["ei_saldoa"];
        $zzrow[$zzrowlask]["oletus"]    = $zrow["oletus"];
        $zzrow[$zzrowlask]["varasto"]    = $zrow["varasto"];
        $zzrow[$zzrowlask]["era"]      = $zrow["era"];
        $zzrow[$zzrowlask]["varastomaa"]  = $zrow["varastomaa"];
        $zzrow[$zzrowlask]["varastotyyppi"]  = $zrow["varastotyyppi"];
        $zzrow[$zzrowlask]["hyllyalue"]    = $zrow["hyllyalue"];
        $zzrow[$zzrowlask]["hyllynro"]    = $zrow["hyllynro"];
        $zzrow[$zzrowlask]["hyllyvali"]    = $zrow["hyllyvali"];
        $zzrow[$zzrowlask]["hyllytaso"]    = $zrow["hyllytaso"];
        $zzrow[$zzrowlask]["prio"]      = $zrow["prio"] == 0 ? PHP_INT_MAX : $zrow["prio"];
        $zzrow[$zzrowlask]['erikoistoimitus_alarajasumma']  = $zrow['erikoistoimitus_alarajasumma'];

        if ($lisaarivi_debug==1) echo "Myyt‰viss‰ $tuoteno paikalta $zrow[hyllyalue]-$zrow[hyllynro]-$zrow[hyllyvali]-$zrow[hyllytaso]: $myytavissa kappaletta<br>";

        // tilatulla tuotteella oli saldoa.. laitetaan lippu p‰‰lle, ett‰ huomataan osatoimituskeisi oikein..
        if ($tuoteno == $alkupera_trow["tuoteno"]) {
          $korvaavan_isaa_myyty = 1; // laitetaan lippu p‰‰lle ett‰ tiedet‰‰n ett‰ meneekˆ tilattua tuotetta ja korvaavaa tuotetetta samalla tilauksella
        }

        $zzrowlask++;
      }

      // Sortataan varastopaikat prio, myyt‰viss‰ j‰rkk‰‰n
      if (!function_exists('zzrowprio')) {
        function zzrowprio($a, $b) {
          if ($a["prio"] < $b["prio"]) {
            return -1;
          }
          elseif ($a["prio"] > $b["prio"]) {
            return 1;
          }
          elseif ($a["myytavissa"] < $b["myytavissa"]) {
            return 1;
          }
          elseif ($a["myytavissa"] > $b["myytavissa"]) {
            return -1;
          }
          else {
            return 0;
          }
        }
      }

      // Sortataan varastopaikat oletus, prio, myyt‰viss‰ j‰rkk‰‰n
      if (!function_exists('zzrowcmp')) {
        function zzrowcmp($a, $b) {
          if (strcmp($a["oletus"], $b["oletus"]) < 0) {
            return 1;
          }
          elseif (strcmp($a["oletus"], $b["oletus"]) > 0) {
            return -1;
          }
          elseif ($a["prio"] < $b["prio"]) {
            return -1;
          }
          elseif ($a["prio"] > $b["prio"]) {
            return 1;
          }
          elseif ($a["myytavissa"] < $b["myytavissa"]) {
            return 1;
          }
          elseif ($a["myytavissa"] > $b["myytavissa"]) {
            return -1;
          }
          else {
            return 0;
          }
        }
      }

      if (!function_exists('chk_varaston_saldo')) {
        function chk_varaston_saldo($arr, $kpl) {
          global $varasto;

          //jos $varasto on asetettu ja se ei ole array niin asetetaan se arrayksi
          //koska if ($chk_row['varasto'] == $chk_oletus_var and (!isset($varasto) or in_array($chk_row['varasto'], $varasto))) {
          //heitt‰‰ noticea jos in_array param 2 on jotain muuta kuin array
          if (isset($varasto) and !is_array($varasto)) {
            $varasto = array();
          }

          reset($arr);

          // Katotaan miss‰ varastossa "oletuspaikka" on.
          // T‰ss‰ tulee zzrowcmp funkkarin sorttauksen mukaan eka paikka
          $chk_oletus_var = $arr[0]['varasto'];

          $chk_saldo      = 0;
          $checked_saldo = false;

          // Loopataaan tuotepaikat
          foreach ($arr as $indx => $chk_row) {

            // riitt‰‰kˆ "parhaimman" varaston saldo (K‰ytet‰‰n vain jos "paras" varasto on sallittu)
            if ($chk_row['varasto'] == $chk_oletus_var and (!isset($varasto) or in_array($chk_row['varasto'], $varasto))) {
              $chk_saldo += $chk_row['myytavissa'];

              if ($chk_saldo >= $kpl) {
                $checked_saldo = true;
                break;
              }
            }
          }

          reset($arr);

          // "Parhaassa" varastossa oli yhteens‰ riitt‰v‰sti saldoa
          if ($checked_saldo) {

            $var_paras = array();

            foreach ($arr as $indx => $chk_row) {
              // Otetaan "parhaimman" varaston sis‰ll‰ olevat tuotepaikat talteen
              if ($chk_row['varasto'] == $chk_oletus_var and (!isset($varasto) or in_array($chk_row['varasto'], $varasto))) {
                $var_paras[] = $chk_row;
                unset($arr[$indx]);
              }
            }

            $chk_yksi_paikka = FALSE;

            // koitetaan lˆyt‰‰ onko kaikki myyt‰viss‰ yhdelt‰ paikalta
            foreach ($var_paras as $indx => $chk_row) {

              // Jos oli tarpeeksi myyt‰viss‰ kappaleita, siirret‰‰n se jonon ensimm‰iseksi
              if ($chk_row['myytavissa'] >= $kpl) {
                unset($var_paras[$indx]);
                array_unshift($var_paras, $chk_row);
                $chk_yksi_paikka = TRUE;
                break;
              }
            }

            // jos ei lˆydy vain yht‰ paikkaa miss‰ riitt‰‰ saldo, sortataan varastopaikat prioriteetti j‰rkk‰‰n
            if (!$chk_yksi_paikka) {
              // Sortataan varastopaikat prioriteetti j‰rkk‰‰n
              usort($var_paras, "zzrowprio");
            }

            // Laitetaan parhaimman varaston paikat k‰rkeen
            $arr = array_merge($var_paras, $arr);
          }
          else {

            // Riitt‰‰kˆ jonkun toisen varaston saldo?
            $chk_distinct_varasto = array();

            // loopataan distinct varasto
            foreach ($arr as $indx => $chk_row) {
              // Ei oteta "parasta" varastoa mukaan, koska tid‰mme, ett‰ siin‰ ei t‰ss‰ tapauksessa ole riitt‰v‰sti kamaa
              if (!isset($chk_distinct_varasto[$chk_row['varasto']]) and $chk_row['varasto'] != $chk_oletus_var) {
                $chk_distinct_varasto[$chk_row['varasto']] = $chk_row['varasto'];
              }
            }

            reset($arr);

            foreach ($chk_distinct_varasto as $varastosta) {

              $varaston_saldo = 0;
              $varasto_set   = '';

              foreach ($arr as $indx => $chk_row) {

                // Onko loopattavassa varastossa tarpeeksi myyt‰viss‰ olevia kappaleita
                if ($varastosta == $chk_row['varasto'] and (!isset($varasto) or in_array($chk_row['varasto'], $varasto))) {
                  $varaston_saldo += $chk_row['myytavissa'];

                  if ($varaston_saldo >= $kpl) {
                    $varasto_set = $chk_row['varasto'];
                    break;
                  }
                }
              }

              // Jos lˆytyi jokin varasto miss‰ on tarpeeksi myyt‰viss‰ olevia kappaleita
              if ($varasto_set != '') {
                reset($arr);

                $var_varasto = array();

                foreach ($arr as $indx => $chk_row) {

                  // Otetaan kaikki varastopaikat talteen mitk‰ kuuluvat varastoon jossa on tarpeeksi myyt‰viss‰ olevia kappaleita
                  if ($chk_row['varasto'] == $varasto_set) {
                    $var_varasto[] = $chk_row;
                    unset($arr[$indx]);
                  }
                }

                $chk_yksi_paikka = FALSE;

                // koitetaan lˆyt‰‰ onko kaikki myyt‰viss‰ yhdelt‰ paikalta
                foreach ($var_varasto as $indx => $chk_row) {
                  if ($chk_row['myytavissa'] >= $kpl) {
                    unset($var_varasto[$indx]);
                    array_unshift($var_varasto, $chk_row);
                    $chk_yksi_paikka = TRUE;
                    break;
                  }
                }

                // Jos ei lˆytynyt yht‰ paikkaa mist‰ oltaisiin voitu myyd‰ kaikki
                if (!$chk_yksi_paikka) {
                  // Sortataan varastopaikat prioriteetti j‰rkk‰‰n
                  usort($var_varasto, "zzrowprio");
                }

                $arr = array_merge($var_varasto, $arr);
                break;
              }
            }
          }

          return $arr;
        }
      }

      // Sortataan tuotepaikat
      $oma_maa = array();
      $muu_maa = array();

      foreach ($zzrow as $zzrow_rivi) {
        // jos lˆydet‰‰n varasto toimitusosoitteen maasta, k‰ytet‰‰n sit‰ eka
        if ($laskurow["toim_maa"] == $zzrow_rivi["varastomaa"] and $laskurow["toim_maa"] != "" and $zzrow_rivi["varastomaa"] != "") {
          $oma_maa[] = $zzrow_rivi;
        }
        else {
          $muu_maa[] = $zzrow_rivi;
        }
      }

      // sortataan ostajan maan varastopaikat "oletus, prio, myyt‰viss‰"-j‰rkk‰‰n
      if (count($oma_maa) > 0) usort($oma_maa, "zzrowcmp");

      // sortataan muiden maiden varastopaikat "oletus, prio, myyt‰viss‰"-j‰rkk‰‰n
      if (count($muu_maa) > 0) usort($muu_maa, "zzrowcmp");

      if ($yhtiorow['varaston_splittaus'] == 'K') {
        /*
        Jos $yhtiorow['varaston_splittaus'] parametri on 'K' (yritet‰‰nkˆ myyd‰ p‰‰asiassa yhdest‰ varastosta tuotetta)
        a) Katotaan miss‰ oletuspaikka, riitt‰‰kˆ oletuspaikan varaston saldo (jos on oikeus varastoon jossa oletus on). Jos, katotaan ensin lˆytyykˆ tarvittava m‰‰r‰ joltain yhdelt‰ tietylt‰ paikalta.
        b) Riitt‰‰kˆ jonkun toisen varaston saldo (ne mihin on oikeus). Jos, katotaan ensin lˆytyykˆ tarvittava m‰‰r‰ joltain yhdelt‰ tietylt‰ paikalta.
        c) Jos a) tai b) ei natsaa niin palautetaan paikat saman kaavan mukaan kuin tapauksessa 1.
        */

        // sortataan ostajan maan varastopaikat
        if (count($oma_maa) > 0) $oma_maa = chk_varaston_saldo($oma_maa, $kpl);

        // sortataan muiden maiden varastopaikat
        if (count($muu_maa) > 0) $muu_maa = chk_varaston_saldo($muu_maa, $kpl);
      }

      // yhdistet‰‰n arrayt
      $zzrow = array_merge($oma_maa, $muu_maa);

      if (count($zzrow) > 0) {

        // Jos tuotteella on tuotepaikkoja
        foreach ($zzrow as $zrow) {

          if ($lisaarivi_debug == 1) {
            echo "<pre>
                  ({$yhtiorow["puute_jt_oletus"]} == O
                  or $varataan_saldoa == EI
                  or ({$zrow["myytavissa"]} > 0
                      and (($rivityyppi == 'L'
                          and {$kukarow['kassamyyja']} == ''
                          and $myy_erikoistoim_ok and {$zrow['varastotyyppi']} == 'E'
                          and {$zrow['erikoistoimitus_alarajasumma']} > 0
                          and (float) {$trow['myyntihinta']} >= (float) {$zrow['erikoistoimitus_alarajasumma']})
                        or {$zrow["varastotyyppi"]} == ''
                        or {$zrow["varastotyyppi"]} == 'V'
                        or (is_array($varasto)
                          and in_array({$zrow["varasto"]}, "; var_dump($varasto); echo ")
                          and {$laskurow["varasto"]} == 0)
                        or {$laskurow["varasto"]} == {$zrow["varasto"]})
                    )
                  )
                  </pre>
                  <br>";
          }

          if ($yhtiorow["puute_jt_oletus"] == "O"
            or $varataan_saldoa == "EI"
            or ($zrow["myytavissa"] > 0
              and (($rivityyppi == 'L'
                  and $kukarow['kassamyyja'] == ''
                  and $myy_erikoistoim_ok
                  and $zrow['varastotyyppi'] == 'E'
                  and $zrow['erikoistoimitus_alarajasumma'] > 0
                  and (float) $trow['myyntihinta'] >= (float) $zrow['erikoistoimitus_alarajasumma'])
                or $zrow["varastotyyppi"] == ''
                or $zrow["varastotyyppi"] == 'V'
                or (is_array($varasto) and in_array($zrow["varasto"], $varasto)
                  and $laskurow["varasto"] == 0)
                or $laskurow["varasto"] == $zrow["varasto"]
                or ($laskurow["tila"] == "G"
                  and $laskurow["varasto"] == 0)))) {

            if ($lisaarivi_debug == 1) echo "Myyd‰‰n $tuoteno paikalta $zrow[hyllyalue]-$zrow[hyllynro]-$zrow[hyllyvali]-$zrow[hyllytaso]: $zrow[myytavissa] kappaletta<br>";

            // lis‰t‰‰n tuotteen tiedot arrayseen, koska t‰m‰n tuotteen voi myyd‰
            $myy_tuoteno[$i]   = strtoupper($tuoteno);
            $myy_kpl[$i]       = $zrow["myytavissa"];
            $myy_hyllyalue[$i] = $zrow["hyllyalue"];
            $myy_hyllynro[$i]  = $zrow["hyllynro"];
            $myy_hyllyvali[$i] = $zrow["hyllyvali"];
            $myy_hyllytaso[$i] = $zrow["hyllytaso"];
            $myy_varasto[$i]   = $zrow["varasto"];
            $myy_era[$i]      = $zrow["era"];

            if (!in_array(strtoupper($tuoteno), $tuoteperhe)) {

              // Sis‰‰ntulevissa EDI-tilauksissa tehdastilaustuotteita ei saa tilata
              if (isset($kutsuja) and $kutsuja == "EDITILAUS" and $zrow['status'] == 'T') {
                $myy_tyyppi[$i] = "TEHDASTILAUSTUOTE";
              }
              else {
                $myy_tyyppi[$i] = "NORMAALI";
              }

              $saldoyhteensa += $zrow["myytavissa"]; // summaillaan kaikkien isien saldoa yhteens‰.
            }
            else {
              $myy_tyyppi[$i] = "LAPSITUOTE";
              $lapsiyhteensa += $zrow["myytavissa"]; // summaillaan kaikkien lapsien saldoa yhteens‰.
            }

            // jos halutaan myyd‰ erikoisvarastosta ja alarajasumma on syˆtetty ja tuotteen myyntihinta ylitt‰‰ alarajasumman
            if ($rivityyppi == 'L' and $kukarow['kassamyyja'] == '' and $myy_erikoistoim_ok and $zrow['varastotyyppi'] == 'E' and $zrow['erikoistoimitus_alarajasumma'] > 0 and (float) $trow['myyntihinta'] >= (float) $zrow['erikoistoimitus_alarajasumma']) {
              $myy_erikoistoimitus[$i] = true;
            }
            else {
              $myy_erikoistoimitus[$i] = false;
            }

            //Kasvatetaan indeksi‰
            $i++;
          }
        } // end foreach tuotepaikat
      }
      elseif ($yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI") {
        // Jos tuotteella taas ei ole ollenkaan tuotepaikkoja ja myyd‰‰n kuitenkin (eli ei piitata saldoista)

        if ($lisaarivi_debug==1) echo "Tuotteella $tuoteno ei ole yht‰‰n tuotepaikkaa! Myyd‰‰n silti koska saldoista ei nyt piitata!<br>";

        $myy_tuoteno[$i]   = strtoupper($tuoteno);
        $myy_kpl[$i]       = 99999999;
        $myy_hyllyalue[$i] = "";
        $myy_hyllynro[$i]  = "";
        $myy_hyllyvali[$i] = "";
        $myy_hyllytaso[$i] = "";
        $myy_varasto[$i]   = "";
        $myy_era[$i]      = "";

        if (!in_array(strtoupper($tuoteno), $tuoteperhe)) {
          // Sis‰‰ntulevissa EDI-tilauksissa tehdastilaustuotteita ei saa tilata
          if (isset($kutsuja) and $kutsuja == "EDITILAUS" and $zrow['status'] == 'T') {
            $myy_tyyppi[$i] = "TEHDASTILAUSTUOTE";
          }
          else {
            $myy_tyyppi[$i] = "NORMAALI";
          }

          $saldoyhteensa += $toimittamatta; // summaillaan kaikkien isien saldoa yhteens‰.
        }
        else {
          $myy_tyyppi[$i] = "LAPSITUOTE";
          $lapsiyhteensa += $toimittamatta; // summaillaan kaikkien lapsien saldoa yhteens‰.
        }

        //Kasvatetaan ‰iti-indeksi‰
        $i++;
      }
    }
    else {
      // Saldottomat tuotteet
      // lis‰t‰‰n tuotteen tiedot arrayseen, koska t‰m‰n tuotteen voi myyd‰
      $myy_tuoteno[$i]   = strtoupper($tuoteno);
      $myy_kpl[$i]       = 99999999;
      $myy_hyllyalue[$i] = "";
      $myy_hyllynro[$i]  = "";
      $myy_hyllyvali[$i] = "";
      $myy_hyllytaso[$i] = "";
      $myy_varasto[$i]   = "";
      $myy_era[$i]      = "";

      if (!in_array(strtoupper($tuoteno), $tuoteperhe)) {
        $myy_tyyppi[$i] = "NORMAALI";
        $saldoyhteensa += $toimittamatta; // summaillaan kaikkien isien saldoa yhteens‰.
        if ($lisaarivi_debug==1) echo "$tuoteno on is‰tuote/normituote<br>";
      }
      else {
        $myy_tyyppi[$i] = "LAPSITUOTE";
        $lapsiyhteensa += $toimittamatta; // summaillaan kaikkien lapsien saldoa yhteens‰.
        if ($lisaarivi_debug==1) echo "$tuoteno on lapsituote<br>";
      }

      //Kasvatetaan ‰iti-indeksi‰
      $i++;
    }
  } // end foreach myyt‰v‰t tuotteet

  if ($lisaarivi_debug==1) echo "Kaikkiaan myyt‰viss‰: $saldoyhteensa kappaletta<br>";

  //tutkitaan ensin saako t‰lle asiakkaalle myyd‰ osatoimituksen t‰st‰ tuoteryhm‰st‰
  $kko_laji = korvaavat_kiellot($laskurow['ytunnus'], $trow['osasto'], $trow['try'], "Q");

  // jos result on nolla, saadaan myyd‰ osatoimitus tai jos meill‰ on saldoa tarpeeksi
  if ($yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI" or ($kko_laji == "" and $osatoimkielto == "") or $saldoyhteensa >= $toimittamatta) {

    if ($lisaarivi_debug==1) echo "Saadaan myyd‰ osatoimitus tai jos meill‰ on saldoa tarpeeksi. Saldoa $saldoyhteensa Toimittamatta $toimittamatta<br>";

    // nyt meill‰ on $myy_ arrayperheess‰ kaikki tuotteet ja varastopaikat jolta voidaan myyd‰ jotain..
    // loopataan kaikki tuotteet l‰pi

    // Myyd‰‰n tietylt‰ paikalta
    if ($paikka != '') {
      $paikat = explode('#!°!#', $paikka);

      if ($lisaarivi_debug == 1) echo "Halutaan myyd‰ paikalta: $paikat[0]-$paikat[1]-$paikat[2]-$paikat[3]<br>";
      $paikkakelpasi = FALSE;

      foreach ($myy_tuoteno as $i => $tuoteno) {
        if ($yhtiorow["puute_jt_oletus"] == "O" or
          ($trow["sarjanumeroseuranta"] != "E" and $trow["sarjanumeroseuranta"] != "F" and $trow["sarjanumeroseuranta"] != "G" and ($myy_kpl[$i] >= $toimittamatta or $varataan_saldoa == "EI") and $paikat[0] == $myy_hyllyalue[$i] and $paikat[1] == $myy_hyllynro[$i] and $paikat[2] == $myy_hyllyvali[$i] and $paikat[3] == $myy_hyllytaso[$i]) or
          (($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and $myy_era[$i] == $paikat[4] and ($myy_kpl[$i] >= $toimittamatta or $varataan_saldoa == "EI") and $paikat[0] == $myy_hyllyalue[$i] and $paikat[1] == $myy_hyllynro[$i] and $paikat[2] == $myy_hyllyvali[$i] and $paikat[3] == $myy_hyllytaso[$i])) {

          //Otetaan t‰m‰
          $myy_tuoteno   = array();
          $myy_kpl     = array();
          $myy_hyllyalue   = array();
          $myy_hyllynro   = array();
          $myy_hyllytaso   = array();
          $myy_hyllyvali   = array();
          $myy_varasto   = array();
          $myy_kpl_myyty   = array();
          $myy_era    = array();

          $myy_tuoteno[]   = strtoupper($tuoteno);
          $myy_kpl[]      = $toimittamatta;
          $myy_hyllyalue[] = $paikat[0];
          $myy_hyllynro[]  = $paikat[1];
          $myy_hyllyvali[] = $paikat[2];
          $myy_hyllytaso[] = $paikat[3];
          $myy_era[]      = $paikat[4];

          $paikkakelpasi   = TRUE;
          break;
        }
      }

      // Jos paikkaa ei lˆytynytk‰‰n tai jos sen saldo ei riitt‰nyt, niin forgetaboutit.
      if (!$paikkakelpasi) $paikka = "";

    }

    // Myyd‰‰n tietyst‰ varastosta
    if (isset($varasto) and is_array($varasto) and $paikka == '') {

      if ($lisaarivi_debug == 1) echo "Halutaan myyd‰ varastoista ", var_dump($varasto).".<br>";

      foreach ($myy_tuoteno as $i => $tuoteno) {
        if (!in_array($myy_varasto[$i], $varasto) and $myy_varasto[$i] != "") {

          // jos halutaan myyd‰ erikoisvarastosta (josta ei myyd‰ automaattisesti eli tyyppi E), niin ei poisteta sit‰ arraysta
          if ($myy_erikoistoimitus[$i]) {
            continue;
          }

          if ($myy_tyyppi[$i] == "NORMAALI" or $myy_tyyppi[$i] == "TEHDASTILAUSTUOTE") {
            $saldoyhteensa -= $myy_kpl[$i];
          }
          else {
            $lapsiyhteensa -= $myy_kpl[$i];
          }

          unset($myy_tuoteno[$i]);
          unset($myy_kpl[$i]);
          unset($myy_hyllyalue[$i]);
          unset($myy_hyllynro[$i]);
          unset($myy_hyllyvali[$i]);
          unset($myy_hyllytaso[$i]);
          unset($myy_varasto[$i]);
          unset($myy_tyyppi[$i]);
          unset($myy_era[$i]);
        }
      }
    }

    foreach ($myy_tuoteno as $i => $tuoteno) {
      //T‰ss‰ loopataan vain is‰/normaleja tuotteita
      if ($lisaarivi_debug==1) echo "Loopataan $tuoteno, {$myy_tyyppi[$i]}, $toimittamatta<br>";

      if ($myy_tyyppi[$i] == "NORMAALI") {
        // jos ei olla viel‰ toimitettu kaikkea, niin lis‰t‰‰n tilausrivi
        if ($toimittamatta > 0) {

          if ($lisaarivi_debug==1) echo "Normaalituote $tuoteno, toimittamatta $toimittamatta<br>";

          if ($yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI" or $myy_kpl[$i] >= $toimittamatta) {
            $myy_kpl[$i] = $toimittamatta; // jos t‰ll‰ paikalla on saldoa enemm‰n kun tarvitaan, otetaan sitten vaan tarvittava m‰‰r‰
          }

          if (isset($myy_kpl_myyty[$i]) and $myy_kpl[$i]) {
            $myy_kpl_myyty[$i] += $myy_kpl[$i];
          }

          // Status on lis‰t‰‰n is‰ ja perhe jos virheit‰ ei tule
          $perheok   = 0;

          // Is‰ halutaan nyt ainakin myyd‰
          $myynama   = array();
          $myynama[]   = $i;

          if (count($tuoteperhe) > 0) {

            $riikoko       = 1;
            $isat_array     = array();
            $kaikki_array     = array($tuoteno);
            $kerroin_array     = array($tuoteno."#!°!#1");
            $perhearray     = array();
            $ohita_kerays_array = array();

            for ($isa = 0; $isa < $riikoko; $isa++) {
              list($perherow, $isat_array, $kaikki_array, $kerroin_array, $ohita_kerays_array) = tuoteperhe_reku2($kaikki_array[$isa], $isat_array, $kaikki_array, $kerroin_array, $perhelisa, $kieltolisa, $lisaarivi_debug, $ohita_kerays_array);
              $perhearray  = array_merge($perhearray, $perherow);

              if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y" and ($laskurow['tila'] != 'V' or $var == "W")) {
                $riikoko = count($kaikki_array);
              }
            }

            foreach ($perhearray as $perherow) {

              $lapsitoimittamatta = $perherow["kerroin"]*$myy_kpl[$i];

              foreach ($myy_tuoteno as $j => $lapsituoteno) {
                // Tuoteperheiss‰ voi olla samaa tuotetta monta kertaa, siksi and !isset($myy_hintakerr[$j]), muuten tuote on jo k‰sitelty...
                if (strtoupper($lapsituoteno) == strtoupper($perherow["tuoteno"]) and $myy_tyyppi[$j] == "LAPSITUOTE" and !isset($myy_hintakerr[$j])) {

                  // laitetaan lasten kertoimet kuntoon
                  $myy_hintakerr[$j] = $perherow["hintakerroin"];
                  $myy_alekerroi[$j] = $perherow["alekerroin"];
                  $myy_lapsi_ein[$j] = $perherow["ei_nayteta"];
                  $myy_ohita_kerays[$j] = $perherow['ohita_kerays'];

                  // jos ei olla viel‰ toimitettu kaikkea, niin lis‰t‰‰n tilausrivi
                  if ($lapsitoimittamatta > 0 and (($myy_kpl[$j] - $myy_kpl_myyty[$j]) > 0 or $yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI")) {

                    if (($myy_kpl[$j] - $myy_kpl_myyty[$j] >= $lapsitoimittamatta) or $yhtiorow["puute_jt_oletus"] == "O" or $varataan_saldoa == "EI") {
                      // jos t‰ll‰ paikalla on saldoa enemm‰n kun tarvitaan, otetaan sitten vaan tarvittava m‰‰r‰
                      // ja splitataan loput omaan paikaan arrayhin jotta voidaan sit myyd‰ ne seuraavalla kiekalla jos on tarvis
                      $myy_tuoteno[]   = $myy_tuoteno[$j];
                      $myy_kpl[]     = $myy_kpl[$j] - $lapsitoimittamatta;
                      $myy_hyllyalue[] = $myy_hyllyalue[$j];
                      $myy_hyllynro[]   = $myy_hyllynro[$j];
                      $myy_hyllyvali[] = $myy_hyllyvali[$j];
                      $myy_hyllytaso[] = $myy_hyllytaso[$j];
                      $myy_varasto[]   = $myy_varasto[$j];
                      $myy_tyyppi[]   = $myy_tyyppi[$j];
                      $myy_era[]       = $myy_era[$j];

                      $myy_kpl[$j] = $lapsitoimittamatta;
                    }

                    $myy_kpl_myyty[$j] += $myy_kpl[$j];

                    $myynama[] = $j;

                    // hoidellaan saldojen summailua kuntoon
                    $lapsitoimittamatta -= $myy_kpl[$j];
                  }
                }
              }

              if ($lapsitoimittamatta > 0) {
                //t‰m‰n lapsen saldo ei riit‰
                $perheok++;
              }
            }
          }

          // Jos homma on ok niin lis‰t‰‰n rivi
          if ($perheok == 0) {
            foreach ($myynama as $indeksi) {
              //K‰sinsyˆtetyt hinnat ja alennukset eiv‰t p‰de korvaaviin tuotteisiin, eik‰ tuoteperheiden lapsiin
              if (strtoupper($myy_tuoteno[$indeksi]) != strtoupper($alkupera_trow["tuoteno"])) {
                $hinta  = "";

                for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                  ${'ale'.$alepostfix} = "";
                }

                $netto  = "";
                $alv   = "";
              }

              // Haetaan k‰sitelt‰v‰n tuotteen tiedot
              $query = "SELECT *
                        FROM tuote
                        WHERE yhtio='$kukarow[yhtio]' and tuoteno='$myy_tuoteno[$indeksi]'";
              $tres = pupe_query($query);
              $trow = mysql_fetch_assoc($tres);

              $ale_kaikki_array = array();

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                $ale_kaikki_array['ale'.$alepostfix] = ${'ale'.$alepostfix};
              }

              list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $myy_kpl[$indeksi], $netto, $hinta, $ale_kaikki_array, '', '', ($myy_tyyppi[$indeksi] == 'LAPSITUOTE' ? 'X' : ''));
              list($lis_hinta, $lis_alv) = alv($laskurow, $trow, $lis_hinta, $alv, $alehinta_alv);

              // jos ollaan laittamassa muuta tuotetta kun mit‰ tilattiin, lis‰t‰‰n rivikommentti
              if (strtoupper($myy_tuoteno[$indeksi]) != strtoupper($alkupera_trow["tuoteno"]) and $myy_tyyppi[$indeksi] == "NORMAALI") {

                // Haluan tarkistaa alkuper‰isen hinnan ennen korvausta, niin saadaan se halvempi hinta k‰yttˆˆn.
                if ($yhtiorow['korvaavan_hinta_ylaraja'] == 'MIN') {
                  $lis_hinta = $korvaavan_hinta_ylaraja_halvin_hinta;
                  $lis_ale_kaikki = $korvaavan_hinta_ylaraja_halvin_ale;
                  $lis_netto = $korvaavan_hinta_ylaraja_halvin_netto;
                }

                // Tilausrivin systeemikommentti
                if (isset($korlisakommi[strtoupper($myy_tuoteno[$indeksi])]) and  stripos($kommentti, $korlisakommi[strtoupper($myy_tuoteno[$indeksi])]) === FALSE) {
                  $kommentti .= " ".$korlisakommi[strtoupper($myy_tuoteno[$indeksi])];
                }

                // oletetaan, ett‰ meill‰ on $edi_tyyppi muuttuja viritetty editilaus_in.inc failista
                if ((int) $rivinumero != 0 and $edi_tyyppi != "" and $korvaavan_isaa_myyty == 1) {
                  $rivinumero = 0; // jos myyd‰‰n korvaava tuote ja ollaan myyty jo tilattuakin tuotetta, niin ei pistet‰ samaa rivinumeroa..
                }
              }

              if ($yhtiorow['huomautetaanko_poistuvasta'] == 'K' and ($trow['status'] == 'X' or $trow['status'] == 'P')) {
                // Tilausrivin systeemikommentti
                if (stripos($kommentti, t("HUOM:", $kieli)." $alkupera_trow[tuoteno] ".t("on poistuva tuote!", $kieli)) === FALSE) {
                  $kommentti .= " ".t("HUOM:", $kieli)." $alkupera_trow[tuoteno] ".t("on poistuva tuote!", $kieli);
                }
              }

              if ((isset($kutsuja) and $kutsuja == "EDITILAUS") or $lisaarivi_debug == 1) {
                $edi_ulos .= " --> ".t("Myyd‰‰n")." $myy_tuoteno[$indeksi] ".t("paikalta")." $myy_hyllyalue[$indeksi]-$myy_hyllynro[$indeksi]-$myy_hyllyvali[$indeksi]-$myy_hyllytaso[$indeksi]: $myy_kpl[$indeksi] ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite")." ";

                for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                  $edi_ulos .= t("Ale")."$alepostfix ".$lis_ale_kaikki['ale'.$alepostfix]."% ";
                }

                if ($tilauksen_lahde == "finvoice" and $lis_hinta == 0 and !empty($kappalehinta_edista)) {
                  $lis_hinta = $kappalehinta_edista;
                }

                $edi_ulos .= t("Hinta")." $lis_hinta $aperuste ".t("ALV")." $lis_alv% $rivikomm\n";
                if ($lisaarivi_debug == 1) echo "$edi_ulos<br>";
              }

              // Tilatut ja varatut kappaleet
              $tilattu = $myy_kpl[$indeksi];
              $varattu = $myy_kpl[$indeksi];

              if ($yhtiorow['myyntiera_pyoristys'] == 'K' and (isset($kutsuja) and $kutsuja == "EDITILAUS") and $edi_tyyppi != "magento" and $myyntierahuomatus) {
                if (strpos($kommentti, t("HUOM: Rivin m‰‰r‰ on pyˆristetty t‰yteen myyntier‰‰n! Myyntier‰ on", $kieli)) === FALSE) {
                  $kommentti .= " ".t("HUOM: Rivin m‰‰r‰ on pyˆristetty t‰yteen myyntier‰‰n! Myyntier‰ on", $kieli).": $trow[myynti_era]";
                }
              }

              // Jos hinta ja alekertoimet on setattu
              if (isset($myy_hintakerr[$indeksi])) {
                if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
                  $lis_hinta = round($lis_hinta * $myy_hintakerr[$indeksi], 6);
                }
                else {
                  $lis_hinta = round($lis_hinta * $myy_hintakerr[$indeksi], $yhtiorow['hintapyoristys']);
                }
              }

              if (isset($myy_alekerroi[$indeksi])) {
                if ((int) $myy_alekerroi[$indeksi] == -1) {

                  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                    $lis_ale_kaikki['ale'.$alepostfix] = 100.00;
                  }

                }
                else {
                  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                    $lis_ale_kaikki['ale'.$alepostfix] = round($lis_ale_kaikki['ale'.$alepostfix] * $myy_alekerroi[$indeksi], $yhtiorow['hintapyoristys']);
                  }
                }
              }

              // Jos ei varata saldoa niin ei varata saldoa
              if ($varataan_saldoa == "EI" and ($rivityyppi == 'G' or $laskurow['tilaustyyppi'] == 'H')) {
                $varattu = 0;
              }

              if ($yhtiorow['rivinumero_syotto'] != '' and $kutsuja != "EDITILAUS") {
                if (!isset($tilrivinumero) or (int) $tilrivinumero == 0) {
                  $query = "SELECT max(tilaajanrivinro) as trivinro from tilausrivi where yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
                  $triviresult = pupe_query($query);
                  $trivinrow = mysql_fetch_assoc($triviresult);

                  $rivinumero = $trivinrow["trivinro"]+1;
                  $tilrivinumero = $rivinumero;
                }
                else {
                  $rivinumero = $tilrivinumero;
                }
              }

              // jos olemme saaneet tuotenimityksen syotarivist‰ ja sallimme nimityksen muutoksen
              if (isset($tuotenimitys_force) and trim($tuotenimitys_force) != "") {
                $trow["nimitys"] = addslashes($tuotenimitys_force);
              }
              elseif (!empty($yhtiorow["nimityksen_muutos_tilauksella"]) and trim($tuotenimitys) != "") {
                // lis‰t‰‰n saatu nimitys kaikille tuotteille
                $trow["nimitys"] = addslashes($tuotenimitys);
              }

              // Onko tuotteella staattinen tilausrivikommentti
              if ($trow["tilausrivi_kommentti"] != "" and $rivitunnus == 0) {
                // Tilausrivin systeemikommentti
                if (stripos($kommentti, $trow["tilausrivi_kommentti"]) === FALSE) {
                  if (trim($kommentti) != "")  $kommentti = "\n".$kommentti;
                  $kommentti = trim($trow["tilausrivi_kommentti"].$kommentti);
                }
              }

              // jos meill‰ on vanha laadittuaika, niin k‰ytet‰‰ sit‰
              if ($rivilaadittu != "" and $rivitunnus != 0) {
                $vanharivilaadittu = "'$rivilaadittu'";
              }
              else {
                $vanharivilaadittu = "now()";
              }

              $query_ale_lrivi = "";

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                $query_ale_lrivi .= " ale{$alepostfix} = '".$lis_ale_kaikki['ale'.$alepostfix]."',";
              }

              $query_erikoisale_lisa = trim($lis_netto) != 'N' ? " erikoisale = '{$laskurow['erikoisale']}', " : '';

              $var2 = '';

              if ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A') {
                $var2 = $var == 'P' ? '' : 'OK';
              }

              if ($yhtiorow['reklamaation_hinnoittelu'] == 'K' and ($toim == "REKLAMAATIO"  or $toim == "EXTRANET_REKLAMAATIO") and $hyvityssaannon_palautuskielto == "X") {
                $var2 = 'PK';
              }

              $_hyllyalue_tmp = (isset($myy_hyllyalue_temp) and $myy_hyllyalue_temp != '');
              $_hyllynro_tmp = (isset($myy_hyllynro_temp) and $myy_hyllynro_temp != '');
              $_hyllyvali_tmp = (isset($myy_hyllyvali_temp) and $myy_hyllyvali_temp != '');
              $_hyllytaso_tmp = (isset($myy_hyllytaso_temp) and $myy_hyllytaso_temp != '');

              if ($_hyllyalue_tmp and $_hyllynro_tmp and $_hyllyvali_tmp and $_hyllytaso_tmp) {
                $myy_hyllyalue[$indeksi] = $myy_hyllyalue_temp;
                $myy_hyllynro[$indeksi] = $myy_hyllynro_temp;
                $myy_hyllytaso[$indeksi] = $myy_hyllytaso_temp;
                $myy_hyllyvali[$indeksi] = $myy_hyllyvali_temp;
              }

              $query = "INSERT into tilausrivi set
                        hyllyalue       = '$myy_hyllyalue[$indeksi]',
                        hyllynro        = '$myy_hyllynro[$indeksi]',
                        hyllytaso       = '$myy_hyllytaso[$indeksi]',
                        hyllyvali       = '$myy_hyllyvali[$indeksi]',
                        tilaajanrivinro = '$rivinumero',
                        laatija         = '$kukarow[kuka]',
                        laadittu        = $vanharivilaadittu,
                        yhtio           = '$kukarow[yhtio]',
                        tuoteno         = '$myy_tuoteno[$indeksi]',
                        varattu         = '$varattu',
                        yksikko         = '$trow[yksikko]',
                        kpl             = '0',
                        kpl2            = '$kpl2',
                        tilkpl          = '$tilattu',
                        {$query_ale_lrivi}
                        {$query_erikoisale_lisa}
                        alv             = '$lis_alv',
                        netto           = '$lis_netto',
                        hinta           = '$lis_hinta',
                        kerayspvm       = '$kerayspvm',
                        otunnus         = '$kukarow[kesken]',
                        tyyppi          = '$rivityyppi',
                        toimaika        = '$toimaika',
                        kommentti       = '$kommentti',
                        ale_peruste     = '{$GLOBALS['ale_peruste']}',
                        var             = '',
                        var2            = '{$var2}',
                        try             = '$trow[try]',
                        osasto          = '$trow[osasto]',
                        perheid         = '$perheid',
                        perheid2        = '$perheid2',
                        tunnus          = '$rivitunnus',
                        nimitys         = '$trow[nimitys]',
                        jaksotettu      = '$jaksotettu'";

              if ($trow['ei_saldoa'] != '' and $yhtiorow["kerataanko_saldottomat"] == '') {
                $query .= ", keratty = 'saldoton', kerattyaika = now()";
              }

              $result = pupe_query($query);
              $lisatty_tun = mysql_insert_id($GLOBALS["masterlink"]);

              $trivtyrow = array("selite" => "");

              @include "trivityyppivalinta.inc";

              //special case: Kun yhtiˆparametri Valmistuksien_kasittely on asennossa, "valmistuksella voi olla vain yksi valmiste...",
              //excelist‰ sis‰‰nluettavissa valmistusriveiss‰ voi antaa valmistuslinjan ja toivotut p‰iv‰m‰‰r‰t (mikrotilaus.inc)
              //N‰m‰ tiedot tallennetaan v‰liaikaisesti tilausriveille, joista ne sitten kopioidaan omille otsikoilleen.
              if ($valmistuslinja != '') {
                $positio = $valmistuslinja;
              }
              else {
                $positio = $trivtyrow['selite'];
              }

              if (!isset($myy_ohita_kerays[$indeksi])) {
                $myy_ohita_kerays[$indeksi] = "";
              }

              $tlisatied_lisa2 = "";
              $query = "SELECT *
                        FROM tilausrivin_lisatiedot
                        WHERE yhtio          = '$kukarow[yhtio]'
                        and tilausrivitunnus = '$lisatty_tun'";
              $lisatied_res = pupe_query($query);

              if (!isset($myy_erikoistoimitus[$indeksi])) {
                $myy_erikoistoimitus[$indeksi] = "";
              }

              if (!isset($myy_lapsi_ein[$indeksi]) and !isset($einayres["ei_nayteta"])) {
                $myy_lapsi_ein[$indeksi] = "";
              }
              elseif (isset($einayres["ei_nayteta"])) {
                $myy_lapsi_ein[$indeksi] = $einayres["ei_nayteta"];
              }

              $tlisatied_lisa2 = "";

              if ($toim == "YLLAPITO") {
                $tlisatied_lisa2 .= ", sopimuksen_lisatieto1 = '".mysql_real_escape_string($sopimuksen_lisatieto1)."', sopimuksen_lisatieto2 = '".mysql_real_escape_string($sopimuksen_lisatieto2)."'";
              }

              $tlisatied_lisa2 .= in_array($toim, array('RIVISYOTTO', 'PIKATILAUS')) ? ", omalle_tilaukselle = '".mysql_real_escape_string($omalle_tilaukselle)."'" : '';

              if ($laskurow["tila"] == 'G' and isset($kohde_alue) and $kohde_alue != "") {
                $tlisatied_lisa2 .= ", kohde_hyllyalue='".mysql_real_escape_string($kohde_alue)."' , kohde_hyllynro='".mysql_real_escape_string($kohde_nro)."' , kohde_hyllyvali='".mysql_real_escape_string($kohde_vali)."' , kohde_hyllytaso='".mysql_real_escape_string($kohde_taso)."'";
              }

              if (mysql_num_rows($lisatied_res) > 0) {
                $lisatied_row = mysql_fetch_assoc($lisatied_res);

                $query = "UPDATE tilausrivin_lisatiedot
                          SET
                          positio                = if (positio = '' or '$valmistuslinja' != '', '{$positio}', positio),
                          toimittajan_tunnus     = '$toimittajan_tunnus',
                          rekisterinumero        = '{$rekisterinumero}',
                          muutospvm              = now(),
                          muuttaja               = '$kukarow[kuka]',
                          erikoistoimitus_myynti = '$myy_erikoistoimitus[$indeksi]'
                          {$tlisatied_lisa}
                          {$tlisatied_lisa2}
                          WHERE yhtio            = '$kukarow[yhtio]'
                          and tilausrivitunnus   = '$lisatty_tun'";
                $result = pupe_query($query);
              }
              else {
                if ($yhtiorow["tilauksen_jarjestys"] == "M") {
                  $jarjestys = $lisatty_tun;
                }
                else {
                  $jarjestys = 0;
                }

                $autoid = !empty($autoid) ? $autoid : 0;

                $query = "INSERT INTO tilausrivin_lisatiedot
                          SET yhtio = '{$kukarow['yhtio']}',
                          positio                = '{$positio}',
                          toimittajan_tunnus     = '{$toimittajan_tunnus}',
                          ei_nayteta             = '{$myy_lapsi_ein[$indeksi]}',
                          tilausrivitunnus       = '{$lisatty_tun}',
                          erikoistoimitus_myynti = '{$myy_erikoistoimitus[$indeksi]}',
                          ohita_kerays           = '{$myy_ohita_kerays[$indeksi]}',
                          vanha_otunnus          = '{$kukarow['kesken']}',
                          rekisterinumero        = '{$rekisterinumero}',
                          autoid                 = '{$autoid}',
                          jarjestys              = '{$jarjestys}',
                          luontiaika             = now(),
                          laatija                = '{$kukarow['kuka']}'
                          $tlisatied_lisa
                          $tlisatied_lisa2";

                if ($trow["sarjanumeroseuranta"] == "S" and $varattu < 0) {
                  $query .= " , osto_vai_hyvitys='O' ";
                }

                $result = pupe_query($query);
              }

              $lisatyt_rivit1[] = $lisatty_tun;

              if ($myyntierahuomatus) {
                $myyntierahuom[] = $lisatty_tun;
              }

              if ($ostoerahuomatus) {
                $ostoerahuom[] = $lisatty_tun;
              }

              if ($myy_tyyppi[$indeksi] == "NORMAALI") {
                // lapsille oikea perheid
                $perheid_lisaarivi = $lisatty_tun;

                // hoidellaan saldojen summailua kuntoon
                $toimittamatta -= $myy_kpl[$indeksi];
              }

              if (($trow["sarjanumeroseuranta"] == "S" or $trow["sarjanumeroseuranta"] == "T" or $trow["sarjanumeroseuranta"] == "V") and $myy_tyyppi[$indeksi] == "NORMAALI" and $myy_sarjatunnus != '') {
                if ($varattu > 0) {
                  if ($laskurow["tila"] != 'G') {
                    $tunken = "myyntirivitunnus";
                  }
                  else {
                    $tunken = "siirtorivitunnus";
                  }
                }
                else {
                  $tunken = "ostorivitunnus";
                }

                $query = "SELECT sarjanumeroseuranta.tunnus, sarjanumeroseuranta.kaytetty, sarjanumeroseuranta.sarjanumero
                          FROM sarjanumeroseuranta
                          LEFT JOIN tilausrivi ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.$tunken
                          LEFT JOIN lasku ON lasku.yhtio=sarjanumeroseuranta.yhtio and lasku.tunnus=tilausrivi.otunnus
                          WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
                          and sarjanumeroseuranta.tunnus  IN ($myy_sarjatunnus)
                          and sarjanumeroseuranta.tuoteno = '$myy_tuoteno[$indeksi]'
                          and (sarjanumeroseuranta.myyntirivitunnus = 0 or lasku.tila='T')
                          ORDER BY sarjanumeroseuranta.tunnus
                          LIMIT ". (int) abs($varattu);
                $sres = pupe_query($query);

                while ($srow = mysql_fetch_assoc($sres)) {
                  $query = "UPDATE sarjanumeroseuranta
                            SET $tunken      = '$lisatty_tun'
                            WHERE yhtio = '$kukarow[yhtio]'
                            and tunnus  = '$srow[tunnus]'
                            and tuoteno = '$myy_tuoteno[$indeksi]'";
                  $sarjares = pupe_query($query);

                  if ($srow["kaytetty"] == 'K') {
                    $query = "UPDATE tilausrivi
                              SET alv=alv+500
                              WHERE yhtio = '$kukarow[yhtio]'
                              and tunnus  = '$lisatty_tun'
                              and alv     < 500";
                    $sarjares = pupe_query($query);
                  }
                }

                $myy_sarjatunnus = "";
              }

              if ($rivityyppi != "T" and ($trow["sarjanumeroseuranta"] == "E" or $trow["sarjanumeroseuranta"] == "F" or $trow["sarjanumeroseuranta"] == "G") and $myy_era[$indeksi] != "") {

                if ($varattu > 0) {
                  $tunken = "myyntirivitunnus";

                  $query = "SELECT *
                            FROM sarjanumeroseuranta
                            WHERE yhtio     = '$kukarow[yhtio]'
                            and tuoteno     = '$myy_tuoteno[$indeksi]'
                            and hyllyalue   = '$myy_hyllyalue[$indeksi]'
                            and hyllynro    = '$myy_hyllynro[$indeksi]'
                            and hyllytaso   = '$myy_hyllytaso[$indeksi]'
                            and hyllyvali   = '$myy_hyllyvali[$indeksi]'
                            and sarjanumero = '$myy_era[$indeksi]'";
                  $lisa_res = pupe_query($query);
                  $lisa_row = mysql_fetch_assoc($lisa_res);

                  $oslisa = " ostorivitunnus ='$lisa_row[ostorivitunnus]', ";
                }
                else {
                  $tunken = "ostorivitunnus";
                  $oslisa = "";
                }

                $query = "INSERT into sarjanumeroseuranta
                          SET yhtio     = '$kukarow[yhtio]',
                          tuoteno       = '$myy_tuoteno[$indeksi]',
                          lisatieto     = '$lisa_row[lisatieto]',
                          $tunken     = '$lisatty_tun',
                          $oslisa
                          kaytetty      = '$lisa_row[kaytetty]',
                          era_kpl       = '',
                          laatija       = '$kukarow[kuka]',
                          luontiaika    = now(),
                          takuu_alku    = '$lisa_row[takuu_alku]',
                          takuu_loppu   = '$lisa_row[takuu_loppu]',
                          parasta_ennen = '$lisa_row[parasta_ennen]',
                          hyllyalue     = '$myy_hyllyalue[$indeksi]',
                          hyllynro      = '$myy_hyllynro[$indeksi]',
                          hyllytaso     = '$myy_hyllytaso[$indeksi]',
                          hyllyvali     = '$myy_hyllyvali[$indeksi]',
                          sarjanumero   = '$myy_era[$indeksi]'";
                $lisa_res = pupe_query($query);

              }

              if ($myy_tyyppi[$indeksi] == "LAPSITUOTE") {
                //P‰ivitet‰‰n is‰n perhetunnus
                if ($rivityyppi == 'V') {

                  $query = "SELECT tuoteno
                            FROM tilausrivi
                            WHERE yhtio = '$kukarow[yhtio]'
                            and tunnus  = $perheid_lisaarivi
                            and tyyppi  = 'V'";
                  $lisa_res = pupe_query($query);
                  $lisa_row = mysql_fetch_assoc($lisa_res);

                  if ($lisatied_row["porauskuvio"] == "M" or (isset($lapsenlap_array[$lisa_row["tuoteno"]]) and strtoupper($lapsenlap_array[$lisa_row["tuoteno"]]) == strtoupper($lisa_row["tuoteno"]))) {
                    $valmistetyyppi = "M";

                    $query = "UPDATE tilausrivin_lisatiedot
                              SET porauskuvio  = '$valmistetyyppi'
                              WHERE yhtio          = '$kukarow[yhtio]'
                              and tilausrivitunnus = '$perheid_lisaarivi'";
                    $result = pupe_query($query);
                  }
                  else {
                    $valmistetyyppi = "W";
                  }

                  //jos on valmistusprosessi kyseess‰ niin is‰n tyyppi vaihdetaan W:ksi koska sen ei pid‰ varata saldoa!
                  $query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi', tyyppi='$valmistetyyppi' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and tyyppi='V'";
                  $result = pupe_query($query);

                  $query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus=$lisatty_tun";
                  $result = pupe_query($query);
                }
                else {
                  //Hoidetaan saldottomat is‰t samaan varastoon kuin lapsetkin jotta varastosplittaukset eiv‰t menisi hassusti
                  $query = "UPDATE tilausrivi SET hyllyalue='$myy_hyllyalue[$indeksi]' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and hyllyalue=''";
                  $result = pupe_query($query);

                  $query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus in ('$perheid_lisaarivi','$lisatty_tun')";
                  $result = pupe_query($query);

                  $lisatyt_perheet[$perheid_lisaarivi][] = $lisatty_tun;
                }
              }

              $rivitunnus = 0;
              $tuotenimitys_force = "";
              $omalle_tilaukselle = "";
              $ale_peruste = "";

              if ($toimittamatta == 0) {
                $kommentti = "";
                $tuotenimitys = "";
              }

            } // T‰ll‰ rundilla myydyt
          } // Jos tuoteperhett‰ lˆytyi riitt‰v‰sti tai myytiin pelk‰‰ is‰‰
        } // Jos vil‰ on toimittamatta kappaleita
      } // end if toimittamatta > 0
    } // end foreach myy_tuoteno
  } // end saadaanko myyd‰ osatoimitus
}

// jos tultiin ulos loopista, ett‰ meill‰ on toimittamattomia rivej‰, tehd‰‰n niist‰ puuterivi
// tai jos tullaan niin, ett‰ var-kent‰ss‰ on J tai P tai jotain
if ($toimittamatta > 0 or trim($var) != '' or $rivityyppi == 'O') {

  if ($lisaarivi_debug==1) echo "<br>Menn‰‰n puutehaaraan: $tuoteno hinta $hinta toimittamatta: $toimittamatta var: $var laskuntila: $laskurow[tila]<br>";

  //Tyhjennet‰‰n apumuuttuja
  if ($var == "Y") {
    $var = "";
  }

  $puu_tuoteno = array();
  $puu_kpl     = array();
  $puu_hintake = array();
  $puu_alekerr = array();
  $puu_ein     = array();
  $puu_ohiker  = array();
  $puu_var     = array();
  $puu_tyyppi  = array();

  if ($rivityyppi == 'O' and $rivitunnus > 0 and isset($suoratoimitus_rivitun) and $suoratoimitus_rivitun > 0) {
    // Kappalem‰‰r‰‰ ei voida lis‰t‰
    if ($yhtiorow["suoratoim_lisamyynti_osto"] != "K" and
      $toimittamatta >= $suoratoimitus_rivikpl
    ) {
      $toimittamatta = $suoratoimitus_rivikpl;
    }
    elseif ($toimittamatta < $suoratoimitus_rivikpl) {
      // Pienennt‰‰n suoratoimitusostorivin m‰‰r‰‰, eli joudumme splittaamaan myyntirivin samassa suhteessa
      $suoratoimitus_splittaus = TRUE;
      // Pakotetaan loput omalle riville
      $var = "LOJ#!°!#$suoratoimitus_rivikpl";
    }
    else {
      $paivita_myyntitilaus = true;
    }
  }

  if (substr($var, 0, 8) == "LOJ#!°!#" or substr($var, 0, 8) == "LOP#!°!#" or substr($lop, 0, 8) == "LOP#!°!#") {

    if ($lisaarivi_debug == 1) echo "Laitetaan loput rivit jtksi tai puutteeksi (".substr($var, 0, 4).") (".substr($lop, 0, 4).")<br>";

    // Lis‰t‰‰n syˆtetyt ja laitetaan loput JT:seen tai puutteiksi
    $puu_tuoteno[]   = (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? $uusin_korvaava_tuote : $alkupera_trow['tuoteno'];
    $puu_kpl[]     = $toimittamatta;
    $puu_tyyppi[]   = 'NORMAALI';
    $puu_hintake[]  = "X";
    $puu_alekerr[]  = "X";
    $puu_ein[]    = "";
    $puu_ohiker[]  = "";
    $puu_var[]    = $var;

    if (substr($var, 0, 8) == "LOJ#!°!#" and substr($var, 8) - $toimittamatta > 0) {
      $puu_tuoteno[]   = (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? $uusin_korvaava_tuote : $alkupera_trow['tuoteno'];
      $puu_kpl[]     = substr($var, 8) - $toimittamatta;
      $puu_tyyppi[]   = 'NORMAALI';
      $puu_hintake[]  = "X";
      $puu_alekerr[]  = "X";
      $puu_ein[]    = "";
      $puu_ohiker[]  = "";
      $puu_var[]    = "";
    }
    elseif (substr($lop, 0, 8) == "LOP#!°!#" and substr($lop, 8) - $toimittamatta > 0) {
      $puu_tuoteno[]   = (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? $uusin_korvaava_tuote : $alkupera_trow['tuoteno'];
      $puu_kpl[]     = substr($lop, 8) - $toimittamatta;
      $puu_tyyppi[]   = 'NORMAALI';
      $puu_hintake[]  = "X";
      $puu_alekerr[]  = "X";
      $puu_ein[]    = "";
      $puu_ohiker[]  = "";
      $puu_var[]    = "P";
    }
  }
  else {
    if ($lisaarivi_debug == 1) echo "Kaikki puutteeksi var $var<br>";

    $puu_tuoteno[]   = (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? $uusin_korvaava_tuote : $alkupera_trow['tuoteno'];
    $puu_kpl[]     = $toimittamatta;
    $puu_tyyppi[]   = 'NORMAALI';
    $puu_hintake[]  = "X";
    $puu_alekerr[]  = "X";
    $puu_ein[]    = "";
    $puu_ohiker[]  = "";
    $puu_var[]    = $var;
  }

  if ($rivityyppi != 'O' and $perheid == 0 and $perhekielto == "") {

    $riikoko       = 1;
    $isat_array     = array();
    $kaikki_array     = (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? array($uusin_korvaava_tuote) : array($alkupera_trow["tuoteno"]);
    $kerroin_array     = (($yhtiorow['puute_jt_oletus'] == 'B' or $yhtiorow['puute_jt_oletus'] == 'A') and $uusin_korvaava_tuote != '') ? array($uusin_korvaava_tuote."#!°!#1") : array($alkupera_trow["tuoteno"]."#!°!#1");
    $perhearray     = array();
    $ohita_kerays_array = array();

    for ($isa=0; $isa < $riikoko; $isa++) {
      list($perherow, $isat_array, $kaikki_array, $kerroin_array, $ohita_kerays_array) = tuoteperhe_reku2($kaikki_array[$isa], $isat_array, $kaikki_array, $kerroin_array, $perhelisa, $kieltolisa, $lisaarivi_debug, $ohita_kerays_array);
      $perhearray  = array_merge($perhearray, $perherow);

      // Ollaanko rekursiivisia vai ei
      if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y" and ($laskurow['tila'] != 'V' or $var == "W")) {
        $riikoko = count($kaikki_array);
      }
    }

    if (count($perhearray) == 0 and $laskurow["tila"] == 'V' and $laskurow["tilaustyyppi"] == 'V' and $rivityyppi == "V") {
      // Asiakkaallevalmistuskeissi, resepit‰ ei lˆydy joten teemme t‰st‰ myyntirivin
      $rivityyppi = 'L'; // Myyntitilausrivi
    }

    foreach ($perhearray as $perherow) {
      $puu_tuoteno[]  = $perherow["tuoteno"];
      $puu_kpl[]     = $toimittamatta*$perherow["kerroin"];
      $puu_hintake[]  = $perherow["hintakerroin"];
      $puu_alekerr[]  = $perherow["alekerroin"];
      $puu_ein[]     = $perherow["ei_nayteta"];
      $puu_ohiker[]   = $perherow["ohita_kerays"];
      $puu_var[]    = $var;
      $puu_tyyppi[] = 'LAPSITUOTE';
    }
  }
  elseif ($perheid != 0 and !isset($einayres["ei_nayteta"])) {
    $query = "SELECT ei_nayteta
              FROM tuoteperhe
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND isatuoteno = (SELECT tuoteno FROM tilausrivi WHERE tunnus = $perheid) LIMIT 1";
    $einayres = mysql_fetch_assoc(pupe_query($query));
  }

  foreach ($puu_tuoteno as $i => $tuoteno) {
    $kpl = $puu_kpl[$i];
    $var = $puu_var[$i];

    //jos ei olla k‰yty tossa yll‰‰ll‰ niin katotaan onko $einayres["ei_nayteta"] settattu ja laitetaan $puu_ein = $einayres["ei_nayteta"] jos se on viel tyhj‰
    if ($puu_ein[$i] == "" and isset($einayres["ei_nayteta"])) {
      $puu_ein[$i] = $einayres["ei_nayteta"];
    }

    if (!$kvkasittely) {

      // Otetaan tuotteen oletustoimittaja
      $query = "SELECT tuotteen_toimittajat.tehdas_saldo
                FROM tuotteen_toimittajat use index (yhtio_tuoteno)
                JOIN toimi
                ON ( toimi.yhtio = tuotteen_toimittajat.yhtio
                  AND toimi.tunnus                 = tuotteen_toimittajat.liitostunnus)
                WHERE tuotteen_toimittajat.yhtio   = '$kukarow[yhtio]'
                  AND tuotteen_toimittajat.tuoteno = '$tuoteno'
                ORDER BY if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)
                LIMIT 1";

      $toimpaikres = pupe_query($query);
      $krow = mysql_fetch_assoc($toimpaikres);

      if ($kpl > $krow['tehdas_saldo']) {
        $kvkasittely = true;
      }
    }

    $kaannetaan_tuote = true;

    if (in_array($toim, array('RIVISYOTTO', 'PIKATILAUS')) and in_array($yhtiorow["korvaavuusketjun_puutekasittely"], array('K', 'V')) and $puu_ohiker[$i] == '' and $puu_hintake[$i] != "X") {
      list(, , $_myytavissa) = saldo_myytavissa($myy_tuoteno[$i], $jtspec, '', '', '', '', '', '', '', $saldoaikalisa);

      if ($_myytavissa > 0) $kaannetaan_tuote = false;
    }

    // Korvaavuusketjun puutek‰sittely, lis‰t‰‰n ketjun p‰‰tuote puutteeksi
    if ($kaannetaan_tuote and $puu_hintake[$i] != "X" and $tuoteno != "" and $yhtiorow["korvaavuusketjun_puutekasittely"] == "K" and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS') and $laskurow['tilaustyyppi'] != '9' and (empty($rivitunnus) and (!isset($mista_tullaan) or (isset($mista_tullaan) and $mista_tullaan != 'TULOUTUS'))) and $kvkasittely) {
      require_once 'korvaavat.class.php';
      $korvaavat = new Korvaavat($tuoteno);
      $paatuote  = $korvaavat->paatuote();

      if ($paatuote != "") {
        if (strtoupper($paatuote) != strtoupper($tuoteno)) {
          if (stripos($kommentti, t("HUOM: Korvaa tuotteen", $kieli)." $tuoteno") === FALSE) {
            $kommentti .= " ".t("HUOM: Korvaa tuotteen", $kieli)." $tuoteno";
          }
        }

        $tuoteno = $paatuote;
      }
    }
    // Vastaavuusketjun puutek‰sittely, lis‰t‰‰n ketjun p‰‰tuote puutteeksi
    elseif ($kaannetaan_tuote and $puu_hintake[$i] != "X" and $tuoteno != "" and $yhtiorow["korvaavuusketjun_puutekasittely"] == "V" and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS') and $laskurow['tilaustyyppi'] != '9' and (empty($rivitunnus) and (!isset($mista_tullaan) or (isset($mista_tullaan) and $mista_tullaan != 'TULOUTUS'))) and $kvkasittely) {
      // Haetaan korvaavuusketju tuotteelle, jotta saadaan ketjun p‰‰tuote
      require_once 'korvaavat.class.php';
      $korvaavat = new Korvaavat($tuoteno);

      require_once 'vastaavat.class.php';
      $options = array('skippaa_vaihtoehtoiset' => TRUE);

      if (count($korvaavat->tuotteet()) > 0) {
        $vastaavat = new Vastaavat($korvaavat->paatuote(), $options);
      }
      else {
        $vastaavat = new Vastaavat($tuoteno, $options);
      }

      if ($vastaavat->onkovastaavia()) {

        $options = array('vastaavuusketjun_jarjestys' => $yhtiorow['vastaavuusketjun_jarjestys'], 'skippaa_vaihtoehtoiset' => TRUE);

        // Loopataan kaikki tuotteen vastaavuusketjut
        foreach (explode(",", $vastaavat->getIDt()) as $ketju) {

          // Haetaan tuotteet ketjukohtaisesti
          $_tuotteet = $vastaavat->tuotteet($ketju, $options);

          // Lis‰t‰‰n lˆydetyt vastaavat mahdollisten myyt‰vien joukkoon
          foreach ($_tuotteet as $_tuote) {
            if (strtoupper($_tuote['tuoteno']) != strtoupper($tuoteno)) {
              if (stripos($kommentti, t("HUOM: Korvaa tuotteen", $kieli)." $tuoteno") === FALSE and empty($korlisakommi[$_tuote['tuoteno']])) {
                $kommentti .= " ".t("HUOM: Korvaa tuotteen", $kieli)." $tuoteno";
              }
              else {
                $kommentti .= " ".$korlisakommi[$_tuote['tuoteno']];
              }

              $tuoteno = $_tuote['tuoteno'];
            }

            // Breakataan aina ekan j‰lkeen
            break 2;
          }
        }
      }
    }

    $query = "SELECT *
              FROM tuote
              WHERE tuoteno = '$tuoteno'
              and yhtio     = '$kukarow[yhtio]'";
    $tuoteresult = pupe_query($query);

    if (mysql_num_rows($tuoteresult) == 0) {
      // tuotetta ei lˆydy, lis‰t‰‰n silti tuoteno kantaan?
      $trow["tuoteno"] = $kaikkituotteet[0];
    }
    else {
      $trow = mysql_fetch_assoc($tuoteresult);
    }

    $autom_siirto_myytil = false;

    $_tarjouksella = ($toim == "TARJOUS");
    $_myyntitilauksella = ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS');
    $_extranetista = (isset($mista_tullaan) and $mista_tullaan == 'ORUMNET' and $toim == 'EXTRANET');
    $_osto_myyntitilaukselta = ($yhtiorow["tee_osto_myyntitilaukselta"] != '' and $_myyntitilauksella);

    if (($rivitunnus == 0 or $tapa == "MYYVASTAAVA") and
      $rivityyppi == 'L' and
      $yhtiorow['tee_automaattinen_siirto_myyntitilaukselta'] == 'K' and
      $yhtiorow['tee_siirtolista_myyntitilaukselta'] == 'K' and
      $laskurow["tilaustyyppi"] != "U" and
      ($_tarjouksella or $_osto_myyntitilaukselta or $_extranetista) and
      !in_array($var, array('V', 'H', 'P'))
      and $kpl > 0
      and in_array($laskurow['tila'], array('N', 'T'))
      and in_array($laskurow['alatila'], array('', 'F'))) {

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

      $_onko_isatuote = (!empty($perhearray) and isset($isatuoteno) and $isatuoteno == $trow['tuoteno']);
      $_onko_lapsituote = (!empty($perhearray) and isset($isatuoteno) and $isatuoteno != $trow['tuoteno']);
      $siirto_varastot = hae_mahdolliset_siirto_varastot($kohde_varastot, $trow['tuoteno']);

      foreach ($siirto_varastot as $siirto_varasto) {
        $saldo_myytavissa = $siirto_varasto['tuote']['myytavissa'];
        if ($_onko_isatuote) {
          $tuoteperhe_saldomyytavissa = tuoteperhe_myytavissa($isatuoteno, '', '', $siirto_varasto['lahde_tunnus']);
          $saldo_myytavissa = $tuoteperhe_saldomyytavissa[$siirto_varasto['lahde_nimi']];
        }

        //Jos tilausrivin toimittajan_tunnus ja siirto_varasto.tunnus on samat,
        //tarkoittaa se ett‰ tilausriville on valittu kyseinen siirtovarasto
        //ja lisaarivi vaiheessa saldot on riitt‰neet.
        $_normirivi_onko_siirto = (
          !isset($tuoteperhe_saldomyytavissa)
          and $siirto_varasto['tuote']['myytavissa'] <= 0
        );
        $_perherivi_onko_siirto = (
          isset($tuoteperhe_saldomyytavissa)
          and $tuoteperhe_saldomyytavissa[$siirto_varasto['lahde_nimi']] <= 0
        );
        if ($_normirivi_onko_siirto or $_perherivi_onko_siirto) {
          continue;
        }

        if ($saldo_myytavissa >= $kpl) {
          $var = "S";
          $autom_siirto_myytil = true;
          $toimittajan_tunnus = $siirto_varasto['tunnus'];

          $tuotepaikkojen_saldot = varaston_tuotepaikkojen_saldo($tuoteno, array($siirto_varasto['kohde_tunnus']));
          $tuotepaikka = $tuotepaikkojen_saldot[0];

          $kohde_varasto_update = "  tilausrivin_lisatiedot.kohde_hyllyalue = '{$tuotepaikka['hyllyalue']}',
                        tilausrivin_lisatiedot.kohde_hyllynro = '{$tuotepaikka['hyllynro']}',
                        tilausrivin_lisatiedot.kohde_hyllytaso = '{$tuotepaikka['hyllytaso']}',
                        tilausrivin_lisatiedot.kohde_hyllyvali = '{$tuotepaikka['hyllyvali']}',";

          $tuotepaikkojen_saldot = varaston_tuotepaikkojen_saldo($tuoteno, array($siirto_varasto['lahde_tunnus']));
          $tuotepaikka = $tuotepaikkojen_saldot[0];

          $myy_hyllyalue_temp = $tuotepaikka['hyllyalue'];
          $myy_hyllynro_temp = $tuotepaikka['hyllynro'];
          $myy_hyllytaso_temp = $tuotepaikka['hyllytaso'];
          $myy_hyllyvali_temp = $tuotepaikka['hyllyvali'];

          $paivita_hyllyt_varastosiirto = true;
          break;
        }
      }
    }

    // Tehd‰‰nkˆ puute- tai jt-rivist‰ automaattisesti osto/suoratoimitus?
    if (!$autom_siirto_myytil and (int) $rivitunnus == 0 and $yhtiorow["tee_automaattinen_osto_myyntitilaukselta"] != "" and ($kukarow["extranet"] == "" or $yhtiorow['tuoteperhe_suoratoimitus'] == 'E') and ($toim == "TARJOUS" or ($yhtiorow["tee_osto_myyntitilaukselta"] != '' and ($toim == 'RIVISYOTTO' or $toim == 'PIKATILAUS'))) and !in_array($var, array('V', 'H'))) {

      if ((
          $kukarow["extranet"] == ""
          or $yhtiorow['tuoteperhe_suoratoimitus'] == 'E'
        )
        and in_array($var, array('', 'H', 'S', 'T', 'U', 'J', 'P'))
        and (
          $toim == "TARJOUS"
          or (!in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('', 'Z', 'Q')))
        )
        and $kpl > 0
        and $toim != "SIIRTOTYOMAARAYS"
      ) {

        if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('A', 'B', 'C', 'I', 'J'))) {
          // Katsotaan onko t‰lle tuotteelle toimittajaa ja oletusvienti on jotain vaihto-omaisuutta (Tilaa-toimittajalta drop-downiin mukaan)
          $query = "SELECT tilausrivin_lisatiedot.toimittajan_tunnus
                    FROM tilausrivi
                    JOIN tilausrivin_lisatiedot
                    ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                      AND tilausrivin_lisatiedot.tilausrivitunnus    = tilausrivi.tunnus
                      AND tilausrivin_lisatiedot.toimittajan_tunnus != 0 )
                    WHERE tilausrivi.yhtio                           = '{$kukarow['yhtio']}'
                    AND tilausrivi.otunnus                           = '{$laskurow['tunnus']}'
                    AND tilausrivi.tyyppi                           != 'D'
                    AND tilausrivi.var                               in ('J','T','U')
                    ORDER BY tilausrivi.tunnus ASC
                    LIMIT 1";
          $toimittaja_result = pupe_query($query);
          $toimittaja = mysql_fetch_assoc($toimittaja_result);

          if (!empty($toimittaja['toimittajan_tunnus'])) {
            $yksi_suoratoimittaja_join = " AND toimi.tunnus = {$toimittaja['toimittajan_tunnus']} ";
          }
          else {
            $yksi_suoratoimittaja_join = "";
          }
        }
        else {
          $yksi_suoratoimittaja_join = "";
        }

        // Otetaan tuotteen oletustoimittaja
        $query = "SELECT toimi.tyyppi_tieto,
                  tuotteen_toimittajat.liitostunnus,
                  toimi.nimi,
                  toimi.suoratoimitus,
                  tuotteen_toimittajat.tehdas_saldo
                  FROM tuotteen_toimittajat use index (yhtio_tuoteno)
                  JOIN toimi
                  ON ( toimi.yhtio = tuotteen_toimittajat.yhtio
                    AND toimi.tunnus               = tuotteen_toimittajat.liitostunnus
                    {$yksi_suoratoimittaja_join})
                  WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
                  AND tuotteen_toimittajat.tuoteno = '$trow[tuoteno]'
                  ORDER BY if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys)
                  LIMIT 1";
        $toimpaikres = pupe_query($query);
        $krow = mysql_fetch_assoc($toimpaikres);

        if (!empty($krow) and !in_array($yhtiorow["tee_automaattinen_osto_myyntitilaukselta"], array('Y', 'V', 'W', 'H')) or ($krow['tehdas_saldo'] >= $kpl)) {
          // tarkistetaan voiko tuotteen tilata suoraan asiakkaalle tai varastoon. oletuksena voi...
          $tmp_varastoon   = 1;
          $tmp_asiakkaalle = 1;

          // ...kiellet‰‰n tilaamasta varastoon jos yhtiˆparametri ohjaa katsomaan tuotteelta ja siell‰ ei ole sallittu...
          if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J'))
            and !in_array($trow['suoratoimitus'], array('S', 'X'))) {         // vain S ja X sallivat tilauksen varastoon
            $tmp_varastoon = 0;
          }

          // ...kiellet‰‰n tilaamasta asiakkaalle jos yhtiˆparametri ohjaa katsomaan tuotteelta ja siell‰ ei ole sallittu...
          if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('W', 'J'))
            and !in_array($trow['suoratoimitus'], array('S', 'U'))) {        // vain S ja U sallivat tilauksen asiakkaalle
            $tmp_asiakkaalle = 0;
          }

          // katsotaan onko tuote suoratoimituskelpoinen (vain ei tuoteperheet, tai tuoteperheiden saldolliset is‰t tai jos yhtiˆll‰ suoratoimitetaan perheet per tuote)
          if ((   // jos on tuoteperheen lapsi, pit‰‰ lapsien suoratoimitus olla sallittu
              $yhtiorow['tuoteperhe_suoratoimitus'] == ''
              or $perheid == 0
              or $perheid == $rivitunnus
            )
            and ( // onko suoratoimitus yleens‰k‰‰n sallittu
              in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'X', 'Y', 'A', 'B', 'C'))
              or (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('V', 'W', 'I', 'J')) and ($tmp_varastoon == 1 or $tmp_asiakkaalle == 1))
            )
          ) {

            if (!in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'B')) and in_array($krow["suoratoimitus"], array('', 'X')) and $tmp_varastoon == 1 and in_array($yhtiorow["tee_automaattinen_osto_myyntitilaukselta"], array('X', 'Y', 'H'))) {
              $var          = "T";
              $toimittajan_tunnus  = $krow['liitostunnus'];
              $paikka         = "";
            }

            if (in_array($yhtiorow["tee_osto_myyntitilaukselta"], array('U', 'Y', 'W', 'J', 'B', 'C')) and in_array($krow["suoratoimitus"], array('', 'U')) and $tmp_asiakkaalle == 1 and in_array($yhtiorow["tee_automaattinen_osto_myyntitilaukselta"], array('U', 'V', 'W'))) {
              $var          = "U";
              $toimittajan_tunnus  = $krow['liitostunnus'];
              $paikka         = "";
            }
          }
        }
        else {
          if (in_array($yhtiorow['puute_jt_oletus'], array('', 'A'))) {
            $var = "P";
          }
          elseif (in_array($yhtiorow['puute_jt_oletus'], array('J', 'B'))) {
            $var = "J";
          }
          elseif (in_array($yhtiorow['puute_jt_oletus'], array('H'))) {
            $var = "";
          }
          else {
            $var = "";
          }
          $toimittajan_tunnus  = '';
          $paikka         = '';
        }
      }
    }

    if ($yhtiorow['rivinumero_syotto'] != '' and $kutsuja != "EDITILAUS") {
      if (!isset($tilrivinumero) or (int) $tilrivinumero == 0) {
        $query = "SELECT max(tilaajanrivinro) as trivinro from tilausrivi where yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
        $triviresult = pupe_query($query);
        $trivinrow = mysql_fetch_assoc($triviresult);

        $rivinumero = $trivinrow["trivinro"]+1;
      }
      else {
        $rivinumero = $tilrivinumero;
      }
    }

    // Jos ollaan lis‰‰m‰ss‰ alkuper‰ist‰ tuotetta niin otetaan takaisin k‰ytt‰j‰n syˆtetyt alkuper‰iset hinnat
    if ($rivityyppi != 'O' and strtoupper($trow["tuoteno"]) == strtoupper($alkupera_trow["tuoteno"])) {
      $hinta  = $alkupera_hinta;

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        ${'ale'.$alepostfix} = ${'alkupera_ale'.$alepostfix};
      }

      $netto  = $alkupera_netto;
      $alv  = $alkupera_alv;
    }
    elseif ($rivityyppi != 'O' and $i > 0) {
      //K‰sinsyˆteyt hinnat nollataan kun lis‰t‰‰n tuoteperheiden lapsia
      $hinta  = "";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        ${'ale'.$alepostfix} = "";
      }

      $netto  = "";
      $alv  = "";

      if ($var != "S") {
        $rivinumero = "";
      }
    }

    if ($rivityyppi == 'O') {
      $ale_kaikki_array = array();

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
        $ale_kaikki_array['ale'.$alepostfix] = ${'alkupera_ale'.$alepostfix};
      }

      list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_val) = alehinta_osto($laskurow, $trow, $kpl, "", $hinta, $ale_kaikki_array);
    }

    // jos k‰ytˆss‰ on hintakertoimet, niin muutetaan yksikkˆ kuntoon
    if ($rivityyppi == 'O' and $trow["myyntihinta_maara"] != 0 and $lis_hinta != 0) {
      $lis_hinta = round($lis_hinta / $trow["myyntihinta_maara"], $yhtiorow["hintapyoristys"]);
    }

    if ($rivityyppi != 'O') {
      $ale_kaikki_array = array();

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $ale_kaikki_array['ale'.$alepostfix] = ${'ale'.$alepostfix};
      }

      if ($toim == "REKLAMAATIO" or $toim == "EXTRANET_REKLAMAATIO") {
        $alemaara = 99999999999;
      }
      else {
        $alemaara = $kpl;
      }

      list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, $alemaara, $netto, $hinta, $ale_kaikki_array, '', '', ($puu_tyyppi[$i] == 'LAPSITUOTE' ? 'X' : ''));
      list($lis_hinta, $lis_alv) = alv($laskurow, $trow, $lis_hinta, $alv, $alehinta_alv);
    }

    if ($rivityyppi != 'O') {
      switch ($var) {
      case 'J' : // J‰lkitoimitukseen
        // Ollaan painettu JT-muiden mukana nappia, tai meill‰ on parametrit siten, ett‰ se halutaan defaulttina p‰‰lle
        // (Jt_automatiikka = "kohdistetaan.." ja Automaattinen_jt_toimitus = "J‰lkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydess‰ ker‰ysp‰iv‰n mukaisesti")
        if (isset($jt_manual) and $jt_manual != '' and !empty($yhtiorow['jt_manual'])) {
          $kerayspvm = date('Y-m-d', strtotime('now'));
        }
        elseif ($laskurow["tila"] != 'G' and ((isset($jt_muidenmukana) and $jt_muidenmukana == 'KYLLA') or (!isset($jt_muidenmukana) and !empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A'))) {
          $kerayspvm = date('Y-m-d', strtotime('now + 3 month'));
        }
        elseif ((isset($jt_muidenmukana) and $jt_muidenmukana == 'EI') or (!isset($jt_muidenmukana) and !empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A')) {
          $kerayspvm = date('Y-m-d', strtotime('now'));
        }
      case 'R' : // Valmistetaan asiakkaalle
      case 'U' : // Tilataan toimittajalta asiakkaalle
      case 'T' : // Tilataan toimittajalta
        // Yhtiˆn parametri: varaako JT-rivi saldoja
        if ($yhtiorow["varaako_jt_saldoa"] == "") {
          $varattu   = 0;
          $jt     = $kpl;
        }
        else {
          $varattu   = $kpl;
          $jt     = 0;
        }
        break;
      case 'P' : // Puute
        $varattu   = 0;
        $jt     = 0;
        break;
      case 'H' : // Pakkohyv‰ksynt‰
        $varattu   = $kpl;
        $jt     = 0;
        break;
      case 'O' : // Ookooaus-hyv‰ksynt‰
        $varattu   = $kpl;
        $jt     = 0;
        break;
      case 'V' : // Tehdaslis‰varuste
        $varattu   = $kpl;
        $jt     = 0;
        break;
      case 'S':
        $varattu = $kpl;
        $jt = 0;
        break;
      case '' :
        $varattu   = 0;
        $jt     = 0;

        // Sirtolistoissa defaultti on JT jos saldo ei riit‰. (jos yhtio ei v‰lit‰ saldoista ei v‰litet‰ t‰‰ll‰k‰‰n..)
        // Tai jos yhtiˆlle on konffattu, ett‰ se haluaa ylimemen‰t jteiksi
        // Sis‰isiss‰ tyˆm‰‰r‰ykiss‰ ei haluta JT-rivej‰
        if ((($rivityyppi == 'G' and $yhtiorow["puute_jt_oletus"] != "H") or (($yhtiorow["puute_jt_oletus"] == "J" or $yhtiorow["puute_jt_oletus"] == "B") and $kpl > 0)) and $laskurow["tila"] != 'S' and $jtkielto != "o") {
          if ($yhtiorow["varaako_jt_saldoa"] == "") {
            $varattu   = 0;
            $jt     = $kpl;
          }
          else {
            $varattu   = $kpl;
            $jt     = 0;
          }

          $var  = "J";
        }
        // Terminaalitoimitukset tai ostoskorit; var tyhj‰‰ ja varattu normisti
        elseif ($rivityyppi == 'Z' or $rivityyppi == 'B' or $rivityyppi == 'E' or $rivityyppi == 'F') {
          $var   = "";
          $varattu = $kpl;
        }
        // Halutaan myyd‰ saldoista juurikaan mit‰‰n v‰litt‰m‰tt‰!
        elseif ($yhtiorow["puute_jt_oletus"] == "H") {
          $var   = "";
          $varattu = $kpl;
        }
        // Hyvitysrivit menee normaaleina
        elseif ($kpl < 0) {
          $var   = "";
          $varattu = $kpl;
        }
        // Normaalisti kaikki menee puutteeksi.
        else {
          $var   = "P";
        }
        break;
      default :
        echo t("VIRHE: Virheellinen arvo kent‰ss‰ hyvaksy")." '$var'";
      }

      if (trim($var) == 'P') {

        // Onko customi uutekommenttia
        $puurivires = t_avainsana("PUUTEKOMM");

        if ($kutsuja == "EDITILAUS" and $trow['status'] == 'T') {
          if (stripos($kommentti, t("Tilaustuote.", $kieli)) === FALSE) {
            // Tilausrivin systeemikommentti
            $kommentti .= " ".t("Tilaustuote.", $kieli);
          }
        }
        elseif (mysql_num_rows($puurivires) > 0) {

          $puurivirow = mysql_fetch_assoc($puurivires);

          if (stripos($kommentti, $puurivirow["selite"]) === FALSE) {
            // Tilausrivin systeemikommentti
            $kommentti .= " ".$puurivirow["selite"];
          }
        }
        elseif ($kutsuja == "EDITILAUS") {
          if (stripos($kommentti, t("Tuote Loppu.", $kieli)) === FALSE) {
            // Tilausrivin systeemikommentti
            $kommentti .= " ".t("Tuote loppu.", $kieli);
          }
        }
      }
    }
    else {
      $var     = "";
      $varattu   = $kpl;
    }

    $p_hyllyalue  = "";
    $p_hyllynro    = "";
    $p_hyllyvali  = "";
    $p_hyllytaso  = "";

    if ($paikka != '') {
      //K‰ytt‰j‰n syˆtt‰m‰ paikka
      $paikat = explode('#!°!#', $paikka);

      $p_hyllyalue  = $paikat[0];
      $p_hyllynro    = $paikat[1];
      $p_hyllyvali  = $paikat[2];
      $p_hyllytaso  = $paikat[3];
    }
    else {
      // haetaan viel‰ tuotteen oletuspaikka puuteriville, jotta seuranta ois helpompaa
      //jos laskurow[varasto] on setattu niin yritet‰‰n lˆyt‰‰ paikka siit‰ varastosta
      $query = "SELECT tuotepaikat.*, tuotepaikat.varasto varastotunnus
                FROM tuotepaikat
                where tuotepaikat.yhtio    = '$kukarow[yhtio]'
                and tuotepaikat.tuoteno    = '$trow[tuoteno]'
                AND tuotepaikat.hyllyalue != '!!M'
                ORDER BY tuotepaikat.oletus DESC";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {

        // Yritet‰‰n lˆyt‰‰ paras varastopaikka
        $loydetytpaikat = FALSE;

        // lˆytyykˆ syˆtetty $varasto (muttujassa tulee myˆs $laskurow[varasto] tai $kukarow["varasto"] jos $varastoa ei olla eksplisiittisesti syˆtetty)?
        if (isset($varasto) and is_array($varasto)) {
          while ($olerow = mysql_fetch_assoc($result)) {
            if (in_array($olerow["varastotunnus"], $varasto)) {
              $p_hyllyalue  = $olerow["hyllyalue"];
              $p_hyllynro    = $olerow["hyllynro"];
              $p_hyllyvali  = $olerow["hyllyvali"];
              $p_hyllytaso  = $olerow["hyllytaso"];

              $loydetytpaikat = TRUE;
              break;
            }
          }

          // Jos paikkaa ei viel‰k‰‰n lˆytynyt, luodaan tilauksen varastoon tuotepaikka ja k‰ytet‰‰n sit‰ (ostossa toisin)
          if (!$loydetytpaikat and $rivityyppi != 'O') {

            if ($laskurow['varasto'] != 0) {
              $_varasto = $laskurow['varasto'];
            }
            else {
              $_varasto = $varasto[0];
            }

            $query = "SELECT alkuhyllyalue, alkuhyllynro
                      FROM varastopaikat
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$_varasto}'";
            $oletus_tuotepaikka_res = pupe_query($query);
            $oletus_tuotepaikka_row = mysql_fetch_assoc($oletus_tuotepaikka_res);

            lisaa_tuotepaikka($trow['tuoteno'], $oletus_tuotepaikka_row['alkuhyllyalue'], $oletus_tuotepaikka_row['alkuhyllynro'], 0, 0, "Tuotetta lis‰tt‰ess‰", "", 0, 0, 0);

            $p_hyllyalue  = $oletus_tuotepaikka_row["alkuhyllyalue"];
            $p_hyllynro    = $oletus_tuotepaikka_row["alkuhyllynro"];
            $p_hyllyvali  = '0';
            $p_hyllytaso  = '0';

            $loydetytpaikat = TRUE;
          }
        }

        // ostotilaus
        // jos otsikolle on setattu $varasto ja tuotepaikkaa ei lˆydy kyseisest‰ varastosta
        // ostetaan tyhj‰lt‰ paikalta, koska ostotilauksen valmiiksi laittaminen perustaa kyseiseen varastoon uuden tuotepaikan

        // keissit:
        // jos $rivityyppi = 'O' ja setattu $varasto ja is_array($varasto)
        if (!$loydetytpaikat and $rivityyppi == 'O' and isset($varasto) and is_array($varasto)) {
          $p_hyllyalue  = "";
          $p_hyllynro    = "";
          $p_hyllyvali  = "";
          $p_hyllytaso  = "";
        }
        else {
          // lˆytyykˆ $kukarow["oletus_varasto"]?
          if (!$loydetytpaikat and $kukarow["oletus_varasto"] > 0) {
            mysql_data_seek($result, 0);

            while ($olerow = mysql_fetch_assoc($result)) {
              if ($olerow["varastotunnus"] == $kukarow["oletus_varasto"]) {
                $p_hyllyalue  = $olerow["hyllyalue"];
                $p_hyllynro    = $olerow["hyllynro"];
                $p_hyllyvali  = $olerow["hyllyvali"];
                $p_hyllytaso  = $olerow["hyllytaso"];

                $loydetytpaikat = TRUE;
                break;
              }
            }
          }

          //Jos ei lˆytynyt niin myyd‰‰n oletuspaikalta
          if (!$loydetytpaikat) {
            mysql_data_seek($result, 0);
            $olerow = mysql_fetch_assoc($result);

            $p_hyllyalue  = $olerow["hyllyalue"];
            $p_hyllynro    = $olerow["hyllynro"];
            $p_hyllyvali  = $olerow["hyllyvali"];
            $p_hyllytaso  = $olerow["hyllytaso"];
          }
        }
      }
    }

    if ($kutsuja == "EDITILAUS" or $lisaarivi_debug == 1) {
      if ($var == "J") {
        $edi_ulos .= " --> ".t("JT")." $trow[tuoteno] ".t("paikalta")." $p_hyllyalue-$p_hyllynro-$p_hyllyvali-$p_hyllytaso: $lis_kpl ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite")." ";

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $edi_ulos .= t("Ale")."{$alepostfix} ".$lis_ale_kaikki['ale'.$alepostfix]." ";
        }

        $edi_ulos .= t("Hinta")." $lis_hinta $aperuste $rivikomm\n";
      }
      elseif ($var == "P") {
        $edi_ulos .= " --> ".t("PUUTE")." $trow[tuoteno] ".t("paikalta")." $p_hyllyalue-$p_hyllynro-$p_hyllyvali-$p_hyllytaso: $lis_kpl ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite")." ";

        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $edi_ulos .= t("Ale")."{$alepostfix} ".$lis_ale_kaikki['ale'.$alepostfix]." ";
        }

        $edi_ulos .= t("Hinta")." $lis_hinta $aperuste $rivikomm\n";
      }

      if ($lisaarivi_debug == 1) echo "$edi_ulos<br>";
    }

    // Jos hinta ja alekertoimet on setattu
    if ($puu_hintake[$i] != "X") {
      if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
        $lis_hinta = round($lis_hinta * $puu_hintake[$i], 6);
      }
      else {
        $lis_hinta = round($lis_hinta * $puu_hintake[$i], $yhtiorow['hintapyoristys']);
      }
    }

    if ($puu_alekerr[$i] != "X") {
      if ((int) $puu_alekerr[$i] == -1) {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $lis_ale_kaikki['ale'.$alepostfix] = 100.00;
        }

      }
      else {
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $lis_ale_kaikki['ale'.$alepostfix] = round($lis_ale_kaikki['ale'.$alepostfix] * $puu_alekerr[$i], $yhtiorow['hintapyoristys']);
        }
      }
    }

    if (!is_array($korkieltokommi) and $korkieltokommi != '') {
      // Tilausrivin systeemikommentti
      if (stripos($kommentti, $korkieltokommi) === FALSE) {
        $kommentti .= " ".$korkieltokommi;
      }
    }

    $trow["tuoteno"] = strtoupper($trow["tuoteno"]);

    // jos olemme saaneet tuotenimityksen syotarivist‰ ja sallimme nimityksen muutoksen
    if (trim($tuotenimitys_force) != "") {
      $trow["nimitys"] = addslashes($tuotenimitys_force);
    }
    elseif (($yhtiorow["nimityksen_muutos_tilauksella"] == "Y" or $toim == "HAAMU" or $trow["tuoteno"] == "99") and trim($tuotenimitys) != "") {
      // lis‰t‰‰n saatu nimitys kaikille tuotteille
      $trow["nimitys"] = addslashes($tuotenimitys);
    }

    // Onko tuotteella staattinen tilausrivikommentti
    if ($trow["tilausrivi_kommentti"] != "" and $rivitunnus == 0) {
      // Tilausrivin systeemikommentti
      if (stripos($kommentti, $trow["tilausrivi_kommentti"]) === FALSE) {
        if (trim($kommentti) != "")  $kommentti = "\n".$kommentti;
        $kommentti = trim($trow["tilausrivi_kommentti"].$kommentti);
      }
    }

    if ($rivitunnus == 0 and ($trow["status"] == "P" or $trow["status"] == "X")) {
      // Tilausrivin systeemikommentti
      if (stripos($kommentti, t("Poistunut tuote", $kieli)) === FALSE) {
        if (trim($kommentti) != "")  $kommentti .= "\n";
        $kommentti .= t("Poistunut tuote", $kieli);
      }
    }

    // Jos ollaan tultu editilauksesta ja meill‰ on puute/jt, katsotaan onko t‰lle tuotteelle vastaavia ja pistet‰‰n ne kommenttiin
    if ($kutsuja == "EDITILAUS" and ($var == "J" or $var == "P")) {
      //vastaavat tuotteet
      $query  = "SELECT *
                 FROM vastaavat
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND tuoteno = '$trow[tuoteno]'
                 LIMIT 1";
      $ediresult = pupe_query($query);

      if (mysql_num_rows($ediresult) > 0) {

        $edi_vastaavat_tuotteet = array();
        $edi_vaihtoehtoiset_tuotteet = array();

        $vrow = mysql_fetch_assoc($ediresult);

        $query = "SELECT *
                  FROM vastaavat
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND id      = '$vrow[id]'
                  AND tuoteno <> '$trow[tuoteno]'
                  ORDER BY jarjestys, tuoteno";
        $edi2result = pupe_query($query);

        while ($edirow = mysql_fetch_assoc($edi2result)) {

          list(, , $vmyytavissa) = saldo_myytavissa($edirow["tuoteno"], '' , $varasto);

          if ($vmyytavissa > 0) {
            $tvsql = "SELECT *
                      FROM tuote
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND tuoteno = '$edirow[tuoteno]'";
            $tvresult = pupe_query($tvsql);
            $tvrow = mysql_fetch_assoc($tvresult);

            // Jos tuote on vaihtoehtoinen
            if ($edirow['vaihtoehtoinen']) {
              $edi_vaihtoehtoiset_tuotteet[] = "$edirow[tuoteno] (".hintapyoristys($tvrow["myyntihinta"])." $laskurow[valkoodi] $vmyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite").")";
            }
            else {
              $edi_vastaavat_tuotteet[] = "$edirow[tuoteno] (".hintapyoristys($tvrow["myyntihinta"])." $laskurow[valkoodi] $vmyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$trow[yksikko]'", "", "", "selite").")";
            }
          }
        }

        // Jos vastaavia lˆytyi, lis‰t‰‰n kommentit
        if (!empty($edi_vastaavat_tuotteet)) {
          if ($kommentti != "") $kommentti .= "\n";
          $kommentti .= t("Voit tilata vastaavaa tuotetta", $kieli)." ".implode(", ", $edi_vastaavat_tuotteet).".";
        }

        // Jos vaihtoehtoisia lˆyty lis‰t‰‰n nekin kommentteihin
        if (!empty($edi_vaihtoehtoiset_tuotteet)) {
          if ($kommentti != "") $kommentti .= "\n";
          $kommentti .= t("Voit tilata vaihtoehtoista tuotetta", $kieli)." ".implode(", ", $edi_vaihtoehtoiset_tuotteet).".";
          $kommentti .= " ".t("Tarkista soveltuvuus.", $kieli);
        }
      }
    }

    if ($yhtiorow['myyntiera_pyoristys'] == 'K' and (isset($kutsuja) and $kutsuja == "EDITILAUS") and $edi_tyyppi != "magento" and $myyntierahuomatus) {
      if (strpos($kommentti, t("HUOM: Rivin m‰‰r‰ on pyˆristetty t‰yteen myyntier‰‰n! Myyntier‰ on", $kieli)) === FALSE) {
        $kommentti .= " ".t("HUOM: Rivin m‰‰r‰ on pyˆristetty t‰yteen myyntier‰‰n! Myyntier‰ on", $kieli).": $trow[myynti_era]";
      }
    }

    // Onko tuotteella staattinen ostorivikommentti
    if ($rivityyppi == 'O' and $trow["ostokommentti"] != "" and $rivitunnus == 0) {
      // Tilausrivin systeemikommentti
      if (stripos($kommentti, $trow["ostokommentti"]) === FALSE) {
        if (trim($kommentti) != "")  $kommentti = "\n".$kommentti;
        $kommentti = trim($trow["ostokommentti"].$kommentti);
      }
    }

    // jos meill‰ on vanha laadittuaikaa, niin k‰ytet‰‰ sit‰
    if ($rivilaadittu != "" and $rivitunnus != 0) {
      $vanharivilaadittu = "'$rivilaadittu'";
    }
    else {
      $vanharivilaadittu = "now()";
    }

    if ($var == "U" or $var == "T") {
      $p_hyllyalue  = "";
      $p_hyllynro    = "";
      $p_hyllynro    = "";
      $p_hyllytaso  = "";
    }

    // jos tuote on poistettu niin sit‰ ei voi laittaa j‰lkitoimitukseen
    if ($var == 'J' and ($trow["status"] == "P" or $trow["status"] == "X")) {
      $var = 'P';
      $varattu = 0;
    }

    $query_insert_ale_lisa = $query_insert_hinta_alkuperainen_lisa = '';

    if ($rivityyppi == 'O') {
      for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
        $query_insert_ale_lisa .= " ale{$alepostfix} = '".$lis_ale_kaikki['ale'.$alepostfix]."',";
      }

      if (isset($hinta_alkuperainen) and trim($hinta_alkuperainen) != '' and $laskurow['tila'] == 'O' and ($laskurow['alatila'] != '' or ($laskurow['alatila'] == '' and $laskurow['laskunro'] != 0))) {
        $query_insert_hinta_alkuperainen_lisa = " hinta_alkuperainen = '{$hinta_alkuperainen}',";
      }
      elseif ($rivitunnus == 0 or ($laskurow['tila'] == 'O' and $laskurow['alatila'] == '' and $laskurow['laskunro'] == 0)) {

        $query = "SELECT tuotekerroin
                  FROM tuotteen_toimittajat
                  WHERE yhtio      = '{$kukarow['yhtio']}'
                  AND tuoteno      = '{$trow['tuoteno']}'
                  AND liitostunnus = '{$laskurow['liitostunnus']}'";
        $tt_res = pupe_query($query);
        $tt_row = mysql_fetch_assoc($tt_res);

        $_arr = array(
          'netto' => $lis_netto,
          'erikoisale' => (trim($lis_netto) != 'N' ? $laskurow['erikoisale'] : 0),
          'erkoisale_saapuminen' => 0,
        );

        $_arr = array_merge($_arr, $lis_ale_kaikki);

        $ale_lisa_apu_kala = generoi_alekentta_php($_arr, 'O', 'kerto');
        $ohinta = round($lis_hinta * $ale_lisa_apu_kala * $tt_row['tuotekerroin'] * $laskurow['vienti_kurssi'] * $varattu, 6); // tuotteen rivihinta OLETUSVALUUTASSA

        $query_insert_hinta_alkuperainen_lisa = " hinta_alkuperainen = '{$ohinta}',";
      }
    }
    else {
      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $query_insert_ale_lisa .= " ale{$alepostfix} = '".$lis_ale_kaikki['ale'.$alepostfix]."',";
      }
    }

    $query_erikoisale_lisa = trim($lis_netto) != 'N' ? " erikoisale = '{$laskurow['erikoisale']}', " : '';

    $var2 = !isset($var2) ? '' : $var2;

    if ($yhtiorow['vastaavat_tuotteet_esitysmuoto'] == 'A') {
      $var2 = $var == 'P' ? '' : 'OK';
    }

    if ($var2 == '' and $yhtiorow['reklamaation_hinnoittelu'] == 'K' and ($toim == "REKLAMAATIO"  or $toim == "EXTRANET_REKLAMAATIO") and $hyvityssaannon_palautuskielto == "X") {
      $var2 = 'PK';
    }

    if ($yhtiorow['tee_siirtolista_myyntitilaukselta'] == 'K' and $paivita_hyllyt_varastosiirto) {
      $p_hyllyalue = $myy_hyllyalue_temp;
      $p_hyllynro = $myy_hyllynro_temp;
      $p_hyllytaso = $myy_hyllytaso_temp;
      $p_hyllyvali = $myy_hyllyvali_temp;
    }

    // lis‰t‰‰n puuterivi
    $query = "INSERT into tilausrivi set
              hyllyalue       = '$p_hyllyalue',
              hyllynro        = '$p_hyllynro',
              hyllyvali       = '$p_hyllyvali',
              hyllytaso       = '$p_hyllytaso',
              tilaajanrivinro = '$rivinumero',
              laatija         = '$kukarow[kuka]',
              laadittu        = $vanharivilaadittu,
              yhtio           = '$kukarow[yhtio]',
              tuoteno         = '$trow[tuoteno]',
              varattu         = '$varattu',
              yksikko         = '$trow[yksikko]',
              kpl             = '0',
              kpl2            = '$kpl2',
              tilkpl          = '$kpl',
              jt              = '$jt',
              {$query_insert_ale_lisa}
              {$query_erikoisale_lisa}
              alv             = '$lis_alv',
              netto           = '$lis_netto',
              hinta           = '$lis_hinta',
              {$query_insert_hinta_alkuperainen_lisa}
              kerayspvm       = '$kerayspvm',
              otunnus         = '$kukarow[kesken]',
              tyyppi          = '$rivityyppi',
              toimaika        = '$toimaika',
              kommentti       = '$kommentti',
              ale_peruste     = '{$GLOBALS['ale_peruste']}',
              var             = '$var',
              var2            = '{$var2}',
              try             = '$trow[try]',
              osasto          = '$trow[osasto]',
              perheid         = '$perheid',
              perheid2        = '$perheid2',
              tunnus          = '$rivitunnus',
              nimitys         = '$trow[nimitys]',
              jaksotettu      = '$jaksotettu'";

    if ($trow['ei_saldoa'] != '' and $yhtiorow["kerataanko_saldottomat"] == '') {
      $query .= ", kerattyaika = now()";
    }

    $result = pupe_query($query);
    $lisatty_tun = mysql_insert_id($GLOBALS["masterlink"]);

    if (($suoratoimitus_splittaus or $paivita_myyntitilaus) and $rivitunnus == $lisatty_tun) {
      // P‰ivitet‰‰n suoratoimitus-myyntitilausrivin m‰‰r‰t
      $query = "UPDATE tilausrivi
                JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.tilausrivilinkki > 0 and tilausrivin_lisatiedot.tilausrivilinkki = '$lisatty_tun')
                SET tilausrivi.tilkpl  = '$varattu',
                tilausrivi.varattu     = if(tilausrivi.varattu != 0, $varattu, 0),
                tilausrivi.jt          = if(tilausrivi.jt != 0, $varattu, 0)
                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                and tilausrivi.tunnus  = '$suoratoimitus_rivitun'";
      $result = pupe_query($query);
    }
    elseif (($suoratoimitus_splittaus or $paivita_myyntitilaus) and $rivitunnus != $lisatty_tun) {
      // Splitataan suoratoimitus-myyntitilausrivi
      $query = "SELECT varattu, jt
                FROM tilausrivi
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$suoratoimitus_rivitun'";
      $result = pupe_query($query);
      $suora_row = mysql_fetch_assoc($result);

      if ($suora_row["jt"] != 0) {
        $pkentat = array("tilausrivi.tilkpl"=> $varattu, "tilausrivi.jt"=> $varattu, "tilausrivin_lisatiedot.tilausrivilinkki"=> $lisatty_tun);
      }
      else {
        $pkentat = array("tilausrivi.tilkpl"=> $varattu, "tilausrivi.varattu"=> $varattu, "tilausrivin_lisatiedot.tilausrivilinkki"=> $lisatty_tun);
      }

      $uusi_suoratoimrivi = kopioi_tilausrivi($suoratoimitus_rivitun, $pkentat);
      $suoratoimitus_splittaus = FALSE;
    }

    $trivtyrow = array("selite" => "");
    @include "trivityyppivalinta.inc";

    //special case: Kun yhtiˆparametri Valmistuksien_kasittely on asennossa, "valmistuksella voi olla vain yksi valmiste...",
    //excelist‰ sis‰‰nluettavissa valmistusriveiss‰ voi antaa valmistuslinjan ja toivotut p‰iv‰m‰‰r‰t (mikrotilaus.inc)
    //N‰m‰ tiedot tallennetaan v‰liaikaisesti tilausriveille, joista ne sitten kopioidaan omille otsikoilleen.
    if ($valmistuslinja != '') {
      $positio = $valmistuslinja;
    }
    else {
      $positio = $trivtyrow['selite'];
    }

    $query = "SELECT *
              FROM tilausrivin_lisatiedot
              WHERE yhtio          = '$kukarow[yhtio]'
              and tilausrivitunnus = '$lisatty_tun'";
    $lisatied_res = pupe_query($query);

    $tlisatied_lisa2 = "";

    if ($toim == "YLLAPITO") {
      $tlisatied_lisa2 .= ", sopimuksen_lisatieto1 = '$sopimuksen_lisatieto1', sopimuksen_lisatieto2 = '$sopimuksen_lisatieto2'";
    }

    $tlisatied_lisa2 .= in_array($toim, array('RIVISYOTTO', 'PIKATILAUS')) ? ", omalle_tilaukselle = '".mysql_real_escape_string($omalle_tilaukselle)."'" : '';

    if ($laskurow["tila"] == 'G' and isset($kohde_alue) and $kohde_alue != "") {
      $tlisatied_lisa2 .= ", kohde_hyllyalue='$kohde_alue' , kohde_hyllynro='$kohde_nro' , kohde_hyllyvali='$kohde_vali' , kohde_hyllytaso='$kohde_taso'";
    }

    $jt_manual_lisa = (isset($jt_manual) and trim($jt_manual) != '') ? ", jt_manual = 'K'" : ", jt_manual = ''";

    if (mysql_num_rows($lisatied_res) > 0) {
      $lisatied_row = mysql_fetch_assoc($lisatied_res);

      $query = "UPDATE tilausrivin_lisatiedot
                SET
                positio              = if (positio = '' or '$valmistuslinja' != '', '{$positio}', positio),
                toimittajan_tunnus   = '$toimittajan_tunnus',
                {$kohde_varasto_update}
                rekisterinumero      = '{$rekisterinumero}',
                muutospvm            = now(),
                muuttaja             = '$kukarow[kuka]'
                {$tlisatied_lisa}
                {$tlisatied_lisa2}
                {$jt_manual_lisa}
                WHERE yhtio          = '$kukarow[yhtio]'
                and tilausrivitunnus = '$lisatty_tun'";
      $result = pupe_query($query);
    }
    else {
      if ($yhtiorow["tilauksen_jarjestys"] == "M") {
        $jarjestys = $lisatty_tun;
      }
      else {
        $jarjestys = 0;
      }

      $autoid = !empty($autoid) ? $autoid : 0;

      $query = "INSERT INTO tilausrivin_lisatiedot
                SET yhtio      = '$kukarow[yhtio]',
                positio            = '{$positio}',
                toimittajan_tunnus = '$toimittajan_tunnus',
                {$kohde_varasto_update}
                tilausrivitunnus   = '$lisatty_tun',
                jarjestys          = '$jarjestys',
                vanha_otunnus      = '$kukarow[kesken]',
                ei_nayteta         = '{$puu_ein[$i]}',
                ohita_kerays       = '{$puu_ohiker[$i]}',
                rekisterinumero    = '{$rekisterinumero}',
                autoid             = '{$autoid}',
                luontiaika         = now(),
                laatija            = '$kukarow[kuka]'
                {$tlisatied_lisa}
                {$tlisatied_lisa2}
                {$jt_manual_lisa}";

      if ($trow["sarjanumeroseuranta"] == "S" and $kpl < 0) {
        $query .= " , osto_vai_hyvitys='O' ";
      }

      $result = pupe_query($query);
    }

    if ($var == "H") {
      $lisatyt_rivit1[] = $lisatty_tun;
    }
    else {
      $lisatyt_rivit2[] = $lisatty_tun;
    }

    if ($myyntierahuomatus) {
      $myyntierahuom[] = $lisatty_tun;
    }
    if ($ostoerahuomatus) {
      $ostoerahuom[] = $lisatty_tun;
    }

    if ($i == 0) {
      // lapsille oikea perheid
      $perheid_lisaarivi = $lisatty_tun;

      if ($varattu+$jt != 0 and ($myy_sarjatunnus != "" or $osto_sarjatunnus != "") and strtoupper($trow["tuoteno"]) == strtoupper($alkupera_trow["tuoteno"])) {

        if ($osto_sarjatunnus != "" or $varattu+$jt < 0) {
          $tunken = "ostorivitunnus";

          if ($osto_sarjatunnus != "") {
            $myy_sarjatunnus = $osto_sarjatunnus;
          }
        }
        else {
          $tunken = "myyntirivitunnus";
        }

        $query = "SELECT yhtio, lisatieto, sarjanumero, kaytetty, tunnus
                  FROM sarjanumeroseuranta
                  WHERE tunnus IN ($myy_sarjatunnus)
                  and tuoteno  = '$trow[tuoteno]'
                  ORDER BY tunnus
                  LIMIT ". (int) abs($varattu+$jt);
        $sres = pupe_query($query);

        while ($srow = mysql_fetch_assoc($sres)) {
          if ($var == "S") {
            // jos syˆtet‰‰n sarjanumertollinen tuote suoratoimitukseen niin liitet‰‰n t‰m‰ myyntirivi siihen kiinni
            $query = "INSERT INTO sarjanumeroseuranta
                      SET
                      yhtio            = '$kukarow[yhtio]',
                      tuoteno          = '$trow[tuoteno]',
                      lisatieto        = 'Suoratoimitetaan yhtiˆst‰: $srow[yhtio]. $srow[lisatieto]',
                      sarjanumero      = '$srow[sarjanumero]',
                      kaytetty         = '$srow[kaytetty]',
                      myyntirivitunnus = '$lisatty_tun'";
            $sarjares = pupe_query($query);
          }
          else {
            $query = "UPDATE sarjanumeroseuranta
                      SET $tunken      = '$lisatty_tun'
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$srow[tunnus]'
                      and tuoteno = '$trow[tuoteno]'
                      and $tunken      = 0";
            $sarjares = pupe_query($query);
          }

          if ($srow["kaytetty"] == 'K') {
            $query = "UPDATE tilausrivi
                      SET alv=alv+500
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$lisatty_tun'
                      and alv     < 500";
            $sarjares = pupe_query($query);
          }
        }

        $osto_sarjatunnus = "";
        $myy_sarjatunnus = "";
      }
    }

    if (count($puu_tuoteno) > 1 and $rivityyppi != 'O' and $lop == "") {
      //P‰ivitet‰‰n is‰n perhetunnus
      if ($rivityyppi == 'V') {
        $query = "SELECT tuoteno
                  FROM tilausrivi
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = $perheid_lisaarivi
                  and tyyppi  = 'V'";
        $lisa_res = pupe_query($query);
        $lisa_row = mysql_fetch_assoc($lisa_res);

        if ($lisatied_row["porauskuvio"] == "M" or (isset($lapsenlap_array[$lisa_row["tuoteno"]]) and strtoupper($lapsenlap_array[$lisa_row["tuoteno"]]) == strtoupper($lisa_row["tuoteno"]))) {
          $valmistetyyppi = "M";

          $query = "UPDATE tilausrivin_lisatiedot
                    SET porauskuvio  = '$valmistetyyppi'
                    WHERE yhtio          = '$kukarow[yhtio]'
                    and tilausrivitunnus = '$perheid_lisaarivi'";
          $result = pupe_query($query);
        }
        else {
          $valmistetyyppi = "W";
        }

        //jos on valmistusprosessi kyseess‰ niin is‰n tyyppi vaihdetaan W:ksi koska sen ei pid‰ varata saldoa!
        $query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi', tyyppi='$valmistetyyppi' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi";
        $result = pupe_query($query);

        $query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus=$lisatty_tun";
        $result = pupe_query($query);
      }
      else {
        //Hoidetaan saldottomat is‰t samaan varastoon kuin lapsetkin jotta varastosplittaukset eiv‰t menisi hassusti
        $query = "UPDATE tilausrivi SET hyllyalue='$p_hyllyalue' where yhtio='$kukarow[yhtio]' and tunnus=$perheid_lisaarivi and hyllyalue=''";
        $result = pupe_query($query);

        $query = "UPDATE tilausrivi SET perheid='$perheid_lisaarivi' where yhtio='$kukarow[yhtio]' and tunnus in ('$perheid_lisaarivi','$lisatty_tun')";
        $result = pupe_query($query);

        $lisatyt_perheet[$perheid_lisaarivi][] = $lisatty_tun;
      }
    }

    $rivitunnus      = 0;
    $kommentti       = "";
    $tuotenimitys     = "";
    $tuotenimitys_force = "";
    $omalle_tilaukselle = "";
    $rivinumero      = "";
    $tilrivinumero    = "";
  }
}

$_yhteen_varastoon  = ($yhtiorow['pakollinen_varasto'] == 'K');
$_sopivatilaustoimi = (in_array($toim, array('RIVISYOTTO', 'PIKATILAUS', 'REKLAMAATIO', 'TARJOUS')));

if ($_yhteen_varastoon and $_sopivatilaustoimi and !in_array($var, array('S', 'U', 'T', 'P'))) {
  $myy_hyllyalue_temp = $myy_hyllyalue[0];
  $myy_hyllynro_temp  = $myy_hyllynro[0];

  $_normi_tyhja       = (empty($myy_hyllyalue) and empty($myy_hyllynro));
  $_puute_ei_tyhja    = ($p_hyllyalue != "" and $p_hyllynro != "");
  $_puutteesta_hyllyt = ($_normi_tyhja and $_puute_ei_tyhja);

  if ($_puutteesta_hyllyt) {
    $myy_hyllyalue_temp = $p_hyllyalue;
    $myy_hyllynro_temp = $p_hyllynro;
  }

  if ($laskurow['varasto'] == 0 and $myy_hyllynro_temp != '' and $myy_hyllyalue_temp != '') {
    $varasto = kuuluukovarastoon($myy_hyllyalue_temp, $myy_hyllynro_temp);

    if (!empty($varasto)) {
      $v_toimipaikka = hae_varaston_toimipaikka($varasto);
      if ($v_toimipaikka['tunnus'] == 0) {
        $_toimipaikka = $kukarow['toimipaikka'];
        $kukarow['toimipaikka'] = 0;
      }

      //$vt_yhtiorow varaston toimipaikan yhtiorow
      $vt_yhtiorow = hae_yhtion_parametrit($kukarow['yhtio'], $v_toimipaikka['tunnus']);
      $kukarow['toimipaikka'] = (isset($_toimipaikka) ? $_toimipaikka : $kukarow['toimipaikka']);
      unset($_toimipaikka);
      $update = "";

      // Katsotaan onko varasto sidottu johonkin toimipaikkaan
      // Ja onko se eri kuin otsikolla oleva toimipaikka
      // Katsotaan uudella yhtiˆrown parametreilla pit‰‰kˆ t‰ppi‰ laittaa
      $_laajat_toimipaikat = ($vt_yhtiorow['toimipaikkakasittely'] == "L");
      $_laskun_toimipaikka = ($laskurow['yhtio_toimipaikka'] != $v_toimipaikka['tunnus']);

      if ($_laajat_toimipaikat and $_laskun_toimipaikka) {

        $_onko_myyntitilaus = (in_array($toim, array("RIVISYOTTO", "PIKATILAUS")));
        $_kerataanko_valmistukset = (
          $vt_yhtiorow["kerataanko_valmistukset"] == "E"
          and $toim == "VALMISTAASIAKKAALLE"
        );
        $_lahetteen_tulostustapa = ($vt_yhtiorow['lahetteen_tulostustapa'] == 'I');

        if ($_kerataanko_valmistukset or $_lahetteen_tulostustapa) {
          $eilahe = 'o';
        }
        else {
          $eilahe = '';
        }

        $_myyntitilaus_osatoimitus = (
          $vt_yhtiorow['myyntitilaus_osatoimitus'] == 'E'
          and $_onko_myyntitilaus
        );

        if ($_myyntitilaus_osatoimitus) {
          $osatoimitus = 'o';
        }
        else {
          $osatoimitus = '';
        }

        $update = "  , eilahetetta = '{$eilahe}'
                    , osatoimitus = '{$osatoimitus}'";
      }

      $query = "UPDATE lasku
                SET varasto = '{$varasto}'
                {$update}
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$laskurow['tunnus']}'";
      pupe_query($query);
    }
  }
}

// Katsotaan lopuksi viel‰ tuoteperheiden hinnoittelu kaikille lis‰tyille riveille
if (!empty($lisatyt_perheet)) {
  $params = array(
    'isatunnukset' => $lisatyt_perheet,
    'tyyppi'       => $tuoteperhe_tyyppi,
  );

  tuoteperheiden_hintojen_paivitys($params);
}
