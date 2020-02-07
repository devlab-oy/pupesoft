<?php

require "inc/parametrit.inc";

//$debug = 1;

echo "<font class='head'>", t("Yhdistä autodata yhteensopivuuksiin"), "</font><hr>";

if ($limit == '') {
  $limit = 0;
}

if ($tee == '') {
  echo "<table>";
  echo "<tr>";
  echo "<th>", t('Maa'), "</th>";
  echo "</tr>";

  echo "<tr><td>";
  echo "<form method='post'>";
  echo "<input type='hidden' name='mitka' value='uudet'>";
  echo "<input type='hidden' name='tee' value='valitse'>";
  echo "<input type='submit' value='", t('Liitä uusia'), "'>";
  echo "</form></td>";

  echo "<tr><td>";
  echo "<form method='post'>";
  echo "<input type='hidden' name='mitka' value='hylatyt'>";
  echo "<input type='hidden' name='tee' value='valitse'>";
  echo "<input type='submit' value='", t('Hylätyt'), "'>";
  echo "</form></td>";

  echo "<tr><td>";
  echo "<form method='post'>";
  echo "<input type='hidden' name='mitka' value='korjaa'>";
  echo "<input type='hidden' name='tee' value='valitse'>";
  echo "<input type='submit' value='", t('Korjaa liitettyjä'), "'>";
  echo "</form></td>";

  echo "</tr>";
  echo "</table>";
  exit;
}

// Näytetään autoidsyöttökenttä
if ($mitka == 'korjaa') {
  echo "<table>";
  echo "<tr>";
  echo "<th colspan='3'>Anna autoid";

  echo "</th><form method='post'>";
  echo "<input type='hidden' name='mitka' value='korjaa'>";
  echo "<input type='hidden' name='tee' value='go'>";

  echo "<td class='back'><input type='text' name = 'autoid_haku' value='$autoid_haku'></td>";

  echo "<td class='back'><input type='submit' value='Hae'></td>";
  echo "</form>";

  echo "</tr>";
  echo "</table>";
  echo "<br>";
}

