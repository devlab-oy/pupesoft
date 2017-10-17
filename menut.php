<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Menujen ylläpito")."</font><hr>";

$syncyhtiot = $yhtiot;
unset($syncyhtiot["REFERENSSI"]);

// Synkronoidaan kahden firman menut
if (isset($synkronoi) and count($syncyhtiot) > 1) {

  $yht = "";
  foreach ($syncyhtiot as $yhtio) {
    $yht .= "'$yhtio',";
  }

  $yht = substr($yht, 0, -1);

  if ($sovellus != '') {
    $lisa = " and sovellus = '$sovellus' ";
  }
  else {
    $lisa = "";
  }

  $query = "SELECT sovellus, nimi, alanimi, min(nimitys) nimitys, min(jarjestys) jarjestys, min(jarjestys2) jarjestys2, max(hidden) hidden, max(usermanualurl) usermanualurl
            FROM oikeu
            WHERE yhtio   in ($yht)
            and kuka      = ''
            and sovellus != ''
            $lisa
            GROUP BY sovellus, nimi, alanimi
            ORDER BY sovellus, jarjestys, jarjestys2";
  $result = pupe_query($query);

  //poistetaan molemilta yhtiöiltä tämä menu
  $query = "DELETE
            FROM oikeu
            WHERE yhtio in ($yht)
            and kuka    = ''
            $lisa";
  $deleteresult = pupe_query($query);

  $jarj  = 0;
  $jarj2 = 0;

  while ($row = mysql_fetch_assoc($result)) {

    if ($edsovellus != $row["sovellus"]) {
      $jarj  = 0;
      $jarj2 = 0;
    }

    if ($row["jarjestys"] != $edjarjoikea or (($row["nimi"] != $ednimi or $row["alanimi"] != $edalan) and $row["jarjestys2"] == 0 )) {
      $jarj += 10;
      $jarj2 = 0;
    }

    if ($row["jarjestys2"] != 0 and $edjarjoikea != $row["jarjestys"]) {
      $jarj2 = 10;
    }

    if ($row["jarjestys2"] != 0 and $row["jarjestys"] == $edjarjoikea) {
      $jarj2 += 10;
    }

    foreach ($syncyhtiot as $uusiyhtio) {
      $query = "INSERT into oikeu SET
                kuka          = '',
                profiili      = '',
                sovellus      = '$row[sovellus]',
                nimi          = '$row[nimi]',
                alanimi       = '$row[alanimi]',
                nimitys       = '$row[nimitys]',
                jarjestys     = '$jarj',
                jarjestys2    = '$jarj2',
                hidden        = '$row[hidden]',
                usermanualurl = '$row[usermanualurl]',
                yhtio         = '$uusiyhtio',
                laatija       = '{$kukarow['kuka']}',
                luontiaika    = now(),
                muutospvm     = now(),
                muuttaja      = '{$kukarow['kuka']}'";
      $insresult = pupe_query($query);

      //päivitettän käyttäjien oikeudet
      $query = "UPDATE oikeu
                SET nimitys  = '$row[nimitys]',
                jarjestys    = '$jarj',
                jarjestys2   = '$jarj2',
                muutospvm    = now(),
                muuttaja     = '{$kukarow['kuka']}'
                WHERE yhtio  = '$uusiyhtio'
                and sovellus = '$row[sovellus]'
                and nimi     = '$row[nimi]'
                and alanimi  = '$row[alanimi]'";
      $updresult = pupe_query($query);
    }

    $edsovellus  = $row["sovellus"];
    $edjarjoikea = $row["jarjestys"];
    $ednimi      = $row["nimi"];
    $adalan      = $row["alanimi"];
  }
}

