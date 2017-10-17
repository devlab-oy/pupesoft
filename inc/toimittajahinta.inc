<?php

$otsikko = 'Toimittajahinta';
$otsikko_nappi = 'toimittajahinta';

if ($from == "") {
  // HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
  $kentat = "tunnus, concat_ws('<br>', toimittaja, (SELECT nimi from toimi where toimittajahinta.yhtio=toimi.yhtio and toimittajahinta.toimittaja=toimi.tunnus)) Toimittaja, concat_ws('<br>',ytunnus,(SELECT group_concat(distinct nimi separator '<br>') from toimi where toimittajahinta.yhtio=toimi.yhtio and toimittajahinta.ytunnus=toimi.ytunnus and toimi.ytunnus != '')) 'Ytunnus', tuoteno, ryhma 'Alennusryhmä', hinta, laji, minkpl, concat_ws('<br>',alkupvm, loppupvm) 'Alku/Loppu'";
}
else {
  // HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
  $kentat = "tunnus, concat_ws('<br>', toimittaja, (SELECT nimi from toimi where toimittajahinta.yhtio=toimi.yhtio and toimittajahinta.toimittaja=toimi.tunnus)) Toimittaja, concat_ws('<br>',ytunnus,(SELECT group_concat(distinct nimi separator '<br>') from toimi where toimittajahinta.yhtio=toimi.yhtio and toimittajahinta.ytunnus=toimi.ytunnus and toimi.ytunnus != '')) 'Ytunnus', ryhma 'Alennusryhmä', hinta, laji, minkpl, concat_ws('<br>',alkupvm, loppupvm) 'Alku/Loppu'";
}

// HUOM HUOM: ethän muuta näitä ilman, että korjaat myös yllapito.php:ssä iframen lukitse_avaimeen muuttujat
$hakukentat = "tunnus, toimittaja, ytunnus, tuoteno, ryhma, hinta, laji, minkpl, alkupvm/loppupvm";

$jarjestys = 'ytunnus, tuoteno, ryhma, laji, minkpl, alkupvm, loppupvm';

if ($nayta_eraantyneet != "") {
  $rajauslisa = " and toimittajahinta.loppupvm > '0000-00-00' and toimittajahinta.loppupvm < now() ";
}
