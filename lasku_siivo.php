<?php

require('inc/parametrit.inc');

echo "<br/>";
echo "<br/>";
echo "<br/>";
if (empty($tee)) {
  echo "<form action='' method='POST'>";
  echo "<input type='hidden' name='tee' value='poista' />";
  echo "<input type='submit' value='Poista' />";
  echo "</form>";
}
else {
  echo "Poistettu";
  $query = array(
    "DELETE FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != ''",
    "DELETE FROM tilausrivi WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != ''",
    "DELETE FROM tyomaarays WHERE yhtio = '{$kukarow['yhtio']}' AND otunnus != ''",
    "DELETE FROM laskun_lisatiedot WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != ''",
    "DELETE FROM tilausrivin_lisatiedot WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus != ''",
//    "  UPDATE huoltosyklit_laitteet
//      JOIN huoltosykli
//      ON ( huoltosykli.yhtio = huoltosyklit_laitteet.yhtio
//        AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
//      JOIN tuote
//      ON ( tuote.yhtio = huoltosykli.yhtio
//        AND tuote.tuoteno = huoltosykli.toimenpide)
//      LEFT JOIN tuotteen_avainsanat AS tt
//      ON ( tt.yhtio = tuote.yhtio
//        AND tt.tuoteno = tuote.tuoteno)
//      SET huoltosyklit_laitteet.viimeinen_tapahtuma = NULL
//      WHERE huoltosyklit_laitteet.yhtio = '{$kukarow['yhtio']}'
//      AND tt.selite != 'koeponnistus'",
//    "  UPDATE huoltosyklit_laitteet
//      JOIN laite
//      ON ( laite.yhtio = huoltosyklit_laitteet.yhtio
//        AND laite.tunnus = huoltosyklit_laitteet.laite_tunnus )
//      JOIN huoltosykli
//      ON ( huoltosykli.yhtio = huoltosyklit_laitteet.yhtio
//        AND huoltosykli.tunnus = huoltosyklit_laitteet.huoltosykli_tunnus )
//      JOIN tuote
//      ON ( tuote.yhtio = huoltosykli.yhtio
//        AND tuote.tuoteno = huoltosykli.toimenpide)
//      LEFT JOIN tuotteen_avainsanat AS tt
//      ON ( tt.yhtio = tuote.yhtio
//        AND tt.tuoteno = tuote.tuoteno)
//      SET huoltosyklit_laitteet.viimeinen_tapahtuma = laite.valm_pvm
//      WHERE huoltosyklit_laitteet.yhtio = '{$kukarow['yhtio']}'
//      AND tt.selite = 'koeponnistus'",
  );
  //toka vika query: ei p‰ivitet‰ koeponnistus huoltosyklien viimeist‰ tapahtumap‰iv‰‰ nulliksi.
  //vika query: p‰ivitet‰‰n laite.valm_pvm koeponnistuksen huoltosyklit_laitteet riville
    
  foreach ($query as $q) {
    pupe_query($q);
  }
}

require('inc/footer.inc');