if ($kukarow['kuka'] == 'admin' and (isset($synkronoireferenssi) or isset($synkronoireferenssialapaivita)) and count($syncyhtiot) > 0) {

  $ch  = curl_init();
  curl_setopt($ch, CURLOPT_URL, "http://api.devlab.fi/referenssivalikot.sql");
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  $referenssit = curl_exec($ch);

  // Käännetään aliakset UTF-8 muotoon, jos Pupe on UTF-8:ssa
  if (PUPE_UNICODE) {
    // Tässä on "//NO_MB_OVERLOAD"-kommentti
    // jotta UTF8-konversio ei osu tähän riviin
    $referenssit = utf8_encode($referenssit); //NO_MB_OVERLOAD
  }

  $referenssit = explode("\n", trim($referenssit));

  // Eka rivi roskikseen
  array_shift($referenssit);

  $rows = array();

  foreach ($referenssit as $rivi) {

    // luetaan rivi tiedostosta..
    $rivi = explode("\t", trim($rivi));

    if ($sovellus == '' or strtoupper($sovellus) == strtoupper($rivi[0])) {

      $row = array();
      $row["sovellus"]      = $rivi[0];
      $row["nimi"]          = $rivi[1];
      $row["alanimi"]       = $rivi[2];
      $row["nimitys"]       = $rivi[3];
      $row["jarjestys"]     = (int) $rivi[4];
      $row["jarjestys2"]    = (int) $rivi[5];
      $row["hidden"]        = $rivi[6];
      $row["tunnus"]        = $rivi[7];
      $row["usermanualurl"] = $rivi[8];

      $rows[$row["sovellus"].$row["nimi"].$row["alanimi"]] = $row;
    }
  }

  $yht = "";
  foreach ($syncyhtiot as $yhtio) {
    $yht .= "'$yhtio',";
  }

  $yht = substr($yht, 0, -1);

  if ($sovellus != '') {
    $lisa = " and sovellus  = '$sovellus' ";
  }
  else {
    $lisa = "";
  }

  $query = "SELECT sovellus, nimi, alanimi, min(nimitys) nimitys, min(jarjestys)-1 jarjestys, min(jarjestys2) jarjestys2, max(hidden) hidden, max(usermanualurl) usermanualurl
            FROM oikeu
            WHERE yhtio in ($yht)
            and kuka    = ''
            $lisa
            GROUP BY sovellus, nimi, alanimi
            ORDER BY sovellus, jarjestys, jarjestys2";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    if (!array_key_exists($row["sovellus"].$row["nimi"].$row["alanimi"], $rows)) {
      $rows[$row["sovellus"].$row["nimi"].$row["alanimi"]] = $row;
    }
  }

  // Sortataan array niin että omat privaatit lisäykset tulee sopivaan rakoon referenssiin nähden
  $jarj0 = $jarj1 = $jarj2 = array();
  foreach ($rows as $key => $row) {
    $jarj0[$key] = $row['sovellus'];
    $jarj1[$key] = $row['jarjestys'];
    $jarj2[$key] = $row['jarjestys2'];
  }

  array_multisort($jarj0, SORT_ASC, $jarj1, SORT_ASC, $jarj2, SORT_ASC, $rows);

  $jarj  = 0;
  $jarj2 = 0;

  foreach ($rows as $row) {

    if ($edsovellus != $row["sovellus"]) {
      $jarj  = 0;
      $jarj2 = 0;
    }

    if ($row["jarjestys"] != $edjarjoikea or (($row["nimi"] != $ednimi or $row["alanimi"] != $edalan) and $row["jarjestys2"] == 0 )) {
      $jarj += 10;
      $jarj2 = 0;
    }

    if ($row["jarjestys2"] != 0 and $edjarjoikea != $row["jarjestys"]) {
      $jarj2 = 10;
    }

    if ($row["jarjestys2"] != 0 and $row["jarjestys"] == $edjarjoikea) {
      $jarj2 += 10;
    }

    foreach ($syncyhtiot as $yhtio) {
      $query = "SELECT *
                FROM oikeu
                WHERE yhtio  = '$yhtio'
                and kuka     = ''
                and sovellus = '$row[sovellus]'
                and nimi     = '$row[nimi]'
                and alanimi  = '$row[alanimi]'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0 and $row["sovellus"] != "") {
        $query = "INSERT into oikeu SET
                  kuka          = '',
                  profiili      = '',
                  sovellus      = '$row[sovellus]',
                  nimi          = '$row[nimi]',
                  alanimi       = '$row[alanimi]',
                  nimitys       = '$row[nimitys]',
                  jarjestys     = '$jarj',
                  jarjestys2    = '$jarj2',
                  hidden        = '$row[hidden]',
                  usermanualurl = '$row[usermanualurl]',
                  yhtio         = '$yhtio',
                  laatija       = '{$kukarow['kuka']}',
                  luontiaika    = now(),
                  muutospvm     = now(),
                  muuttaja      = '{$kukarow['kuka']}'";
        pupe_query($query);
        $insid = mysql_insert_id($GLOBALS["masterlink"]);

        if (isset($synkronoireferenssialapaivita)) {
          // Jos lisätään uusi väliin, niin loput pitää työntää vähän eteenpäin
          $query = "UPDATE oikeu
                    SET jarjestys  = jarjestys+10,
                    muutospvm      = now(),
                    muuttaja       = '{$kukarow['kuka']}'
                    WHERE yhtio    = '$yhtio'
                    and sovellus   = '$row[sovellus]'
                    and jarjestys  >= $jarj
                    and tunnus    != $insid";
          pupe_query($query);
        }
      }

      $jarjlisa = "";

      // päivitettän käyttäjien oikeudet
      if (!isset($synkronoireferenssialapaivita)) {
        $jarjlisa = " jarjestys     = '$jarj',
                      jarjestys2    = '$jarj2', ";
      }

      $query = "UPDATE oikeu
                SET nimitys   = '$row[nimitys]',
                {$jarjlisa}
                hidden        = '$row[hidden]',
                usermanualurl = '$row[usermanualurl]',
                muutospvm     = now(),
                muuttaja      = '{$kukarow['kuka']}'
                WHERE yhtio   = '$yhtio'
                and sovellus  = '$row[sovellus]'
                and nimi      = '$row[nimi]'
                and alanimi   = '$row[alanimi]'";
      pupe_query($query);
    }

    $edsovellus  = $row["sovellus"];
    $edjarjoikea = $row["jarjestys"];
    $ednimi      = $row["nimi"];
    $adalan      = $row["alanimi"];
  }
}

