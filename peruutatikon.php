<?php
//Tositelajit ovat 11, 30, 41
//Tositenrot ovat yyxxxxxx, jossa yy on tositelaji ja xxxxxx on tositenro

require "inc/parametrit.inc";
echo "<font class='head'>".t('TIKON-siirron peruutus')."</font><hr>";

if (isset($kausi)) {
  if(!isset($ok)) {
    echo "<font class='message'>".t("Peruutettavat tositteet")."</font><br><br>";
    echo "<table>";

    $query  = "SELECT max(tosite) pienin, min(tosite) suurin
               FROM tiliointi
               WHERE yhtio  = '$kukarow[yhtio]'
               and korjattu = ''
               and left(tapvm,7) = '$kausi'
               and tosite   >= '91000000'
               and tosite   <= '91999999'";

    $result = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($result) == 1) {
      $row = mysql_fetch_array ($result);
      echo "<tr><th>".t("Myyntisaamisia")."</th><td>$row[pienin]</td><td>-</td><td>$row[suurin]</td></tr>";
    }

    $query  = "SELECT max(tosite) pienin, min(tosite) suurin
               FROM tiliointi
               WHERE yhtio  = '$kukarow[yhtio]'
               and korjattu = ''
               and left(tapvm,7) = '$kausi'
               and tosite   >= '93000000'
               and tosite   <= '93999999'";
    $result = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($result) == 1) {
      $row = mysql_fetch_array ($result);
      echo "<tr><th>".t("Ostovelkoja")."</th><td>$row[pienin]</td><td>-</td><td>$row[suurin]</td></tr>";
    }

    $query  = "SELECT max(tosite) pienin, min(tosite) suurin
               FROM tiliointi
               WHERE yhtio  = '$kukarow[yhtio]'
               and korjattu = ''
               and left(tapvm,7) = '$kausi'
               and tosite   >= '50000000'
               and tosite   <= '50999999'";
    $result = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($result) == 1) {
      $row = mysql_fetch_array ($result);
      echo "<tr><th>".t("Tiliotteiden tiliöinnit")."</th><td>$row[pienin]</td><td>-</td><td>$row[suurin]</td></tr>";
    }

    echo "</table><br><br>
      <form name = 'valinta' method='post'>
      <input type = 'hidden' name='ok' value = '1'>
      <input type = 'hidden' name='kausi' value = '$kausi'>
      <input type = 'submit' value = '".t("Peruuta kausi $kausi")."'>
      </form>";
  }
  else {
    $query  = "UPDATE tiliointi SET tosite = ''
               WHERE yhtio  = '$kukarow[yhtio]'
               and korjattu = ''
               and tosite   > 0
               and left(tapvm,7) = '$kausi'";
    $result = mysql_query($query) or pupe_error($query);

    echo "<br><font class='message'>".t("Peruutin TIKON-siirron kaudelta").": $kausi</font><br><br>";
  }
}

if (!isset($kausi)) {
  //Etsitään viimeksi viety kausi

  //Myyntisaamiset
  $query  = "SELECT tunnus, left(tapvm, 7) kausi
             FROM tiliointi
             WHERE yhtio='$kukarow[yhtio]'
             and korjattu=''
             and tosite >= '91000000'
             and tosite <= '91999999'
             ORDER BY tosite desc
             LIMIT 1";
  $result = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($result) == 1) {
    $row11 = mysql_fetch_array ($result);
  }

  //Ostovelat
  $query  = "SELECT tunnus,left(tapvm,7) kausi
             FROM tiliointi
             WHERE yhtio='$kukarow[yhtio]'
             and korjattu=''
             and tosite >= '93000000'
             and tosite <= '93999999'
             ORDER BY tosite desc
             LIMIT 1";
  $result = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($result) == 1) {
    $row30=mysql_fetch_array ($result);
  }

  //Tiliotteet
  $query  = "SELECT tunnus,left(tapvm,7) kausi
             FROM tiliointi
             WHERE yhtio='$kukarow[yhtio]'
             and korjattu=''
             and tosite >= '50000000'
             and tosite <= '50999999'
             ORDER BY tosite desc
             LIMIT 1";
  $result = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($result) == 1) {
    $row41=mysql_fetch_array ($result);
  }

  $suurin = '0000-00';

  if (is_array($row11)) $suurin = $row11['kausi'];
  if (is_array($row30) and $row30['kausi'] > $suurin) $suurin = $row30['kausi'];
  if (is_array($row41) and $row41['kausi'] > $suurin) $suurin = $row41['kausi'];

  echo "<br><font class='message'>".t("Ehdotan peruutettavaksi kautta")." $suurin. ".t("Se on siirretty viimeksi").".</font><br><br>";
  echo "<form name = 'valinta' method='post'>
    <table>
    <tr><th>".t("Anna peruutettava kausi")."</th><td><input type = 'text' name = 'kausi' size=8 value ='$suurin'></td>
    <td class='back'><input type = 'submit' value = '".t("Peruuta valittu kausi")."'></td></tr>
    </table></form>";

  $formi = 'valinta';
  $kentta = 'kausi';
}

require "inc/footer.inc";
