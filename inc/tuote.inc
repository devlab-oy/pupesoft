<?php

if ($yhtiorow['laite_huolto'] == 'X') {
  $kentat = "tunnus, tuoteno, nimitys, round(myyntihinta,$yhtiorow[hintapyoristys]) myyntihinta, status HIDDEN_status, 'huoltosykli' AS liitettavat_huoltosyklit";
}
else {
  $kentat = "tunnus, tuoteno, nimitys, round(myyntihinta,$yhtiorow[hintapyoristys]) myyntihinta, status HIDDEN_status";
}

$hakukentat = "tunnus, tuoteno, nimitys,myyntihinta";

$jarjestys = 'tuoteno';

if ($laji == "V") {
  $otsikko = 'Viranomaistuotteet';
  $rajauslisa = " and tuote.tuotetyyppi IN ('A','B') ";
}
elseif ($laji == "K") {
  $otsikko = 'Palvelutuotteet';
  $rajauslisa = " and tuote.tuotetyyppi = 'K' ";
}
else {
  $otsikko = 'Tuotteet';
  $rajauslisa = " and tuote.tuotetyyppi NOT IN ('A','B') ";
}

if ($nayta_poistetut == "") {
  $rajauslisa .= " and tuote.status != 'P' ";
}
else {
  $rajauslisa .= "";
}

// Tuotteen saa poistaa mikäli sillä ei ole yhtään tapahtumaa (tsekataan tuoterivi.inc)
$poistolukko = "ON";

if (!function_exists('echo_tuote_redirect')) {

  function echo_tuote_redirect($params) {
    global $palvelin2, $yhtiorow, $lopetus;

    //jos syötetään asiakkaalle uusia laitteita asiakkaan_laite_hallinta.php:stä halutaan redirecti sinne takaisin
    if ($yhtiorow['laite_huolto'] == 'X' and isset($params['redirect_to']) and $params['errori'] == '' and !empty($params['valittu_asiakas'])) {
      echo '<meta http-equiv="refresh" content="0;url=' . $palvelin2 . 'yllapito.php?toim=laite&asiakas_tunnus=' . $params['valittu_asiakas'] . '&uusi=1&lopetus=' . $lopetus . '"/>';
    }
  }
}
