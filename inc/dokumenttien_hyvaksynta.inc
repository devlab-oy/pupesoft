<?php

//  Jos olemme tekemässä jotain meillä on oltava kuka tai listaamme kaikki laskut mitä tietokannasta löytyy!
if ($tee == "T" and strlen(trim($kuka)) == 0) {
  echo "<font class='error'>".t("VIRHE: Hyväksyjä puuttuu")."!</font><br><br>";
  $tee = "";
}

if ($tee == '') {
  // Summaus hyväksynnässä olevista dokumenteista
  echo "<font class='head'>".t("Dokumentteja hyväksymättä")."</font><hr>";

  $query = "SELECT hyvaksyja_nyt,
            if(kuka.nimi is not null, kuka.nimi, hyvaksyja_nyt) nimi,
            count(*) kpl
            FROM hyvaksyttavat_dokumentit
            LEFT JOIN kuka ON kuka.yhtio=hyvaksyttavat_dokumentit.yhtio and kuka.kuka = hyvaksyttavat_dokumentit.hyvaksyja_nyt
            WHERE hyvaksyttavat_dokumentit.yhtio = '$kukarow[yhtio]'
            and hyvaksyttavat_dokumentit.tila = 'H'
            and hyvaksyttavat_dokumentit.hyvaksyja_nyt != ''
            GROUP BY 1, 2
            ORDER BY nimi";
  $result = pupe_query($query);

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Kuka")."</th>";
  echo "<th>".t("Dokumentteja")."</th>";
  echo "</tr>";

  while ($trow = mysql_fetch_array($result)) {
    echo "<tr class='aktiivi'>";
    echo "<td><a href = 'raportit.php?toim=dokumenttien_hyvaksynta&tee=T&kuka=$trow[hyvaksyja_nyt]&lopetus=raportit.php////toim=dokumenttien_hyvaksynta'>$trow[nimi]</a></td>";
    echo "<td>$trow[kpl]</td>";
    echo "</tr>";
  }
  echo "</table><br>";
}