if ($tee == "hyvaksy" and count($idt) > 0) {

  //nollataan laskureita
  $autodataid = 0;
  $miinukset = 0;
  $plussat = 0;

  //loopataan eka kerran ja katotaan onko käyttäjä kämmännyt ja valinnut sekä malleja että ei mallia
  foreach ($idt as $autodataid) {
    //jos on negatiivinen tarkottaa että ei ole löytynyt oikeaa
    if ($autodataid < 0) {
      $miinukset++;
    }
    else {
      $plussat++;
    }
  }

  // EI käytetä slavea
  $useslave = 0;
  // otetaan tietokanta connect uudestaan
  require "inc/connect.inc";

  if ($miinukset+$plussat > 0) {

    $autoid = mysql_real_escape_string($autoid);

    $query = "SELECT *
              FROM yhteensopivuus_auto
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  = '$autoid'";
    $result = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($result) > 0 and $mitka == 'uudet') {
      while ($autodatarow = mysql_fetch_array($result)) {
        $query = "SELECT *
                  FROM yhteensopivuus_autodata
                  WHERE yhtio = '$kukarow[yhtio]'
                  and autoid  = '$autoid'";
        $ekahaku = mysql_query($query) or pupe_error($query);

        if (mysql_num_rows($ekahaku) > 0) {
          $tee = 'go';
        }
      }

      if ($tee == 'go') {
        echo "<br><font class='error'>", t('Joku kerkesi jo liittää tämän tiedon'), "!</font>";
      }
    }
  }
  else {
    $tee = '';
  }

  if ($tee == "hyvaksy" and (($mitka == 'korjaa' and $miinukset+$plussat > 0) or ($mitka == 'hylatyt' and $miinukset == 0 and $plussat > 0))) {

    // jos korjaillaan vanhoja, haetaan ja dellataan kaikki olemassa olevat liitokset samoilla tiedoilla joilla groupattiin aikasemmin

    $query = "DELETE
              FROM yhteensopivuus_autodata
              WHERE yhtio = '$kukarow[yhtio]'
              AND autoid  = '$autoid'";
    $dellaus = mysql_query($query) or pupe_error($query);

    foreach ($idt as $autodataid) {

      if ($autodataid > -1) {
        $query = "INSERT INTO yhteensopivuus_autodata
                  set autodataid = '$autodataid',
                  yhtio  = '$kukarow[yhtio]',
                  autoid = '$autoid'";
        $update = mysql_query($query) or pupe_error($query);
      }
    }
  }
  //on löytynyt lisättävää
  elseif ($tee == "hyvaksy" and $plussat > 0 and $mitka == 'uudet') {

    //loopataan uudestaan ja tehdään tarvittavat lisäykset
    foreach ($idt as $autodataid) {

      if ($autodataid > -1) {
        //lisätään kaikki valitut sopivuudet.
        $query = "INSERT into yhteensopivuus_autodata
                  set yhtio = '$kukarow[yhtio]',
                  autodataid = '$autodataid',
                  autoid     = '$autoid'";
        $insertti = mysql_query($query) or pupe_error($query);
      }
    }
  }
  //jos pelkästään miinus niin lisätään tämä ohitettuna
  elseif ($tee == "hyvaksy" and $miinukset > 0  and $mitka != 'hylatyt') {

    //loopataan uudestaan ja tehdään tarvittavat lisäykset
    foreach ($idt as $autodataid) {
      if ($autodataid < 0) {
        //lisätään yhteensopivuus_auto kantaan
        /*if ($autodataid == '-2') {
            //Merkataan S niin kuin seurantaan, jos vaikka halutaan lisätä myöhennim.
            $query = "update autodata set status = 'S' where yhtio = '$kukarow[yhtio]' and autodataid = '$autoid'";
            $update = mysql_query($query) or pupe_error($query);
          }*/

        //hylätään
        if ($autodataid == '-1') {
          //merkataan P niin kuin poistettu.
          $query = "INSERT into yhteensopivuus_autodata
                    set yhtio = '$kukarow[yhtio]', autodataid = 'HYLATTY', autoid = '$autoid'";
          $update = mysql_query($query) or pupe_error($query);
        }
      }
    }
  }

  if ($tee == 'hyvaksy') {
    $tee = 'go';
    $autoid = '';
    $limit++;
  }

  // käytetään taas slavea. Ei käytetä enää, koska aiheuttaa ongelmia.
  $useslave = 0;
  // otetaan tietokanta connect uudestaan
  require "inc/connect.inc";
}
//ei olla ruksattu ytään autoa
elseif ($tee == "hyvaksy") {
  echo "<br><font class='error'>", t('Et tainnut valita mitään'), "!</font>";
  $tee = '';
}

if ($tee == 'hyvaksy' and $mitka == 'korjaa') {
  $tee == "";
  $mitka == "";
}

