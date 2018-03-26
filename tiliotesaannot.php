<?php

require "inc/parametrit.inc";

if (!isset($tiliote) and $tiliote != "Z") echo "<font class='head'>".t("Tiliotteen tiliöintisäännöt")."</font><hr>";

// Jos $pankkitili = 'x' niin kyseessä on viiteaineiston sääntö

if ($tee == 'P') {
  // Olemassaolevaa sääntöä muutetaan, joten poistetaan rivi ja annetaan perustettavaksi
  $query = "SELECT *
            FROM tiliotesaanto
            WHERE tunnus = '$tunnus'
            and yhtio    = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo t("Sääntöä ei löydy")."! $query";
    exit;
  }

  $tiliointirow   = mysql_fetch_array($result);
  $koodi       = $tiliointirow['koodi'];
  $koodiselite  = $tiliointirow['koodiselite'];
  $nimitieto     = $tiliointirow['nimitieto'];
  $selite     = $tiliointirow['selite'];
  $tilino     = $tiliointirow['tilino'];
  $tilino2     = $tiliointirow['tilino2'];
  $kustp       = $tiliointirow['kustp'];
  $kustp2     = $tiliointirow['kustp2'];
  $pankkitili   = $tiliointirow['pankkitili'];
  $erittely     = $tiliointirow['erittely'];
  $ok       = 1;

  $query = "DELETE from tiliotesaanto WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);
}

if ($tee == 'U') {
  // Tarkistetaan sääntö
  if ($erittely == '') {
    $virhe="";
    $query = "SELECT tilino
              FROM tili
              WHERE tilino = '$tilino' and yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $virhe .= t("Tiliä ei löydy")."<br>";
      $ok = 1;
      $tee = '';
    }

    $nimitieto = strtoupper($nimitieto);

    if (($nimitieto=="LUOTTOKUNTA-KREDITLAGET") or ($nimitieto=="LUOTTOKUNTA") or ($nimitieto=="LUOTTOKUNTA/VISA") or ($nimitieto=="LUOTTOKUNTA OY") or ($nimitieto=="NETS OY") or ($nimitieto=="NETS DENMARK A/S FILIAL I FINLAND")) {
      $query = "SELECT tilino
                FROM tili
                WHERE tilino = '$tilino2' and yhtio = '$kukarow[yhtio]'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        $virhe .= t("Palkkiotiliä ei löydy")."<br>";
        $ok = 1;
        $tee = '';
      }

      if ($kustp2 != 0) {
        $query = "SELECT tunnus
                  FROM kustannuspaikka
                  WHERE tunnus  = '$kustp2'
                  and yhtio     = '$kukarow[yhtio]'
                  and kaytossa != 'E'
                  and tyyppi    = 'K'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 0) {
          $virhe.= t("Kustannuspaikkaa ei löydy")."<br>";
          $ok = 1;
          $tee = '';
        }
      }
    }
    else {
      if ($tilino2 != 0) {
        $virhe.= t("Vain maksajalle LUOTTOKUNTA-KREDITLAGET tai LUOTTOKUNTA tai LUOTTOKUNTA/VISA tai NETS OY tai NETS DENMARK A/S FILIAL I FINLAND voi antaa palkkiotilin")."<br>";
        $ok = 1;
        $tee = '';
      }
      if ($kustp2 != 0) {
        $virhe.= t("Vain maksajalle LUOTTOKUNTA-KREDITLAGET voi antaa palkkiokustannuspaikan")."<br>";
        $ok = 1;
        $tee = '';
      }
    }
  }
  else {
    if ($tilino !='') {
      $virhe.= t("Erittelyn ohitusriville ei voi antaa tilinumeroa")."<br>";
      $ok = 1;
      $tee = '';
    }
  }

  if ($pankkitili !='x') {
    $query = "SELECT tilino
              FROM yriti
              WHERE tilino = '$pankkitili'
              and yhtio    = '$kukarow[yhtio]'
              and kaytossa = ''";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $virhe.= t("Pankkitiliä ei enää löydy")."<br>";
      $ok = 1;
      $tee = '';
    }
  }
}