if ($tee == "PAIVITAJARJETYS") {
  foreach ($jarjestys as $tun => $jarj) {

    $query  = "SELECT *
               FROM oikeu
               WHERE tunnus = '$tun'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {

      $row = mysql_fetch_assoc($result);

      //päivitetään uudet menun tiedot kaikille käyttäjille
      $query = "UPDATE oikeu
                SET jarjestys  = '$jarj',
                jarjestys2     = '$jarjestys2[$tun]',
                muutospvm      = now(),
                muuttaja       = '{$kukarow['kuka']}'
                WHERE yhtio    = '$row[yhtio]'
                and sovellus   = '$row[sovellus]'
                and nimi       = '$row[nimi]'
                and alanimi    = '$row[alanimi]'
                and nimitys    = '$row[nimitys]'
                and jarjestys  = '$row[jarjestys]'
                and jarjestys2 = '$row[jarjestys2]'
                and hidden     = '$row[hidden]'";
      $result = pupe_query($query);
      $num1 = mysql_affected_rows();
    }
  }

  echo "<font class='message'>".t("Järjestykset päivitetty")."!<br><br></font>";


  $yhtiot = array();
  $yht = str_replace("'", "", $yht);
  $yht = explode(",", $yht);

  foreach ($yht as $yhtio) {
    $yhtiot[$yhtio] = $yhtio;
  }

  $tee = "";
}