if ($tee == 'valitse' and $mitka != 'korjaa') {
  if ($valmerkki == '') {

    $query =  "SELECT merkki, count(distinct yhteensopivuus_auto.tunnus) laskin, count(yhteensopivuus_rekisteri.rekno) rekkareita
               FROM yhteensopivuus_auto
               LEFT JOIN yhteensopivuus_rekisteri ON yhteensopivuus_auto.yhtio = yhteensopivuus_rekisteri.yhtio and yhteensopivuus_auto.tunnus = yhteensopivuus_rekisteri.autoid
               WHERE yhteensopivuus_auto.yhtio = '$kukarow[yhtio]'
               GROUP BY merkki
               ORDER BY rekkareita DESC, laskin DESC";
    $result = mysql_query($query) or pupe_error($query);

    if ($debug == 1) {
      echo "$query<br>";
    }

    echo "<table>";
    echo "<tr>";

    if ($mitka == 'korjaa') {
      echo "<th>", t("Merkki (jäljellä / liitettyjä / yhteensä / yhd.rek)"), "</th>";
      $selectlisa = ", count(if(yhteensopivuus_autodata.autodataid != 'HYLATTY', 1, 0)) liitetty";

    }
    elseif ($mitka == 'hylatyt') {
      echo "<th>", t("Merkki (jäljellä / hylätyt / yhteensä / yhd.rek)"), "</th>";

      $selectlisa = ", count(if(yhteensopivuus_autodata.autodataid = 'HYLATTY', 1, 0)) hylatyt ";
    }
    else {
      echo "<th>", t("Merkki (jäljellä / yhteensä / yhd.rek)"), "</th>";
      $selectlisa = '';

    }

    echo "<td class='back'></td></tr>";

    while ($merkkirow = mysql_fetch_array($result)) {
      $jaljella = 0;

      $query =  "SELECT count(distinct yhteensopivuus_autodata.autoid) laskin $selectlisa
                 FROM yhteensopivuus_auto
                 JOIN yhteensopivuus_autodata ON (yhteensopivuus_autodata.yhtio = yhteensopivuus_auto.yhtio and yhteensopivuus_autodata.autoid = yhteensopivuus_auto.tunnus)
                 WHERE yhteensopivuus_auto.yhtio = '$kukarow[yhtio]'
                 and yhteensopivuus_auto.merkki  = '$merkkirow[merkki]'";
      $dataresult = mysql_query($query) or pupe_error($query);
      $datarow = mysql_fetch_array($dataresult);


      $jaljella = $merkkirow['laskin'] - $datarow['laskin'];

      echo "<form method='post'>";
      echo "<input type='hidden' name='mitka' value='$mitka'>";
      echo "<input type='hidden' name='tee' value='go'>";
      echo "<input type='hidden' name='valmerkki' value='$merkkirow[merkki]'>";
      echo "<tr>";
      echo "<td>$merkkirow[merkki] ($jaljella / ";
      if ($mitka == 'hylatyt') {
        echo "$datarow[hylatyt] / ";
      }
      elseif ($mitka == 'korjaa') {
        echo "$datarow[liitetty] / ";
      }
      echo "$merkkirow[laskin] / $merkkirow[rekkareita])</td>";
      echo "<td class='back'><input type='submit' value='", t('Valitse'), "'></td>";
      echo "</tr>";
      echo "</form>";
    }
    echo "</tr>";
    echo "</table>";
  }
}

if ($autoid == '' and $autoid_haku != '') {
  $autoid = $autoid_haku;
}