// Yhden käyttäjän pöydällä olevat dokumnetit tai sen muutos
if ($tee == 'T') {
  // Muutetaan hyväksyntää
  if ((strlen($mika) > 0) and (strlen($nimi) > 0)) {
    $query = "SELECT hyvak1, hyvak2, hyvak3, hyvak4, hyvak5, hyvaksyja_nyt, h1time, h2time, h3time, h4time, h5time
              FROM hyvaksyttavat_dokumentit
              WHERE tunnus = '$mika' and yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      echo "<font class='error'>".t("Muutettavaa dokumenttia ei löytynyt")."!</font>";
      exit;
    }

    $trow = mysql_fetch_array($result);
    $mita = "";

    // Aikaikkunan poisto, joku muu ehti ennen meitä
    if ($trow['hyvaksyja_nyt'] == $kuka) {
      if (($trow['hyvak1'] == $kuka) and ($trow['h1time'] == '0000-00-00 00:00:00')) $mita = "hyvak1";
      elseif (($trow['hyvak2'] == $kuka) and ($trow['h2time'] == '0000-00-00 00:00:00')) $mita = "hyvak2";
      elseif (($trow['hyvak3'] == $kuka) and ($trow['h3time'] == '0000-00-00 00:00:00')) $mita = "hyvak3";
      elseif (($trow['hyvak4'] == $kuka) and ($trow['h4time'] == '0000-00-00 00:00:00')) $mita = "hyvak4";
      elseif (($trow['hyvak5'] == $kuka) and ($trow['h5time'] == '0000-00-00 00:00:00')) $mita = "hyvak5";
      else {
        echo "<font class='error'>".t("Dokumentin siirto ei onnistunut")."</font> '$kuka' --> '$nimi'<br>";
        exit;
      }

      $query = "UPDATE hyvaksyttavat_dokumentit set
                $mita = '$nimi',
                hyvaksyja_nyt  = '$nimi'
                WHERE tunnus = '$mika'";
      $result = pupe_query($query);
      echo "<font class='message'>".t("Dokumentti siirrettiin").".. '$kuka' --> '$nimi'</font><br>";
    }
    else {
      echo "<font class='error'>".t("Dokumentti ei enää ollut siirrettävissä")."!</font><br>";
    }
  }

  $query = "SELECT nimi, kuka, tuuraaja
            FROM kuka
            WHERE yhtio = '$kukarow[yhtio]'
            and kuka    = '$kuka'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='message'>".t("Käyttäjää ei enää löydy tältä yhtiöltä")."!</font><br>";
    $trow = array();
  }
  else {
    $trow = mysql_fetch_array($result);
  }

  echo "<font class='head'>$trow[nimi]".t(":n pöydällä olevat dokumentit")."</font><hr>";

  // Tehdään popup, jolla voidaan hyväksyjä myöhemmin vaihtaa
  $query = "SELECT DISTINCT kuka.kuka, kuka.nimi
            FROM kuka
            JOIN oikeu ON oikeu.yhtio = kuka.yhtio and oikeu.kuka = kuka.kuka and oikeu.nimi like '%dokumenttien_hyvaksynta.php'
            WHERE kuka.yhtio    = '$kukarow[yhtio]'
            AND kuka.aktiivinen = 1
            AND kuka.extranet   = ''
            ORDER BY kuka.nimi";
  $result = pupe_query($query);

  $ulos = "<select name='nimi'>";

  while ($vrow = mysql_fetch_array($result)) {
    $sel = "";
    if ($vrow['kuka'] == $trow['tuuraaja']) {
      $sel = "selected";
    }
    $ulos .= "<option value = '$vrow[kuka]' $sel>$vrow[nimi]";
  }
  $ulos .= "</select>";

  $query = "SELECT hyvaksyttavat_dokumentit.*, kuka.nimi laatija
            FROM hyvaksyttavat_dokumentit
            LEFT JOIN kuka ON kuka.yhtio = hyvaksyttavat_dokumentit.yhtio and kuka.kuka = hyvaksyttavat_dokumentit.hyvaksyja_nyt
            WHERE hyvaksyttavat_dokumentit.hyvaksyja_nyt='$kuka'
            and hyvaksyttavat_dokumentit.yhtio = '$kukarow[yhtio]'
            and hyvaksyttavat_dokumentit.tila = 'H'
            ORDER BY hyvaksyttavat_dokumentit.tunnus";
  $result = pupe_query($query);

  pupe_DataTables(array(array($pupe_DataTables[0], 6, 6)));

  echo "<table class='display dataTable' id='$pupe_DataTables[0]'>";
  echo "<thead>";
  echo "<tr>";
  echo "<th>".t("Nimi")."</th>";
  echo "<th>".t("Kuvaus")."</th>";
  echo "<th>".t("Kommentit")."</th>";
  echo "<th>".t("Laatija")."</th>";
  echo "<th>".t("Tiedosto")."</th>";
  echo "<th>".t("Siirrä")."</th>";
  echo "</tr>";
  echo "<tr>";
  echo "<td><input type='text' class='search_field' name='search_nimi'></td>";
  echo "<td><input type='text' class='search_field' name='search_kuvaus'></td>";
  echo "<td><input type='text' class='search_field' name='search_kommentit'></td>";
  echo "<td><input type='text' class='search_field' name='search_laatija'></td>";
  echo "<td><input type='text' class='search_field' name='search_tiedosto'></td>";
  echo "<td><input type='text' class='search_field' name='search_siirra'></td>";
  echo "</tr>";
  echo "</thead>";
  echo "<tbody>";

  while ($trow = mysql_fetch_array($result)) {

    echo "<tr class='aktiivi'>";

    echo "<td>$trow[nimi]</td>";
    echo "<td>$trow[kuvaus]</td>";
    echo "<td>$trow[kommentit]</td>";
    echo "<td>$trow[laatija]</td>";

    $query = "SELECT tunnus, filename, selite
              from liitetiedostot
              where liitostunnus = '{$trow['tunnus']}'
              and liitos = 'hyvaksyttavat_dokumentit'
              and yhtio = '{$kukarow['yhtio']}'
              ORDER BY tunnus";
    $res = pupe_query($query);

    echo "<td>";

    while ($row = mysql_fetch_assoc($res)) {
      echo "<div id='div_$row[tunnus]' class='popup'>$row[filename]<br>$row[selite]</div>";
      echo js_openUrlNewWindow("{$palvelin2}view.php?id=$row[tunnus]", t('Liite').": $row[filename]", "class='tooltip' id='$row[tunnus]'")."<br>";
    }

    echo "</td>";
    echo "<td>";
    echo "<form action = 'raportit.php?toim=dokumenttien_hyvaksynta&tee=T&kuka=$kuka' method = 'post'>
          <input type = 'hidden' name = 'mika' value = '$trow[tunnus]'>
          $ulos
          <input type = 'submit' value = '".t("Siirrä")."'>
          </form>
          </td>
          </tr>";

  }

  echo "</tbody>";
  echo "</table><br>";
}