if ($tee == "PAIVITA") {
  if ($kopioi == 'on') {
    $tunnus = '';
  }

  // Tarkistetaan ettei yritetä tehdä duplikaattia jo olemassaolevasta valikosta
  $t_query = "SELECT tunnus
              FROM oikeu
              WHERE yhtio  = '$yht'
              AND sovellus = '$sove'
              AND nimi     = '$nimi'
              AND alanimi  = '$alanimi'
              AND kuka     = ''
              AND tunnus  != '$tunnus'";
  $tarkistus = pupe_query($t_query);

  if (mysql_num_rows($tarkistus) > 0) {
    echo "<font class='error'> Ohjelma on jo lisätty valikoihin näillä tiedoilla - tarkista valikot ja syöttämäsi tiedot! </font> <br><br>";
  }
  else {
    if ($tunnus != '') {
      $query  = "SELECT *
                 FROM oikeu
                 WHERE tunnus='$tunnus'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 1) {

        $row = mysql_fetch_assoc($result);

        $yht = str_replace(",", "','", $yht);
        $yht = "'".$yht."'";

        //päivitetään uudet menun tiedot kaikille käyttäjille
        $query = "UPDATE oikeu SET
                  sovellus       = '$sove',
                  nimi           = '$nimi',
                  alanimi        = '$alanimi',
                  nimitys        = '$nimitys',
                  jarjestys      = '$jarjestys',
                  jarjestys2     = '$jarjestys2',
                  hidden         = '$hidden',
                  usermanualurl  = '$usermanualurl',
                  muutospvm      = now(),
                  muuttaja       = '{$kukarow['kuka']}'
                  WHERE
                  sovellus       = '$row[sovellus]'
                  and nimi       = '$row[nimi]'
                  and alanimi    = '$row[alanimi]'
                  and nimitys    = '$row[nimitys]'
                  and jarjestys  = '$row[jarjestys]'
                  and jarjestys2 = '$row[jarjestys2]'
                  and hidden     = '$row[hidden]'
                  and yhtio      in ($yht)";
        $result = pupe_query($query);
        $num1 = mysql_affected_rows();

        echo "<font class='message'>$num1 ".t("riviä päivitetty")."!<br></font>";
      }

      $yhtiot = array();
      $yht = str_replace("'", "", $yht);
      $yht = explode(",", $yht);

      foreach ($yht as $yhtio) {
        $yhtiot[$yhtio] = $yhtio;
      }
    }
    else {
      $yhtiot = array();
      $yht = str_replace("'", "", $yht);
      $yht = explode(",", $yht);

      foreach ($yht as $yhtio) {
        $yhtiot[$yhtio] = $yhtio;

        if ($yhtio != "REFERENSSI") {
          $query = "INSERT into oikeu SET
                    kuka          = '',
                    sovellus      = '$sove',
                    nimi          = '$nimi',
                    alanimi       = '$alanimi',
                    paivitys      = '',
                    lukittu       = '',
                    nimitys       = '$nimitys',
                    jarjestys     = '$jarjestys',
                    jarjestys2    = '$jarjestys2',
                    profiili      = '',
                    yhtio         = '$yhtio',
                    hidden        = '$hidden',
                    usermanualurl = '$usermanualurl',
                    laatija       = '{$kukarow['kuka']}',
                    luontiaika    = now(),
                    muutospvm     = now(),
                    muuttaja      = '{$kukarow['kuka']}'";
          $result = pupe_query($query);
          $num = mysql_affected_rows();

          echo "<font class='message'>$num ".t("riviä lisätty")."!<br></font>";
        }
      }
    }
  }

  $tee = "";
}

if ($tee == "MUUTA") {
  echo "<form method='post' action='menut.php'>";
  echo "<input type='hidden' name='tee' value='PAIVITA'>";
  echo "<input type='hidden' name='sovellus' value='$sovellus'>";
  echo "<input type='hidden' name='yht' value='$yht'>";
  echo "<input type='hidden' name='tunnus' value='$tunnus'>";

  if ($tunnus > 0) {
    $query  = "SELECT *
               from oikeu
               where tunnus = '$tunnus'";
    $result = pupe_query($query);
    $row = mysql_fetch_assoc($result);

    $sove          = $row['sovellus'];
    $nimi          = $row['nimi'];
    $alanimi       = $row['alanimi'];
    $nimitys       = $row['nimitys'];
    $jarjestys     = $row['jarjestys'];
    $jarjestys2    = $row['jarjestys2'];
    $hidden        = $row['hidden'];
    $usermanualurl = $row['usermanualurl'];
  }
  else {
    $sove          = "";
    $nimi          = "";
    $alanimi       = "";
    $nimitys       = "";
    $jarjestys     = "";
    $jarjestys2    = "";
    $hidden        = "";
    $usermanualurl = "";
  }

  echo "<table>
      <tr><th>".t("Muokataan yhtöille")."</th><td>".str_replace(",REFERENSSI" , "", $yht)."</td></tr>
      <tr><th>".t("Sovellus")."</th><td><input type='text' name='sove' value='$sove'></td></tr>
      <tr><th>".t("Nimi")."</th><td><input type='text' name='nimi' value='$nimi'></td></tr>
      <tr><th>".t("Alanimi")."</th><td><input type='text' name='alanimi' value='$alanimi'></td></tr>
      <tr><th>".t("Nimitys")."</th><td><input type='text' name='nimitys' value='$nimitys'></td></tr>
      <tr><th>".t("Järjestys")."</th><td><input type='text' name='jarjestys' value='$jarjestys'></td></tr>
      <tr><th>".t("Järjestys2")."</th><td><input type='text' name='jarjestys2' value='$jarjestys2'></td></tr>";

  if ($hidden != '') {
    $chk = "CHECKED";
  }
  else {
    $chk = "";
  }

  echo "  <tr><th>".t("Piilossa")."</th><td><input type='checkbox' name='hidden' value='H' $chk></td></tr>
      <tr><th>".t("Ohje")."</th><td><input type='text' name='usermanualurl' value='$usermanualurl'></td></tr>
      <tr><th>".t("Kopioi")."</th><td><input type='checkbox' name='kopioi'></td></tr>
      </table>
      <br>
      <input type='submit' value='".t("Päivitä")."'>
      </form>";

  if ($tunnus > 0) {
    echo "<form method='post' action='menut.php'>";
    echo "<input type='hidden' name='tee' value='POISTA'>";
    echo "<input type='hidden' name='sovellus' value='$sovellus'>";
    echo "<input type='hidden' name='yht' value='$yht'>";
    echo "<input type='hidden' name='tunnus' value='$tunnus'>";
    echo "<input type='submit' value='*".t("Poista")." $nimitys*'>";
    echo "</form>";
  }
}