if ($autoid == '') {
  if ($tee == 'go' and $mitka == 'uudet') {
    if ($debug == 1) {
      //$debuglisa = " and autodata.alkuvuosi >= '1990' ";
    }

    // käydään kaikki autot läpi, jota ei ole linkattu yhteensopivuustauluun
    $query =  "SELECT yhteensopivuus_auto.*, count(yhteensopivuus_rekisteri.rekno) rekkareita, yhteensopivuus_autodata.tunnus joini
               FROM yhteensopivuus_auto
               LEFT JOIN yhteensopivuus_autodata ON yhteensopivuus_autodata.yhtio = yhteensopivuus_auto.yhtio and yhteensopivuus_autodata.autoid = yhteensopivuus_auto.tunnus
               LEFT JOIN yhteensopivuus_rekisteri ON yhteensopivuus_auto.yhtio = yhteensopivuus_rekisteri.yhtio and yhteensopivuus_auto.tunnus = yhteensopivuus_rekisteri.autoid
               WHERE yhteensopivuus_auto.yhtio='$kukarow[yhtio]'
               and yhteensopivuus_auto.merkki = '$valmerkki'
               $debuglisa
               GROUP BY yhteensopivuus_auto.tunnus
               HAVING joini IS NULL
               ORDER BY rekkareita desc, merkki,malli,mallitarkenne,moottorityyppi
               LIMIT $limit, 1";
    $result = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($result) > 0) {
      $rekrivi = mysql_fetch_array($result);
    }
    else {
      $tee == "";
      $mitka == "";
      echo "<br><font class='message'>", t('Ei löytynyt mitään'), "!</font>";
      exit;
    }

    if ($debug == 1) {
      echo "$query<br>";
    }
  }
  elseif ($tee == 'go' and $mitka == 'hylatyt') {
    // count(yhteensopivuus_rekisteri.rekno) rekkareita
    // LEFT JOIN yhteensopivuus_rekisteri ON (yhteensopivuus_auto.yhtio = yhteensopivuus_rekisteri.yhtio and yhteensopivuus_auto.tunnus = yhteensopivuus_rekisteri.autoid)
    $query =  "SELECT yhteensopivuus_auto.*, count(yhteensopivuus_rekisteri.rekno) rekkareita
               FROM yhteensopivuus_auto
               JOIN yhteensopivuus_autodata ON (yhteensopivuus_autodata.yhtio = yhteensopivuus_auto.yhtio and yhteensopivuus_autodata.autoid = yhteensopivuus_auto.tunnus and yhteensopivuus_autodata.autodataid = 'HYLATTY')
               LEFT JOIN yhteensopivuus_rekisteri ON (yhteensopivuus_auto.yhtio = yhteensopivuus_rekisteri.yhtio and yhteensopivuus_auto.tunnus = yhteensopivuus_rekisteri.autoid)
               WHERE yhteensopivuus_auto.yhtio='$kukarow[yhtio]'
               and yhteensopivuus_auto.merkki = '$valmerkki'
               GROUP BY yhteensopivuus_auto.tunnus
               ORDER BY rekkareita DESC, merkki,malli,mallitarkenne,moottorityyppi
               LIMIT $limit, 1";
    $result = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($result) > 0) {
      $rekrivi = mysql_fetch_array($result);
    }
    else {
      $tee == "";
      $mitka == "";
      echo "<br><font class='message'>", t('Ei löytynyt mitään'), "!</font>";
      exit;
    }

  }
  elseif ($tee == 'go' and $mitka == 'korjaa') {
    $query =  "SELECT yhteensopivuus_auto.*, count(yhteensopivuus_rekisteri.rekno) rekkareita
               FROM yhteensopivuus_auto
               JOIN yhteensopivuus_autodata ON (yhteensopivuus_autodata.yhtio = yhteensopivuus_auto.yhtio and yhteensopivuus_autodata.autoid = yhteensopivuus_auto.tunnus and yhteensopivuus_autodata.autodataid != 'HYLATTY')
               LEFT JOIN yhteensopivuus_rekisteri ON (yhteensopivuus_auto.yhtio = yhteensopivuus_rekisteri.yhtio and yhteensopivuus_auto.tunnus = yhteensopivuus_rekisteri.autoid)
               WHERE yhteensopivuus_auto.yhtio='$kukarow[yhtio]'
               and yhteensopivuus_auto.merkki = '$valmerkki'
               GROUP BY yhteensopivuus_auto.tunnus
               ORDER BY rekkareita DESC, merkki,malli,mallitarkenne,moottorityyppi
               LIMIT $limit, 1";
    $result = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($result) > 0) {
      $rekrivi = mysql_fetch_array($result);
    }
    else {
      $tee == "";
      $mitka == "";
      echo "<br><font class='message'>", t('Ei löytynyt mitään'), "!</font>";
      exit;
    }
  }
}
else {
  // käydään kaikki autot läpi, jota ei ole linkattu yhteensopivuustauluun
  $query =  "SELECT yhteensopivuus_auto.*
             FROM yhteensopivuus_auto
             WHERE yhteensopivuus_auto.yhtio='$kukarow[yhtio]'
             and yhteensopivuus_auto.tunnus = '$autoid'
             LIMIT 1";
  $result = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($result) > 0) {
    $rekrivi = mysql_fetch_array($result);
  }
  else {
    echo "<br><font class='message'>", t('Ei löytynyt mitään'), "! ($autoid)</font>";
    exit;
  }

  if ($debug == 1) {
    echo "$query<br>";
  }
}

