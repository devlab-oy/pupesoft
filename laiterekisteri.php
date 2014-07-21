<?php

if (strpos($_SERVER['SCRIPT_NAME'], "laiterekisteri.php") !== FALSE) {
  require("inc/parametrit.inc");
}

echo "<font class='head'>".t("Laiterekisteri")."</font><hr>";

// Halutaan muuttaa laitteen tietoja
if ($toiminto == 'MUOKKAA') {
  /*echo "<form method='post' action='laiterekisteri.php' name='muokkaaformi'>";
  echo "<input type='hidden' name='muut_siirrettavat'  value='$muut_siirrettavat'>";
  echo "<input type='hidden' name='$tunnuskentta'     value='$rivitunnus'>";
  echo "<input type='hidden' name='from'         value='$from'>";
  echo "<input type='hidden' name='lopetus'       value='$lopetus'>";
  echo "<input type='hidden' name='aputoim'       value='$aputoim'>";
  echo "<input type='hidden' name='otunnus'       value='$otunnus'>";
  echo "<input type='hidden' name='toiminto'       value='MUOKKAA'>";
  echo "<input type='hidden' name='sarjatunnus'     value='$sarjatunnus'>";
  echo "<input type='hidden' name='sarjanumero_haku'   value='$sarjanumero_haku'>";
  echo "<input type='hidden' name='tuoteno_haku'     value='$tuoteno_haku'>";
  echo "<input type='hidden' name='nimitys_haku'     value='$nimitys_haku'>";
  echo "<input type='hidden' name='varasto_haku'     value='$varasto_haku'>";
  echo "<input type='hidden' name='ostotilaus_haku'   value='$ostotilaus_haku'>";
  echo "<input type='hidden' name='myyntitilaus_haku'  value='$myyntitilaus_haku'>";
  echo "<input type='hidden' name='lisatieto_haku'     value='$lisatieto_haku'>";*/
}
else {
  // Haetaan kaikkien laiterekisterin laitteiden,asiakkaiden ja sopimusten tiedot
  $query = "SELECT
            laite.*,
            avainsana.selitetark valmistaja,
            tuote.tuotemerkki 
            FROM laite
            JOIN tuote on tuote.yhtio = laite.yhtio
              AND tuote.tuoteno = laite.tuoteno
            JOIN avainsana on avainsana.yhtio = tuote.yhtio
              AND avainsana.selite = tuote.try
              AND avainsana.laji = 'TRY'
            WHERE laite.yhtio = '{$kukarow['yhtio']}'";
  $res = pupe_query($query);
  echo "<table>";
  echo "<tr>";
  echo "<th>Nro</th>";
  // ----------Sopimukset1/2
  // Sopimusnumero
  // Sopimus k/e
  // LCC k/e
  // MDM k/e
  // NOC k/e
  // Service Desk k/e
  // LCM k/e
  
  // Invoice site
  
  // ---- Tuote
  echo "<th>Valmistaja</th>";
  echo "<th>Malli</th>";
  // ----- Laite 1/2
  echo "<th>Sarjanumero</th>";
  echo "<th>Tuotenumero</th>";
  // ----------Sopimukset2/2
  // LCC SLA
  // LCC start date
  // LCC end date
  // VC
  // VC No
  // VC end date
  // VC SLA
  // MDM end date
  // SD SLA
  // NOC e/kk
  // MDM e/kk
  // LCC e/kk
  // LCM e/kk

  // ----- Laite 2/2
  echo "<th>Kommentti</th>";
  echo "<th>LCM info</th>";
  echo "<th>IP</th>";
  echo "<th>MAC</th>";

  echo "<th>Laatija</th>";
  echo "<th>Luontiaika</th>";

  echo "</tr>";
  while ($rowi = mysql_fetch_assoc($res)) {
    echo "<tr>";
    
    echo "<td nowrap>".$rowi['tunnus']."</td>";
    // Tuote
    echo "<td nowrap>".$rowi['valmistaja']."</td>";
    echo "<td nowrap>".$rowi['tuotemerkki']."</td>";
    // Sopimukset 1/2
    echo "<td nowrap>".$rowi['sarjanro']."</td>";
    echo "<td nowrap>".$rowi['tuoteno']."</td>";
     // Sopimukset 2/2
    echo "<td style='width:300px;'>".$rowi['kommentti']."</td>";
    echo "<td>".$rowi['lcm_info']."</td>";
    echo "<td nowrap>".$rowi['ip_osoite']."</td>";
    echo "<td nowrap>".$rowi['mac_osoite']."</td>";
    echo "<td>".$rowi['laatija']."</td>";
    echo "<td>".$rowi['luontiaika']."</td>";
    echo "</tr>";
  }
  echo "</table>";
}