if ($tee == 'U') {
  // Lisätään sääntö
  $query = "INSERT into tiliotesaanto
            (yhtio, pankkitili, koodi, koodiselite, nimitieto, selite,  tilino, tilino2, kustp, kustp2, erittely)
            VALUES ('$kukarow[yhtio]', '$pankkitili', '$koodi', '$koodiselite', '$nimitieto', '$selite', '$tilino', '$tilino2', '$kustp', '$kustp2', '$erittely')";
  $result = pupe_query($query);
}

if (strlen($pankkitili) != 0) {
  // Pankkitili on valittu ja sille annetaan sääntöjä
  if ($pankkitili != 'x') {
    $query = "SELECT nimi, tilino, tunnus
              FROM yriti
              WHERE tilino = '$pankkitili'
              and yhtio    = '$kukarow[yhtio]'
              and kaytossa = ''";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      echo "<b>".t("Pankkitili")." $pankkitili ".t("katosi")."</b><br>";
      exit;
    }

    $yritirow = mysql_fetch_assoc($result);

    if (!isset($tiliote) and $tiliote != "Z") {
      echo "<table><tr>";
      echo "<th>".t("Nimi")."</th>";
      echo "<th>".t("Tili")."</th>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>$yritirow[nimi]</td>";
      echo "<td>$yritirow[tilino]</td>";
      echo "</tr>";
      echo "</table><br>";
    }
  }
  else {
    echo "<font class='message'>".t("Viiteaineistosäännöt")."</font><br>";
  }

  echo "<font class='head'>".t("Säännöt")."</font><hr><table>";

  // Näytetään vanhat säännöt muutosta varten (viitesäännöille himan eri pohja)
  if ($pankkitili != 'x') {
    $query = "SELECT tunnus, koodi, koodiselite, nimitieto, selite, erittely, tilino, kustp, tilino2, kustp2
              FROM tiliotesaanto
              WHERE yhtio    = '$kukarow[yhtio]'
              and pankkitili = '$pankkitili'
              ORDER BY 2,3,4";
  }
  else {
    $query = "SELECT tunnus, selite, tilino
              FROM tiliotesaanto
              WHERE yhtio    = '$kukarow[yhtio]'
              and pankkitili = '$pankkitili'
              ORDER BY 2";
  }
  $result = pupe_query($query);

  echo "<tr>";
  for ($i = 1; $i < mysql_num_fields($result); $i++) {
    echo "<th>" . t(mysql_field_name($result, $i))."</th>";
  }
  echo "</tr>";

  while ($tiliointirow = mysql_fetch_array($result)) {
    echo "<tr>";
    for ($i = 1; $i<mysql_num_fields($result); $i++) {
      if (mysql_field_name($result, $i) == 'kustp') {
        echo "<td>";
        if ($tiliointirow[$i] != 0) { // Meillä on kustannuspaikka
          $query = "SELECT nimi
                    FROM kustannuspaikka
                    WHERE yhtio   = '$kukarow[yhtio]'
                    and tunnus    = '$tiliointirow[$i]'
                    and kaytossa != 'E'
                    and tyyppi    = 'K'";
          $xresult = pupe_query($query);
          $xrow = mysql_fetch_array($xresult);
          echo "$xrow[0]";
        }
        echo "</td>";
      }
      elseif (mysql_field_name($result, $i) == 'kustp2') {
        echo "<td>";
        if ($tiliointirow[$i] != 0) { // Meillä on kustannuspaikka
          $query = "SELECT nimi
                    FROM kustannuspaikka
                    WHERE yhtio   = '$kukarow[yhtio]'
                    and tunnus    = '$tiliointirow[$i]'
                    and kaytossa != 'E'
                    and tyyppi    = 'K'";
          $xresult = pupe_query($query);
          $xrow = mysql_fetch_array($xresult);

          echo "$xrow[0]";
        }
        echo "</td>";
      }
      else {
        echo "<td>$tiliointirow[$i]</td>";
      }
    }
    echo "<td class='back'>
        <form method='post'>
        <input type='hidden' name='lopetus' value = '$lopetus'>
        <input type='hidden' name='pankkitili' value = '$pankkitili'>
        <input type='hidden' name='tunnus' value = '$tiliointirow[0]'>
        <input type='hidden' name='tee' value = 'P'>
        <input type='submit' value = '".t("Muuta")."'>
      </td></tr></form>";
  }

  // Annetaan mahdollisuus tehdä uusi sääntö
  if ($ok != 1) {
    // Annetaan tyhjät tiedot, jos rivi oli virheetön
    $koodi = '';
    $koodiselite= '';
    $nimitieto = '';
    $tilino = '';
    $selite = '';
    $erittely = '';
    $tilino2 = '';
    $kustp = '';
    $kustp2 = '';
  }

  $query = "SELECT tunnus, nimi
            FROM kustannuspaikka
            WHERE yhtio   = '$kukarow[yhtio]'
            and tyyppi    = 'K'
            and kaytossa != 'E'
            ORDER BY koodi+0, koodi, nimi";
  $result = pupe_query($query);

  $ulos = "<select ".js_alasvetoMaxWidth($nimi, 100)." name = 'kustp'><option value = ' '>".t("Ei kustannuspaikkaa")."</option>";

  while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
    $valittu = "";
    if ($kustannuspaikkarow['tunnus'] == $kustp) {
      $valittu = "selected";
    }
    $ulos .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[nimi]</option>";
  }
  $ulos .= "</select>";

  mysql_data_seek($result, 0);

  $ulos2 = "<select ".js_alasvetoMaxWidth($nimi, 100)." name = 'kustp2'><option value = ' '>".t("Ei kustannuspaikkaa")."</option>";

  while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
    $valittu = "";
    if ($kustannuspaikkarow['tunnus'] == $kustp2) {
      $valittu = "selected";
    }
    $ulos2 .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[nimi]</option>";
  }

  $ulos2 .= "</select>";

  if ($pankkitili != 'x') {
    if (substr($erittely, 0, 1)=='o') $erittely='checked'; else $erittely='';

    echo "<tr>
        <td><form method='post'>
          <input type='hidden' name='lopetus' value = '$lopetus'>
          <input type='hidden' name='tee' value = 'U'>
          <input type='hidden' name='pankkitili' value = '$pankkitili'>
          <input type='text' name='koodi' size='3' value = '$koodi'>
        </td>
        <td><input type='text' name='koodiselite' size='15' value = '$koodiselite'></td>
        <td><input type='text' name='nimitieto' size='25' value = '$nimitieto'></td>
        <td><input type='text' name='selite' size='50' value = '$selite'></td>
        <td><input type='checkbox' name='erittely' $erittely></td>
        <td><input type='text' name='tilino' size='6' value = '$tilino'></td>
        <td>$ulos</td>
        <td><input type='text' name='tilino2' size='6' value = '$tilino2'></td>
        <td>$ulos2</td>
        <td class='back'>$virhe <input type='submit' value = '".t("Lisää")."'>
        </form>
        </td>
      </tr></table>";
  }
  else {
    echo "<tr>
        <td class='back'><form method='post'>
          <input type='hidden' name='lopetus' value = '$lopetus'>
          <input type='hidden' name='tee' value = 'U'>
          <input type='hidden' name='pankkitili' value = '$pankkitili'>
          <input type='text' name='selite' size='15' value = '$selite'></td>
        <td><input type='text' name='tilino' size='6' value = '$tilino'></td>
        <td>$virhe <input type='submit' value = '".t("Lisää")."'>
        </form>
        </td>
      </tr></table>";
  }
}
else {
  // Tällä ollaan, jos olemme vasta valitsemassa pankkitiliä
  $query = "SELECT *
            FROM yriti
            WHERE yhtio  = '$kukarow[yhtio]'
            and kaytossa = ''
            ORDER BY nimi";
  $result = pupe_query($query);

  echo "<form name = 'valinta' method='post'>
      <input type='hidden' name='lopetus' value = '$lopetus'>
      <table>
      <td>
      <select name = 'pankkitili'><option value = 'x'>".t("Viiteaineisto");

  while ($yritirow=mysql_fetch_array($result)) {
    $valittu = "";
    if ($yritirow['tilino'] == $pankkitili) {
      $valittu = "selected";
    }
    echo "<option value = '$yritirow[tilino]' $valittu>$yritirow[nimi] ($yritirow[tilino])";
  }
  echo "</select></td>
      <td><input type = 'submit' value = '".t("Valitse")."'></td>
      </tr></table></form>";
}

require "inc/footer.inc";