// tehdään konversioita
if ($rekrivi['merkki'] != '' and $tee == 'go') {

  $rekmalli = " = '$rekrivi[malli]' ";
  $mallilike = " like '%$rekrivi[malli]%' ";

  if ($rekrivi['cc'] != '') {
    $rektila = sprintf("%.1f", round($rekrivi["cc"]/1000, 1));
  }
  else {
    $rektila = "0";
  }

  if ($rekrivi['teho_kw'] == 0) {
    $rekrivi['teho_kw'] = '';
  }

  if ($rekrivi['teho_kw'] != '') {
    $rekteho = "like '".$rekrivi["teho_kw"]." (%'";
  }
  else {
    $rekteho = "";
  }

  if (strpos($rekteho, '/') !== FALSE) {
    $rekteho = '';
  }

  if ($rekrivi["polttoaine"] == 'B' and !isset($bensa) and $bensa == '') {
    $bensa = "P";
  }
  elseif ($rekrivi["polttoaine"] == 'D' and !isset($bensa) and $bensa == '') {
    $bensa = "D";
  }
  elseif (!isset($bensa)) {
    $bensa = "";
  }

  if (isset($bensa) and isset($rekrivi['polttoaine']) and $bensa != $rekrivi['polttoaine'] and $rekrivi['polttoaine'] == 'D') {
    $bensa = $rekrivi['polttoaine'];
  }
  elseif (isset($bensa) and isset($rekrivi['polttoaine']) and $bensa != $rekrivi['polttoaine'] and $rekrivi['polttoaine'] == 'B') {
    $bensa = 'P';
  }

  if ($rekrivi["moottorityyppi"] != '') {
    if (strpos($rekrivi['moottorityyppi'], '/') !== FALSE) {
      $rekmtyyppi = "moottorityyppi in ('".str_replace('/', '\',\'', $rekrivi['moottorityyppi'])."','".$rekrivi['moottorityyppi']."') and";
    }
    else {
      $rekmtyyppi = "moottorityyppi = '".$rekrivi['moottorityyppi']."' and";
    }
  }
  else {
    $rekmtyyppi = '';
  }
}
elseif ($tee == 'go') {
  echo "<br><font class='error'>", t('Merkki oli tyhjä'), "!!!!!</font>";
  $tee = "";
}

