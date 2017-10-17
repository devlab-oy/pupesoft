<?php

if (isset($lukitse_laji) and $lukitse_laji != "") {
  $otsikko     = 'Avainsanan kieliversiot';
  $otsikko_nappi   = 'avainsanan kieliversio';
}
else {
  $otsikko     = 'Avainsanat';
  $otsikko_nappi   = 'avainsana';
}

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$kentat   = 'tunnus, kieli, laji, selite, selitetark, selitetark_2, selitetark_3, jarjestys, perhe HIDDEN_perhe';
$hakukentat = 'tunnus, kieli, laji, selite, selitetark, selitetark_2, selitetark_3, jarjestys, perhe';

$jarjestys = 'kieli, laji, jarjestys, selite, selitetark, selitetark_2';

$mysqlaliasarray = array();

// Tässä kohtaa aliakset suomeksi, t-funkkari on yllapito.php:ssa
$mysqlaliasarray['SAHKTILTUN']['selite']           = "Toimipaikka";
$mysqlaliasarray['SAHKTILTUN']['selitetark']       = "Toimittaja";
$mysqlaliasarray['SAHKTILTUN']['selitetark_2']     = "Asiakasnumero";
$mysqlaliasarray['SAHKTILTUN']['selitetark_3']     = "Käyttäjätunnus";
$mysqlaliasarray['SAHKTILTUN']['selitetark_4']     = "Salasana";
$mysqlaliasarray['SAHKTILTUN']['selitetark_5']     = "Toimittajanumero";

$mysqlaliasarray['VALMISTUSLINJA']['selite']         = "Linjanumero";
$mysqlaliasarray['VALMISTUSLINJA']['selitetark']     = "Linjan nimi";
$mysqlaliasarray['VALMISTUSLINJA']['selitetark_2']   = "Päiväkapasiteetti sekunneissa";

$mysqlaliasarray['TOIMVAHVISTUS']['selite']         = "Toimitusvahvistuksen tiedoston nimi";
$mysqlaliasarray['TOIMVAHVISTUS']['selitetark']    = "Toimitusvahvistuksen nimi";
$mysqlaliasarray['TOIMVAHVISTUS']['selitetark_2']    = "Toimitusvahvistus tyyppi";

$mysqlaliasarray['OSTOTIL_TILTYYP']['selite']       = "Ostotilauksen tilaustyyppi";
$mysqlaliasarray['OSTOTIL_TILTYYP']['selitetark']   = "Ostotilauksen tilaustyypin nimi";
$mysqlaliasarray['OSTOTIL_TILTYYP']['selitetark_2'] = "Automaattituloutuksen esto";

$mysqlaliasarray['INVEN_LAJI']['selitetark_2']     = "Varastonmuutos tili";
$mysqlaliasarray['INVEN_LAJI']['selitetark_3']     = "Mobiili-inventoinnin oletuslaji";
$mysqlaliasarray['INVEN_LAJI']['selitetark_4']     = "Oletusselite";

$mysqlaliasarray['KERAYSERA_TK']['selite']         = "Kerääjä";
$mysqlaliasarray['KERAYSERA_TK']['selitetark']     = "Varasto";

$mysqlaliasarray['RELEX_TUOTEPAR']['selite']         = "Tuote-kenttä";
$mysqlaliasarray['RELEX_TUOTEPAR']['selitetark']     = "Operaattori";
$mysqlaliasarray['RELEX_TUOTEPAR']['selitetark_2']   = "Arvo";

$mysqlaliasarray['ASIAKASHINNASTO']['selite']         = "Käytetäänkö myyntihinnan tilalla myymälähintaa";

$mysqlaliasarray['TOIMTAPAVKN']['selite']           = "Toimitustavan nimi Pupesoftissa";
$mysqlaliasarray['TOIMTAPAVKN']['selitetark']       = "Toimitustavan nimi verkkokaupassa";
