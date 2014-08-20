<?php

if (isset($_COOKIE['laiteluettelo_keksi'])) $valitut_sarakkeet = unserialize(urldecode($_COOKIE['laiteluettelo_keksi']));

if (isset($_POST['piirtele_laiteluettelo'])) {
  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
        $(function() {
          $('.check_all').on('click', function() {
            var id = $(this).val();

            if ($(this).is(':checked')) {
              $('.'+id).attr('checked', true);
            }
            else {
              $('.'+id).attr('checked', false);
            }
          });
        });
      </script>";

  // Piirrellään laiteluettelo-valikko
  echo "<br><br>";

  // Määritellään jostain halutut kentät
  $kiissit = array("tunnus","laitteen_tunnus","sarjanro","tuotemerkki","valmistaja","sla","nimitys","sopimus_lisatietoja","sopimus_lisatietoja2","sopimus_alkaa","sopimus_loppuu","sopimus_alkupvm","sopimus_loppupvm");

  echo "<form>";
  echo "<table border='0' cellpadding='5' cellspacing='0' width='600'>";
  echo "<tr><th>".t("Valitse sarakkeet")."<br><br><input type='checkbox' class='check_all' value='sarakeboksit'>".t("Valitse Kaikki")."</th></tr>";

  $secretcounter = 0;
  $eka_ajo = true;

  foreach ($kiissit as $i => $selite) {
    $nollaus = false;
    if ($secretcounter == 0) echo "<tr>";
    $tsekk = "";

    foreach ($valitut_sarakkeet as $osuma) {
      if ($osuma == $selite) {
        $tsekk = "CHECKED";
        break;
      }
    }

    echo "<td align='left' valign='top' nowrap><input type='checkbox' class='sarakeboksit' name='valitut_sarakkeet[]' value='{$selite}' $tsekk>{$selite}</td>";

    if ($secretcounter == 2) {
      echo "</tr>";
      $secretcounter = 0;
      $nollaus = true;
    }

    $eka_ajo = false;
    if (!$nollaus) $secretcounter++;
  }

  echo "<br><br>";
  echo "  <tr>
          <td align='left' class='back' valign='top'>
        <form name='aja_ja_tallenna' method='post'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='otunnus' value='$tilausnumero'>
        <input type='hidden' name='tilausnumero' value='$tilausnumero'>
        <input type='hidden' name='mista' value = '$mista'>
        <input type='hidden' name='toim_nimitykset' value='$toim_nimitykset'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='naantali' value='EIENAA'>
        <input type='submit' name='aja_ja_tallenna' value='".t("Valmis")."'>
        </form>
        </td>
      </tr>";
  echo "</table>";
  echo "</form>"; 
}
elseif (isset($valitut_sarakkeet) and count($valitut_sarakkeet) > 0) {
  // täällä ajellaan rapsa ja tallennetaan henkseliin
  // Haetaan sopimuskentät, tilausrivien(palveluiden) ja valittavat laitetiedot
  $query = "SELECT lasku.tunnus,laitteen_sopimukset.laitteen_tunnus, laite.sarjanro, tuote.tuotemerkki, avainsana.selitetark valmistaja, laite.sla,tilausrivi.nimitys, laskun_lisatiedot.sopimus_lisatietoja, laskun_lisatiedot.sopimus_lisatietoja2,
            tilausrivin_lisatiedot.sopimus_alkaa, tilausrivin_lisatiedot.sopimus_loppuu, laskun_lisatiedot.sopimus_alkupvm, laskun_lisatiedot.sopimus_loppupvm
            FROM laitteen_sopimukset
            JOIN laite ON laite.tunnus = laitteen_sopimukset.laitteen_tunnus
            JOIN tilausrivi ON tilausrivi.tunnus = laitteen_sopimukset.sopimusrivin_tunnus AND tilausrivi.yhtio = '{$kukarow['yhtio']}'
            JOIN tuote ON tuote.yhtio = tilausrivi.yhtio AND  tuote.tuoteno = laite.tuoteno
            JOIN avainsana ON avainsana.yhtio = tuote.yhtio AND avainsana.laji = 'TRY' AND avainsana.selite = tuote.try
            JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN lasku ON lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus
            JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio = lasku.yhtio AND laskun_lisatiedot.otunnus = lasku.tunnus
            WHERE lasku.tunnus = '{$tilausnumero}' ORDER BY nimitys, laitteen_tunnus";
  $result = pupe_query($query);

  // Rustaillaan henkseliin kaikki valitut sarakkeet (osan vois laittaa pakollisiks tietenkin)
  while ($row = mysql_fetch_assoc($result)) {
    var_dump($row);
  }
}
