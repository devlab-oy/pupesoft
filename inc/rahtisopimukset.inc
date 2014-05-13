<?php
  $otsikko = 'Rahtisopimukset';
  $kentat = "tunnus, concat_ws('<br>', asiakas, (SELECT nimi from asiakas where rahtisopimukset.yhtio=asiakas.yhtio and rahtisopimukset.asiakas=asiakas.tunnus)) Asiakas, concat_ws('<br>',ytunnus,(SELECT group_concat(distinct nimi separator '<br>') from asiakas where rahtisopimukset.yhtio=asiakas.yhtio and rahtisopimukset.ytunnus=asiakas.ytunnus and asiakas.ytunnus != '')) 'Ytunnus', toimitustapa, rahtisopimus, selite";
  $hakukentat = "tunnus, asiakas, ytunnus, toimitustapa, rahtisopimus, selite";
  
  $jarjestys = 'ytunnus,toimitustapa';
