<?php

$otsikko = 'Paikat';
$otsikko_nappi = 'Paikka';

$kentat = "tunnus, nimi, (SELECT nimi from kohde where kohde.yhtio = paikka.yhtio AND kohde.tunnus = paikka.kohde) as kohde";

$hakukentat = "tunnus, nimi, kohde";

$jarjestys = 'tunnus, nimi, kohde';

if (!function_exists('paikka_poista_relaatiot')) {

  function paikka_poista_relaatiot($toim, $tunnus) {
    global $kukarow, $yhtiorow;

    $query = "  UPDATE laite
          SET tila = 'P'
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND paikka = '{$tunnus}'";
    pupe_query($query);
  }
}

if (!function_exists('echo_laite_redirect')) {
  function echo_laite_redirect($params) {
    global $palvelin2;

    //jos syötetään asiakkaalle uusia paikkoja asiakkaan_laite_hallinta.php:stä halutaan redirecti sinne takaisin
    if (isset($params['redirect_to']) and $params['errori'] == 0) {
      echo '<meta http-equiv="refresh" content="0;url='.$palvelin2.'asiakkaan_laite_hallinta.php?tee=hae_asiakas&asiakas_tunnus='.$params['valittu_asiakas'].'"/>';
    }
  }
}
