<?php

  $otsikko     = 'Asiakkaan sopimushinnat';
  $otsikko_nappi   = 'asiakashinta';

  if ($from == "") {
    // HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
    $kentat = "asiakashinta.tunnus,
    concat_ws('<br>', asiakashinta.asiakas, (SELECT asiakas.nimi FROM asiakas where asiakashinta.yhtio = asiakas.yhtio and asiakashinta.asiakas = asiakas.tunnus)) Asiakas,
    asiakashinta.ytunnus 'Ytunnus',
    (SELECT concat(dynaaminen_puu.tunnus, '<br>', dynaaminen_puu.nimi) FROM dynaaminen_puu WHERE dynaaminen_puu.tunnus = asiakashinta.asiakas_segmentti AND dynaaminen_puu.yhtio = asiakashinta.yhtio) 'Asiakaspuu',
    asiakashinta.asiakas_ryhma 'Asiakasryhmä',
    (SELECT concat(avainsana.selite, '<br>', avainsana.selitetark) FROM avainsana WHERE avainsana.laji = 'piiri' and avainsana.selite = asiakashinta.piiri and avainsana.yhtio = asiakashinta.yhtio) 'Piiri',
    asiakashinta.tuoteno,
    asiakashinta.ryhma 'Alennusryhmä',
    asiakashinta.hinta,
    asiakashinta.laji,
    concat_ws('<br>', asiakashinta.minkpl, asiakashinta.maxkpl) 'Min/Maxkpl',
    concat_ws('<br>', asiakashinta.alkupvm, asiakashinta.loppupvm) 'Alku/Loppu'";
  }
  else {
    // HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
    $kentat = "asiakashinta.tunnus,
    concat_ws('<br>', asiakashinta.tuoteno, asiakashinta.ryhma) 'Tuoteno/Alennusryhmä',
    asiakashinta.hinta,
    asiakashinta.laji,
    concat_ws('<br>', asiakashinta.minkpl, asiakashinta.maxkpl) 'Min/Maxkpl',
    concat_ws('<br>', asiakashinta.alkupvm, asiakashinta.loppupvm) 'Alku/Loppu'";
  }

  // HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
  if ($yhtiorow['vauriopoytakirja'] == 'K') {
    $hakukentat = "asiakashinta.tunnus, asiakashinta.asiakas, asiakashinta.ytunnus, asiakashinta.asiakas_segmentti,asiakashinta.asiakas_ryhma,asiakashinta.piiri, asiakashinta.tuoteno, asiakashinta.ryhma, asiakashinta.hinta, asiakashinta.laji, minkpl/maxkpl, alkupvm/loppupvm";
    $jarjestys = 'asiakashinta.ytunnus, asiakashinta.asiakas_ryhma, asiakashinta.tuoteno, asiakashinta.ryhma, asiakashinta.laji, asiakashinta.minkpl, asiakashinta.alkupvm, asiakashinta.loppupvm';

    $join = "  JOIN tuote
          ON ( tuote.yhtio = asiakashinta.yhtio
            AND tuote.tuoteno = asiakashinta.tuoteno
            AND tuote.try IN ({$kukarow['try']}) )";
  }
  else {
    $hakukentat = "tunnus, asiakas, ytunnus, asiakas_segmentti,asiakas_ryhma,piiri, tuoteno, ryhma, hinta, laji, minkpl/maxkpl, alkupvm/loppupvm";
    $jarjestys = 'ytunnus, asiakas_ryhma, tuoteno, ryhma, laji, minkpl, alkupvm, loppupvm';
    $join = "";
  }

  if ($nayta_eraantyneet != "") {
    $rajauslisa = " and asiakashinta.loppupvm > '0000-00-00' and asiakashinta.loppupvm < now() ";
  }