if ($tee == 'POISTA') {
  // haetaan poistettavan rivin alkuperäiset tiedot
  $query  = "SELECT *
             from oikeu
             where tunnus='$tunnus'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {

    $row = mysql_fetch_assoc($result);

    $yarray = explode(",", $yht);

    $yht = str_replace(",", "','", $yht);
    $yht = "'".$yht."'";

    //päivitetään uudet menun tiedot kaikille käyttäjille
    $query = "DELETE from oikeu
              WHERE sovellus = '$row[sovellus]'
              and nimi       = '$row[nimi]'
              and alanimi    = '$row[alanimi]'
              and nimitys    = '$row[nimitys]'
              and jarjestys  = '$row[jarjestys]'
              and jarjestys2 = '$row[jarjestys2]'
              and yhtio      in ($yht)";
    $result = pupe_query($query);
    $num1 = mysql_affected_rows();

    foreach ($yarray as $yhtio) {
      // päiviteään kuka-tauluun mitkä käyttäjät on aktiivisia ja mitkä poistettuja
      paivita_aktiiviset_kayttajat("", $yhtio);
    }

    echo "<font class='message'>$num1 ".t("riviä poistettu")."!<br></font>";
  }

  $yhtiot = array();
  $yht = str_replace("'", "", $yht);
  $yht = explode(",", $yht);

  foreach ($yht as $yhtio) {
    $yhtiot[$yhtio] = $yhtio;
  }

  $tee = "";
}