for ($i=1; $i <= 2; $i++) {

  if ($i == 1) {
    $rekmerkki = " = '$rekrivi[merkki]' ";
  }
  else {
    $rekmerkki = " like '%$rekrivi[merkki]%' ";
  }

  if ($tee == 'go' and mysql_num_rows($result) > 0 and $rekrivi['merkki'] != '') {

    $tarvitaanmerkki = 0;
    $merkkieiosunu = 0;

    //kaikissa autoissa ei ole tehoa
    if ($rekteho != '') {
      $query =  "select *
              from autodata
              where yhtio='$kukarow[yhtio]' and
              merkki $rekmerkki and
              kayttovoima = '$bensa' and
              replace(moottoritil,',','.') = $rektila and
              teho $rekteho and
              malli $rekmalli
              order by malli,mallitark,vali,moottoritil,moottorityyppi";
      $rekres = mysql_query($query) or pupe_error($query);
      //moottorikoko or mallitarkenne mallilike
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                replace(moottoritil,',','.') = $rektila and
                teho $rekteho and
                malli $mallilike
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //moottorikoko or mallitarkenne
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                (replace(moottoritil,',','.') = $rektila or
                replace(moottoritil,',','.') = '$rekrivi[mallitarkenne]') and
                teho $rekteho and
                malli $rekmalli
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //moottorikoko or mallitarkenne mallilike
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                (replace(moottoritil,',','.') = $rektila or
                replace(moottoritil,',','.') = '$rekrivi[mallitarkenne]') and
                teho $rekteho and
                malli $mallilike
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //ilman moottorikokoa
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                teho $rekteho and
                malli $rekmalli
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //ilman moottorikokoa mallilike
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                teho $rekteho and
                malli $mallilike
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //ilman moottorikokoa ja tehoa
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                malli $rekmalli
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //ilman moottorikokoa ja tehoa mallilike
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                malli $mallilike
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //ilaman mallia, mutta moottorikoko takas ja lisäks $rekmtyyppi
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                $rekmtyyppi
                (replace(moottoritil,',','.') = $rektila or
                replace(moottoritil,',','.') = '$rekrivi[mallitarkenne]')
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);

      }
      //ilaman mallia, ilman moottorikokoa ja lisäks $rekmtyyppi
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                $rekmtyyppi
                kayttovoima = '$bensa'
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //ilaman mallia, mutta moottorikoko takas
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                (replace(moottoritil,',','.') = $rektila or
                replace(moottoritil,',','.') = '$rekrivi[mallitarkenne]')
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //tarvitaan merkkihaku
      if (mysql_num_rows($rekres) == 0) {
        $tarvitaanmerkki = 1;
      }
    }
    else {
      $query =  "select *
              from autodata
              where yhtio='$kukarow[yhtio]' and
              merkki $rekmerkki and
              kayttovoima = '$bensa' and
              replace(moottoritil,',','.') = $rektila and
              malli $rekmalli
              order by malli,mallitark,vali,moottoritil,moottorityyppi";
      $rekres = mysql_query($query) or pupe_error($query);
      //moottorikoko or mallitarkenne mallilike
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                replace(moottoritil,',','.') = $rektila and
                malli $mallilike
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //moottorikoko or mallitarkenne
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                (replace(moottoritil,',','.') = $rektila or
                replace(moottoritil,',','.') = '$rekrivi[mallitarkenne]') and
                malli $rekmalli
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //moottorikoko or mallitarkenne mallilike
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                (replace(moottoritil,',','.') = $rektila or
                replace(moottoritil,',','.') = '$rekrivi[mallitarkenne]') and
                malli $mallilike
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //ilman moottorikokoa
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                malli $rekmalli
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //ilman moottorikokoa mallilike
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                malli $mallilike
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //ilaman mallia, mutta moottorikoko takas ja lisäks $rekmtyyppi
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                $rekmtyyppi
                (replace(moottoritil,',','.') = $rektila or
                replace(moottoritil,',','.') = '$rekrivi[mallitarkenne]')
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);

      }
      //ilaman mallia, ilman moottorikokoa ja lisäks $rekmtyyppi
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                $rekmtyyppi
                kayttovoima = '$bensa'
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //ilaman mallia, mutta moottorikoko takas
      if (mysql_num_rows($rekres) == 0) {
        $query =  "select *
                from autodata
                where yhtio='$kukarow[yhtio]' and
                merkki $rekmerkki and
                kayttovoima = '$bensa' and
                (replace(moottoritil,',','.') = $rektila or
                replace(moottoritil,',','.') = '$rekrivi[mallitarkenne]')
                order by malli,mallitark,vali,moottoritil,moottorityyppi";
        $rekres = mysql_query($query) or pupe_error($query);
      }
      //tarvitaan mekkihakua
      if (mysql_num_rows($rekres) == 0) {
        $tarvitaanmerkki = 1;
      }
    }
  }
  elseif ($tee == 'go' and mysql_num_rows($result) > 0 and $valmerkki != '' and $rekrivi['merkki'] == '') {
    echo "<br><font class='error'>Ei kohdistamattomia autoja $rekrivi[merkki]!</font>";
    $tee = '';
  }

  // vähennetään kritereitä pelkkä merkki
  if ($tarvitaanmerkki > 0 and $rekrivi['merkki'] != '') {
    if ($bensa != '') {
      $query = "select * from autodata
            where yhtio='$kukarow[yhtio]' and
            merkki $rekmerkki and
            kayttovoima = '$bensa'
            order by malli,mallitark,vali,moottoritil,moottorityyppi";
      $rekres = mysql_query($query) or pupe_error($query);
    }
    else {
      // vähennetään kritereitä pelkkä merkki
      $query = "select * from autodata
            where yhtio='$kukarow[yhtio]' and
            merkki $rekmerkki
            order by malli,mallitark,vali,moottoritil,moottorityyppi";
      $rekres = mysql_query($query) or pupe_error($query);
    }

    if (mysql_num_rows($rekres) == 0) {
      $merkkieiosunu = 1;
    }

  }

  if ($merkkieiosunu == 0) {
    $i++;
  }

  if ($i == 2 and $tee != 'valitse') {
    $tee = 'go';
  }
}

