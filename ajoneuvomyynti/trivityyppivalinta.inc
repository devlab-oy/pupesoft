<?php

if ($myy_tyyppi[$indeksi] == "LAPSITUOTE" or ($perheid_lisaarivi > 0 and $perheid_lisaarivi != $lisatty_tun)) {

  $trivtyres = t_avainsana("TRIVITYYPPI", "", "and selite like 'vakio%'");
  $trivtyrow = mysql_fetch_array($trivtyres);
}
elseif ($perheid2 > 0 and $perheid2 != $lisatty_tun) {

  $trivtyres = t_avainsana("TRIVITYYPPI", "", "and selite like 'tehdas%'");
  $trivtyrow = mysql_fetch_array($trivtyres);
}
else {
  $trivtyres = t_avainsana("OSASTO", "", "and selite = '$trow[osasto]'");
  $trivtyrow = mysql_fetch_array($trivtyres);

  $trivtyres = t_avainsana("TRIVITYYPPI", "", "and selite like '".substr($trivtyrow["selitetark"], 0, -2)."%'");
  $trivtyrow = mysql_fetch_array($trivtyres);
}
?>