if ($tee == "") {
  echo "<form method='post' action='menut.php'><table>";

  $query  = "SELECT distinct yhtio, nimi
             from yhtio
             where yhtio='$kukarow[yhtio]' or (konserni = '$yhtiorow[konserni]' and konserni != '')";
  $result = pupe_query($query);

  $sovyhtiot = "";

  while ($prow = mysql_fetch_assoc($result)) {

    if ($yhtiot[$prow["yhtio"]] != "") {
      $chk = "CHECKED";
    }
    else {
      $chk = "";
    }

    echo "<tr><th>".t("Näytä yhtiö").":</th><td><input type='checkbox' name='yhtiot[$prow[yhtio]]' value='$prow[yhtio]' $chk onclick='submit();'> $prow[nimi]</td></tr>";
    $sovyhtiot .= "'$prow[yhtio]',";
  }

  if ($yhtiot["REFERENSSI"] != "") {
    $chk = "CHECKED";
  }
  else {
    $chk = "";
  }

  echo "<tr><th>".t("Näytä referenssivalikot").":</th><td><input type='checkbox' name='yhtiot[REFERENSSI]' value='REFERENSSI' $chk onclick='submit();'></td></tr>";

  $sovyhtiot = substr($sovyhtiot, 0, -1);

  $query = "SELECT distinct sovellus
            FROM oikeu
            where yhtio in ($sovyhtiot)
            and kuka=''
            order by sovellus";
  $result = pupe_query($query);

  echo "<tr><th>".t("Valitse sovellus").":</th><td><select name='sovellus' onchange='submit();'>";

  echo "<option value=''>".t("Näytä kaikki").":</option>";

  while ($orow = mysql_fetch_assoc($result)) {
    $sel = '';
    if ($sovellus == $orow["sovellus"]) {
      $sel = "SELECTED";
    }

    echo "<option value='$orow[sovellus]' $sel>".t($orow["sovellus"])."</option>";
  }
  echo "</select></td></tr>";

  if (count($yhtiot) > 1) {
    echo "<tr><th>".t("Synkronoi").":</th><td><input type='submit' name='synkronoi' value='".t("Synkronoi")."'></td></tr>";
  }

  if ($kukarow['kuka'] == 'admin') {
    echo "<tr><th>".t("Synkronoi referenssiin").":</th><td><input type='submit' name='synkronoireferenssi' value='".t("Synkronoi")."'></td></tr>";
    echo "<tr><th>".t("Synkronoi referenssiin")." ".t("älä päivitä järjestyksiä").":</th><td><input type='submit' name='synkronoireferenssialapaivita' value='".t("Synkronoi")."'></td></tr>";
  }

  echo "</form>";


  if (count($yhtiot) > 0) {
    $yht = "";
    foreach ($yhtiot as $yhtio) {
      $yht .= "$yhtio,";
    }
    $yht = substr($yht, 0, -1);

    echo "<form method='post' action='menut.php'>";
    echo "<input type='hidden' name='tee' value='MUUTA'>";
    echo "<input type='hidden' name='sovellus' value='$sovellus'>";
    echo "<input type='hidden' name='sove' value='$sovellus'>";
    echo "<input type='hidden' name='yht' value='$yht'>";
    echo "<tr><th>".t("Uusi valikko").":</th><td><input type='submit' value='".t("Lisää")."'></td></tr>";
    echo "</form>";
  }

  echo "</table><br>";
  echo "<table><tr>";

  if (count($yhtiot) > 0) {

    $dirikka = getcwd();

    foreach ($yhtiot as $yhtio) {
      echo "<td class='back ptop'>";

      $rows = array();

      if ($yhtio == "REFERENSSI") {
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://api.devlab.fi/referenssivalikot.sql");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $referenssit = curl_exec($ch);

        // Käännetään aliakset UTF-8 muotoon, jos Pupe on UTF-8:ssa
        if (PUPE_UNICODE) {
          // Tässä on "//NO_MB_OVERLOAD"-kommentti
          // jotta UTF8-konversio ei osu tähän riviin
          $referenssit = utf8_encode($referenssit); //NO_MB_OVERLOAD
        }

        $referenssit = explode("\n", trim($referenssit));

        // Eka rivi roskikseen
        array_shift($referenssit);

        $rows = array();

        foreach ($referenssit as $rivi) {

          // luetaan rivi tiedostosta..
          $rivi = explode("\t", trim($rivi));

          if ($sovellus == '' or strtoupper($sovellus) == strtoupper($rivi[0])) {
            $rows[$lask]["sovellus"]      = $rivi[0];
            $rows[$lask]["nimi"]          = $rivi[1];
            $rows[$lask]["alanimi"]       = $rivi[2];
            $rows[$lask]["nimitys"]       = $rivi[3];
            $rows[$lask]["jarjestys"]     = (int) $rivi[4];
            $rows[$lask]["jarjestys2"]    = (int) $rivi[5];
            $rows[$lask]["hidden"]        = $rivi[6];
            $rows[$lask]["tunnus"]        = $rivi[7];
            $rows[$lask]["usermanualurl"] = $rivi[8];
          }

          $lask++;
        }
      }
      else {

        echo "<form method='post' action='menut.php'>";
        echo "<input type='hidden' name='tee' value='PAIVITAJARJETYS'>";
        echo "<input type='hidden' name='sovellus' value='$sovellus'>";
        echo "<input type='hidden' name='yht' value='$yht'>";

        $query  = "SELECT sovellus, nimi, alanimi, nimitys, jarjestys, jarjestys2, hidden, usermanualurl, tunnus
                   from oikeu
                   where kuka = ''
                   and yhtio  = '$yhtio'";

        if ($sovellus != '') {
          $query .= " and sovellus='$sovellus'";
        }

        $query .= " order by sovellus, jarjestys, jarjestys2";
        $result = pupe_query($query);

        $lask = 0;

        while ($prow = mysql_fetch_assoc($result)) {
          $rows[$lask]["sovellus"]      = $prow["sovellus"];
          $rows[$lask]["nimi"]          = $prow["nimi"];
          $rows[$lask]["alanimi"]       = $prow["alanimi"];
          $rows[$lask]["nimitys"]       = $prow["nimitys"];
          $rows[$lask]["jarjestys"]     = $prow["jarjestys"];
          $rows[$lask]["jarjestys2"]    = $prow["jarjestys2"];
          $rows[$lask]["hidden"]        = $prow["hidden"];
          $rows[$lask]["tunnus"]        = $prow["tunnus"];
          $rows[$lask]["usermanualurl"] = $prow["usermanualurl"];

          $lask++;
        }
      }

      echo "<table>";

      $vsove = "";

      foreach ($rows as $row) {
        $tunnus        = $row['tunnus'];
        $sove          = $row['sovellus'];
        $nimi          = $row['nimi'];
        $alanimi       = $row['alanimi'];
        $nimitys       = $row['nimitys'];
        $jarjestys     = $row['jarjestys'];
        $jarjestys2    = $row['jarjestys2'];
        $hidden        = $row['hidden'];
        $usermanualurl = $row['usermanualurl'];

        if ($vsove != $sove) {
          echo "<tr><td class='back' colspan='4'><br></td></tr>\n";
          echo "<tr>
              <th colspan='2' nowrap>".t("Sovellus").": ".t($sove)." $yhtio</th>
              <th nowrap>".t("Alanimi")."</th>
              <th nowrap>".t("Nimitys")."</th>
              <th nowrap>".t("J1")."</th>
              <th nowrap>".t("J2")."</th>
              <th nowrap>".t("Piilossa")."</th>
              <th nowrap>".t("Ohje")."</th>
            </tr>\n";
        }

        echo "<tr>";

        if ($jarjestys2!='0') {
          echo "<td class='back' nowrap>--></td><td>";
        }
        else {
          echo "<td colspan='2' nowrap>";
        }

        if (!file_exists($dirikka."/".$nimi)) {
          $mordor1 = "<font class='error'>";
          $mordor2 = "</font>";
        }
        else {
          $mordor1 = $mordor2 = "";
        }

        if ($yhtio == "REFERENSSI") {
          echo "$mordor1$nimi$mordor2</td>";
          echo "<td nowrap>$alanimi</td>";
          echo "<td nowrap>".t($nimitys)."</td>";
          echo "<td nowrap><input type='text' size='4' value='$jarjestys' DISABLED></td>";
          echo "<td nowrap><input type='text' size='4' value='$jarjestys2' DISABLED></td>";
          echo "<td nowrap>$hidden</td>";
          echo "<td nowrap>$usermanualurl</td>";
        }
        else {
          echo "<a href='$PHP_SELF?tee=MUUTA&tunnus=$tunnus&yht=$yht&sovellus=$sovellus'>$mordor1$nimi$mordor2</a></td>";
          echo "<td nowrap>$alanimi</td>";
          echo "<td nowrap>".t($nimitys)."</td>";
          echo "<td nowrap><input type='text' size='4' name='jarjestys[$tunnus]' value='$jarjestys'></td>";
          echo "<td nowrap><input type='text' size='4' name='jarjestys2[$tunnus]' value='$jarjestys2'></td>";
          echo "<td nowrap>$hidden</td>";
          echo "<td nowrap>$usermanualurl</td>";
        }

        echo "</tr>\n";

        $vsove = $sove;
      }

      echo "</table>";

      if ($yhtio != "REFERENSSI") {
        echo "<input type='submit' value='".t("Päivitä järjestykset")."'>\n";
        echo "</form>";
      }

      echo "</td>";
    }
  }
  echo "</tr></table>";
}

require "inc/footer.inc";