if ($tee == 'go') {

  if ($debug == 1) {
    echo "<br>$query<br>";
  }

  if ($rekrivi['rekkareita'] != '') {
    $rekkareita = $rekrivi['rekkareita'];
  }

  echo "<font class='message'>Rekisteröity $rekkareita kappaletta<br>";
  echo "<table>";
  echo "<tr>";
  echo "<td class='back' valign='top'><table>";
  echo "<tr><th>autoid</th><td nowrap>$rekrivi[tunnus]</td></tr>";
  echo "<tr><th>merkki</th><td nowrap>$rekrivi[merkki]</td></tr>";
  echo "<tr><th>malli</th><td nowrap>$rekrivi[malli]</td></tr>";
  echo "<tr><th>mallitarkenne</th><td nowrap>$rekrivi[mallitarkenne]</td></tr>";
  echo "<tr><th>korityyppi</th><td nowrap>$rekrivi[korityyppi]</td></tr>";
  echo "<tr><th>lisätiedot</th><td nowrap>$rekrivi[lisatiedot]</td></tr>";
  echo "</table></td>";

  echo "<td class='back' valign='top'><table>";
  echo "<tr><th>vuosimalli</th><td nowrap>$rekrivi[alkukk]/$rekrivi[alkuvuosi] - $rekrivi[loppukk]/$rekrivi[loppuvuosi]</td></tr>";
  echo "<tr><th>polttoaine</th><td nowrap>$rekrivi[polttoaine]</td></tr>";
  echo "<tr><th>moottoritil</th><td nowrap>$rekrivi[cc]</td></tr>";
  echo "<tr><th>moottorityyppi</th><td nowrap>$rekrivi[moottorityyppi]</td></tr>";
  echo "<tr><th>teho</th><td nowrap>$rekrivi[teho_kw] ($rekrivi[teho_hv])</td></tr>";
  echo "<tr><th>syl.määrä/halk.</th><td nowrap>$rekrivi[sylinterimaara]/$rekrivi[sylinterinhalkaisija]</td></tr>";
  echo "</table></td>";

  $autodata = "";

  $query = "SELECT autodataid from yhteensopivuus_autodata where yhtio = '$kukarow[yhtio]' and autoid = '$rekrivi[tunnus]'";
  $whileres = mysql_query($query) or pupe_error($query);
  if (mysql_num_rows($whileres) > 0) {
    while ($while = mysql_fetch_array($whileres)) {
      $autodata .= $while["autodataid"]."<br>";
    }
  }

  if ($autodata != '') {
    $autodata = substr($autodata, 0, -4);
  }
  else {
    $autodata = " <br> ";
  }

  echo "<td class='back' valign='top'><table>";
  echo "<tr><th>Autodataid</th></tr>";
  echo "<tr rowspan='5'><td nowrap>$autodata</td></tr>";
  echo "</table></td>";

  echo "</tr>";

  echo "</table>";

  echo "<br>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee'           value='hyvaksy'>";
  echo "<input type='hidden' name='mitka'         value='$mitka'>";
  echo "<input type='hidden' name='valmerkki'       value='$valmerkki'>";
  echo "<input type='hidden' name='limit'         value='$limit'>";
  echo "<input type='hidden' name='autoid'         value='$rekrivi[tunnus]'>";
  //    echo "<input type='hidden' name='merkki'         value='$rekrivi[merkki]'>";
  echo "<input type='hidden' name='malli'         value='$rekrivi[malli]'>";
  echo "<input type='hidden' name='mallitarkenne'     value='$rekrivi[mallitarkenne]'>";
  echo "<input type='hidden' name='korityyppi'       value='$rekrivi[korityyppi]'>";
  echo "<input type='hidden' name='lisatiedot'      value='$rekrivi[lisatiedot]'>";
  echo "<input type='hidden' name='bensa'           value='$bensa'>";
  echo "<input type='hidden' name='cc'           value='$rekrivi[cc]'>";
  echo "<input type='hidden' name='moottorityyppi'    value='$rekrivi[moottorityyppi]'>";
  echo "<input type='hidden' name='teho_kw'        value='$rekrivi[teho_kw]'>";
  echo "<input type='hidden' name='teho_hv'        value='$rekrivi[teho_hv]'>";
  echo "<input type='hidden' name='sylinterimaara'    value='$rekrivi[sylinterimaara]'>";
  echo "<input type='hidden' name='sylinterinhalkaisija'  value='$rekrivi[teho_hv]'>";

  /*
    if (mysql_num_rows($rekres) == 1) {
      $chk = "CHECKED";
    }
    else {
      $chk = "";
    }
    */

  echo "<div style='height:600px;overflow:auto;'>";

  echo "<table>";
  echo "<tr>";
  echo "<th colspan='12'>Omat tiedot</th>";
  echo "</tr>";
  echo "<tr>";
  echo "<th>merkki</th>";
  echo "<th>malli</th>";
  echo "<th>mallitark</th>";
  echo "<th>vali</th>";
  echo "<th>vuosimalli</th>";
  echo "<th>moottorityyppi</th>";
  echo "<th>moottoritark</th>";
  echo "<th>moottoritil</th>";
  echo "<th>B/D</th>";
  echo "<th>teho</th>";
  echo "<th>autodata_id</th>";

  echo "<th></th>";
  echo "</tr>";

  $idt = "";
  $idt = array();

  $varilask = 1;
  $class = "";

  while ($yhtsop = mysql_fetch_array($rekres)) {
    //joka kolmas rivi eri värinen
    if ($varilask == 3) {
      $class = "class='back'";
      $varilask = 0;
    }
    else {
      $class = "";
    }

    if ($yhtsop['kayttovoima'] == 'P') {
      $yhtsop['kayttovoima'] = 'B';
    }

    if (stristr($autodata, $yhtsop['autodataid'])) {
      $chk = 'CHECKED';
    }
    else {
      $chk = '';
    }

    echo "<tr>";
    echo "<td $class>$yhtsop[merkki]</td>";
    echo "<td $class>$yhtsop[malli]</td>";
    echo "<td $class>$yhtsop[mallitark]</td>";
    echo "<td $class>$yhtsop[vali]</td>";
    echo "<td $class>$yhtsop[alkuvuosi]-$yhtsop[loppuvuosi]</td>";
    echo "<td $class>$yhtsop[moottorityyppi]</td>";
    echo "<td $class>$yhtsop[moottoritark]</td>";
    echo "<td $class>$yhtsop[moottoritil]</td>";
    echo "<td $class>$yhtsop[kayttovoima]</td>";
    echo "<td $class>$yhtsop[teho]</td>";
    echo "<td $class>$yhtsop[autodataid]</td>";

    echo "<td $class><input type='checkbox' name='idt[]' value='$yhtsop[autodataid]' $chk></td>";
    echo "</tr>";

    $varilask ++;
  }

  /*echo "<tr>";
    echo "<td colspan='14'>Merkitse seurantaan.</td>";
    echo "<td><input type='checkbox' name='idt[]' value='-2'></td>";
    echo "</tr>";*/

  echo "<tr>";
  echo "<td colspan='11'>", t('Ei löydy, hylkää'), ".</td>";
  echo "<td><input type='checkbox' name='idt[]' value='-1'></td>";
  echo "</tr>";

  echo "</table>";

  echo "</div>";

  echo "<br>";
  echo "<input type='submit' value='", t('Hyväksy/Jatka'), "'>";

  echo "</form>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='merkki'>";
  echo "<input type='hidden' name='mitka' value='$mitka'>";
  echo "<input type='hidden' name='valmerkki' value='$valmerkki'>";

  echo "<input type='hidden' name='tarvitaanmerkki' value='1'>";
  echo "<input type='hidden' name='autoid' value='$rekrivi[tunnus]'>";
  echo "<input type='hidden' name='bensa'  value='$bensa'>";
  echo "<input type='hidden' name='rekkareita' value='$rekkareita'>";

  echo "<br>";
  echo "<input type='submit' value='Ei löydy, näytä kaikki $rekrivi[merkki]'>";

  echo "</form>";
}


require "inc/footer.inc";
