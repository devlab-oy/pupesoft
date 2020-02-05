<?php

require "inc/parametrit.inc";

echo "<font class='head'>", t("Yhdistä rekisteri yhteensopivuuksiin"), "</font><hr>";

$db_tecdoc = mysql_connect($dbhost_tecdoc, $dbuser_tecdoc, $dbpass_tecdoc) or die('Could not connect: ' . mysql_error());
mysql_select_db($dbkanta_tecdoc, $db_tecdoc) or die("Could not select database $dbkanta_tecdoc<br>");

if ($limit == '') {
  $limit = 0;
}

if ($rekkari != '') {
  $rekkari = strtoupper(trim($rekkari));
  if (strlen($rekkari) == 6 and strpos($rekkari, '-') === false and $maa != "SE") {
    $rekkari = substr($rekkari, 0, 3)."-".substr($rekkari, 3, 3);
  }
}

$vuosirajalisa = "";

if ($alkuvuosiraja != "") {
  $vuosirajalisa .= " and kayttoonotto >= '{$alkuvuosiraja}' ";
}

if ($loppuvuosiraja != "") {
  $vuosirajalisa .= " and kayttoonotto <= '{$loppuvuosiraja}' ";
}

$mplisa1 = "";
$mplisa2 = "";
$mplisa3 = ", group_concat(distinct left(kayttoonotto, 4) order by kayttoonotto) kayttoonotto ";

// Näytetään rekkarisyöttökenttä
if ($mitka == 'korjaa' or $mitka == 'liita_rekkari') {
  echo "<table>";
  echo "<tr>";
  echo "<th colspan='3'>", t("Anna rekisterinumero");

  echo "</th><form method='post'>";
  echo "<input type='hidden' name='mitka' value='{$mitka}'>";
  echo "<input type='hidden' name='tee' value='go'>";
  echo "<input type='hidden' name='maa' value='{$maa}'>";

  echo "<td class='back'><input type='text' name = 'rekkari' value='{$rekkari}'></td>";

  echo "<td class='back'><input type='submit' value='", t("Hae"), "'></td>";
  echo "</form>";

  echo "</tr>";
  echo "</table>";
  echo "<br>";
}

if ($tee == 'valitse' and $valmerkki == '' and ($mitka != 'korjaa' and $mitka != 'liita_rekkari')) {

  $order = "3";

  if ($mitka == "hylatyt") {
    $order = "4";
  }

  $query = "SELECT merkki, count(*) laskin, sum(if(kohdistettu='',1,0)) kohdistamatta, sum(if(kohdistettu='y',1,0)) hylatty
            FROM rekisteritiedot USE INDEX (yhtio_maa_merkki)
            WHERE yhtio = '{$kukarow['yhtio']}'
            and maa     = '{$maa}'
            {$vuosirajalisa}
            group by merkki
            order by {$order} desc";
  $result = mysql_query($query, $link) or pupe_error($query);

  echo "<table>";
  echo "<tr>";
  echo "<th>", t("Valitse merkki"), "</th><th>", t("Kohdistamatta"), "</th><th>", t("Ohitettu"), "</th><th>", t("Yhteensä"), "</th><td class='back'></td></tr>";

  while ($merkkirow = mysql_fetch_array($result)) {
    echo "<form method='post'>";
    echo "<input type='hidden' name='alkuvuosiraja' value='{$alkuvuosiraja}'>";
    echo "<input type='hidden' name='loppuvuosiraja' value='{$loppuvuosiraja}'>";
    echo "<input type='hidden' name='mitka' value='{$mitka}'>";
    echo "<input type='hidden' name='maa' value='{$maa}'>";
    echo "<input type='hidden' name='tee' value='go'>";
    echo "<input type='hidden' name='valmerkki' value='{$merkkirow['merkki']}'>";
    echo "<tr>";
    echo "<td>{$merkkirow['merkki']}</td><td>{$merkkirow['kohdistamatta']}</td><td>{$merkkirow['hylatty']}</td><td>{$merkkirow['laskin']}</td>";
    echo "<td class='back'><input type='submit' value='Valitse'></td>";
    echo "</tr>";
    echo "</form>";
  }

  echo "</tr>";
  echo "</table>";
}

if ($tee == "hyvaksy" and count($idt) > 0) {

  //nollataan laskureita
  $autoid   = 0;
  $miinukset   = 0;
  $plussat   = 0;

  //loopataan eka kerran ja katotaan onko käyttäjä kämmännyt ja valinnut sekä malleja että ei mallia
  foreach ($idt as $autoid) {

    // Ollaan klikattu hylkää-nappulaa
    if (!is_numeric($autoid)) {
      $autoid = -1;
    }

    //jos on negatiivinen tarkottaa että ei ole löytynyt oikeaa
    if ($autoid < 0) {
      $miinukset++;
    }
    else {
      $plussat++;
    }
  }

  // Haetaan vain ne rekkarit jotka oikeesti on liitämättä (Ei jouduta nin herkästi tohon Joku kerkesi jo liitää haaraan...)
  if ($mitka == 'uudet' and $rektun != "") {
    $rektunlisa = " and tunnus in ({$rektun}) ";
  }
  else {
    $rektunlisa = "";
  }

  if ($miinukset+$plussat > 0) {
    $query = "SELECT rekno, ajoneuvolaji
              from rekisteritiedot
              where yhtio         = '{$kukarow['yhtio']}'
              and maa             = '{$maa}'
              and merkki          = '{$merkki}'
              and malli           = '{$malli}'
              and ajoneuvolaji    = '{$ajoneuvolaji}'
              and k_voima         = '{$k_voima}'
              and moottorin_til   = '{$moottorin_til}'
              and teho            = '{$teho}'
              and variantti       = '{$variantti}'
              and versio          = '{$versio}'
              and moottoritunnus  = '{$moottoritunnus}'
              and vetavat_akselit = '{$vetavat_akselit}'
              and vahapaastoisyys = '{$vahapaastoisyys}'
              {$mplisa1}
              {$rektunlisa}";
    $result = mysql_query($query, $link) or pupe_error($query);

    if (mysql_num_rows($result) > 0 and $mitka == 'uudet') {

      while ($rekkarirow = mysql_fetch_array($result)) {
        // Tarkistetaan jokaiselta rekkarille kaikki sopivuudet. jos jotain löytyy niin ei uskalleta päivittää mitään!!!!
        //HUOM: oli pakko laittaa and autoid > 0 koska päivitys epäonnistui hylättyjen kohdalta, mutta ens päivityksessä menee oikein
        $query = "SELECT tunnus
                  FROM yhteensopivuus_rekisteri
                  WHERE yhtio      = '{$kukarow['yhtio']}'
                  and maa          = '{$maa}'
                  and rekno        = '{$rekkarirow['rekno']}'
                  and ajoneuvolaji = '{$rekkarirow['ajoneuvolaji']}'
                  and autoid       > 0";
        $ekahaku = mysql_query($query, $link) or pupe_error($query);

        if (mysql_num_rows($ekahaku) > 0) {
          $tee = 'go';
        }
      }

      if ($tee == 'go') {
        echo "<br><font class='error'>", t("Joku kerkesi jo liittää tämän tiedon"), "!</font>";
      }
    }
  }
  else {
    $tee = '';
  }

  //jos korjaillaan vanhoja haetaan ja dellataan kaikki olemassa olevat liitokset samoilla tiedoilla joilla groupattiin aikasemmin
  if ($tee == "hyvaksy" and (($mitka == 'korjaa' and $miinukset+$plussat > 0) or ($mitka == 'hylatyt' and $miinukset == 0 and $plussat > 0))) {

    //haetaan kaikki samat tiedot joilla groupattiin aikasemmin
    if (is_resource($result) and mysql_num_rows($result) > 0) mysql_data_seek($result, 0);

    if (mysql_num_rows($result) > 0) {
      while ($rekkarirow = mysql_fetch_array($result)) {

        //tehtiin tämmöinen mercalle, koska väärään ajoneuvolajiin kohdistettu rekkari oli hankala poistaa "korjaa liitettyjä" ohjelmassa. Lajit 4 ja 5 eivät voi sisältää duplikaatteja rekisterinumeroita, jolloin tämä on safe.
        $ajoneuvolajilisa = ($rekkarirow['ajoneuvolaji'] == '4' or $rekkarirow['ajoneuvolaji'] == '5') ? '' : " and ajoneuvolaji = '{$rekkarirow['ajoneuvolaji']}' ";

        //dellataan jokaiselta rekkarille kaikki sopivuudet.
        $query = "DELETE
                  FROM yhteensopivuus_rekisteri
                  where yhtio = '{$kukarow['yhtio']}'
                  and maa     = '{$maa}'
                  and rekno   = '{$rekkarirow['rekno']}'
                  $ajoneuvolajilisa";
        $dellaus = mysql_query($query, $link) or pupe_error($query);

        $query = "UPDATE rekisteritiedot
                  SET kohdistettu = ''
                  where yhtio = '{$kukarow['yhtio']}'
                  and maa     = '{$maa}'
                  and rekno   = '{$rekkarirow['rekno']}'
                  $ajoneuvolajilisa";
        $updeitti = mysql_query($query, $link) or pupe_error($query);
      }
    }
  }

  //on löytynyt lisättävää
  if ($tee == "hyvaksy" and $plussat > 0) {

    //loopataan uudestaan ja tehdään tarvittavat lisäykset
    foreach ($idt as $autoid) {

      // Ollaan klikattu hylkää-nappulaa
      if (!is_numeric($autoid)) {
        $autoid = -1;
      }

      //haetaan kaikki samat tiedot joilla groupattiin aikasemmin
      if (is_resource($result) and mysql_num_rows($result) > 0) mysql_data_seek($result, 0);

      if (mysql_num_rows($result) > 0) {
        //mutta ei jos käyttäjä on valinnut että ei löydy oikeaa
        if ($autoid > 0) {

          while ($rekkarirow = mysql_fetch_array($result)) {
            //lisätään jokaiselle rekkarille kaikki valitut sopivuudet.
            $query = "INSERT into yhteensopivuus_rekisteri
                      SET yhtio     = '{$kukarow['yhtio']}',
                      maa          = '{$maa}',
                      rekno        = '{$rekkarirow['rekno']}',
                      ajoneuvolaji = '{$rekkarirow['ajoneuvolaji']}',
                      autoid       = '{$autoid}',
                      laatija      = '{$kukarow['kuka']}',
                      luontiaika   = now(),
                      muutospvm    = now(),
                      muuttaja     = '{$kukarow['kuka']}'";
            $insertti = mysql_query($query, $link) or pupe_error($query);

            $query = "UPDATE rekisteritiedot
                      SET kohdistettu = 'x'
                      where yhtio      = '{$kukarow['yhtio']}'
                      and maa          = '{$maa}'
                      and rekno        = '{$rekkarirow['rekno']}'
                      and ajoneuvolaji = '{$rekkarirow['ajoneuvolaji']}'";
            $updeitti = mysql_query($query, $link) or pupe_error($query);
          }
        }
      }
    }
  }
  //jos pelkästään miinus niin lisätään tämä ohitettuna
  elseif ($tee == "hyvaksy" and $miinukset > 0 and $mitka != 'hylatyt') {

    //loopataan uudestaan ja tehdään tarvittavat lisäykset
    foreach ($idt as $autoid) {

      // Ollaan klikattu hylkää-nappulaa
      if (!is_numeric($autoid)) {
        $autoid = -1;
      }

      //haetaan kaikki samat tiedot joilla groupattiin aikasemmin
      if (is_resource($result) and mysql_num_rows($result) > 0) mysql_data_seek($result, 0);

      if (mysql_num_rows($result) > 0) {
        //mutta ei jos käyttäjä on valinnut että ei löydy oikeaa
        if ($autoid < 0) {

          while ($rekkarirow = mysql_fetch_array($result)) {
            //lisätään jokaiselle rekkarille kaikki valitut sopivuudet.
            $query = "INSERT into yhteensopivuus_rekisteri
                      SET yhtio     = '{$kukarow['yhtio']}',
                      maa          = '{$maa}',
                      rekno        = '{$rekkarirow['rekno']}',
                      ajoneuvolaji = '{$rekkarirow['ajoneuvolaji']}',
                      autoid       = '{$autoid}',
                      laatija      = '{$kukarow['kuka']}',
                      luontiaika   = now(),
                      muutospvm    = now(),
                      muuttaja     = '{$kukarow['kuka']}'";
            $insertti = mysql_query($query, $link) or pupe_error($query);

            $query = "UPDATE rekisteritiedot
                      SET kohdistettu = 'y'
                      where yhtio      = '{$kukarow['yhtio']}'
                      and maa          = '{$maa}'
                      and rekno        = '{$rekkarirow['rekno']}'
                      and ajoneuvolaji = '{$rekkarirow['ajoneuvolaji']}'";
            $updeitti = mysql_query($query, $link) or pupe_error($query);
          }
        }
      }
    }
  }

  if ($tee == 'hyvaksy') {
    if ($mitka == 'liita_rekkari') {
      $tee = '';
      $mitka = '';
      $rekkari = '';
    }
    else {
      $tee = 'go';
      $limit++;
    }
  }
}
elseif ($tee == "hyvaksy") {
  //ei olla ruksattu ytään autoa
  echo "<br><font class='error'>", t("Valitse sopivat ajoneuvomallit"), "!</font><br><br>";
  $tee = 'go';
}

if ($tee == 'hyvaksy' and $mitka == 'korjaa') {
  $tee = "";
  $mitka = "";
}

if ($tee == 'go' and $mitka == 'uudet') {
  // käydään kaikki autot läpi yleisyysjärjestyksessä
  $query = "SELECT merkki, malli, ajoneuvolaji, k_voima, moottorin_til, teho, vetavat_akselit, vahapaastoisyys, variantti, versio, moottoritunnus {$mplisa3},
            count(*) kpl, min(valmistenumero) valmistenumero_min, max(valmistenumero) valmistenumero_max, group_concat(tunnus) rektun
            FROM rekisteritiedot use index (yhtio_merkki_kohdistettu)
            WHERE yhtio     = '{$kukarow['yhtio']}'
            and maa         = '{$maa}'
            and merkki      = '{$valmerkki}'
            and kohdistettu = ''
            {$vuosirajalisa}
            GROUP BY merkki, malli, ajoneuvolaji, k_voima, moottorin_til, teho, vetavat_akselit, vahapaastoisyys, variantti, versio, moottoritunnus {$mplisa2}
            ORDER BY kpl DESC
            LIMIT 1";
  $result = mysql_query($query, $link) or pupe_error($query);
  $rekrivi = mysql_fetch_array($result);
}
elseif ($tee == 'go' and $mitka == 'hylatyt') {
  // käydään kaikki autot läpi, jotka on linkattu hylätyiksi
  $query = "SELECT merkki, malli, ajoneuvolaji, k_voima, moottorin_til, teho, vetavat_akselit, vahapaastoisyys, variantti, versio, moottoritunnus {$mplisa3},
            count(*) kpl, min(valmistenumero) valmistenumero_min, max(valmistenumero) valmistenumero_max, group_concat(tunnus) rektun
            FROM rekisteritiedot use index (yhtio_merkki_kohdistettu)
            WHERE yhtio     = '{$kukarow['yhtio']}'
            and maa         = '{$maa}'
            and merkki      = '{$valmerkki}'
            and kohdistettu = 'y'
            {$vuosirajalisa}
            GROUP BY merkki, malli, ajoneuvolaji, k_voima, moottorin_til, teho, vetavat_akselit, vahapaastoisyys, variantti, versio, moottoritunnus {$mplisa2}
            ORDER BY kpl DESC
            LIMIT {$limit} , 1";
  $result = mysql_query($query, $link) or pupe_error($query);

  if (mysql_num_rows($result) == 0) {
    echo "<br><font class='error'>", t("Ei löytynyt yhtään hylättyä"), " {$valmerkki}!</font>";
    $tee = "";
  }
  else {
    $rekrivi = mysql_fetch_array($result);
  }

}
elseif ($tee == 'go' and $mitka == 'korjaa' and trim($rekkari) != '') {

  //haetaan rekisterinumeron perusteella rivit muutettavaksi
  $query = "SELECT merkki, malli, ajoneuvolaji, k_voima, moottorin_til, teho, vetavat_akselit, vahapaastoisyys, variantti, versio, moottoritunnus {$mplisa3},
            count(*) kpl, min(valmistenumero) valmistenumero_min, max(valmistenumero) valmistenumero_max, group_concat(tunnus) rektun
            FROM rekisteritiedot use index (yhtio_maa_rekno_kohdistettu)
            WHERE yhtio      = '{$kukarow['yhtio']}'
            and maa          = '{$maa}'
            and rekno        = '{$rekkari}'
            and kohdistettu != ''
            GROUP BY merkki, malli, ajoneuvolaji, k_voima, moottorin_til, teho, vetavat_akselit, vahapaastoisyys, variantti, versio, moottoritunnus {$mplisa2}
            ORDER BY kpl DESC
            LIMIT 1";
  $result = mysql_query($query, $link) or pupe_error($query);

  if (mysql_num_rows($result) == 0) {
    echo "<br><font class='error'>", t("Ei löytynyt yhtään liitosta rekisterinumerolla"), " {$rekkari}!</font><br /><br />";
    $tee = "";
  }
  else {
    $rekrivi = mysql_fetch_array($result);

    //haetaan rekisterinumeron perusteella rivit muutettavaksi
    $query = "SELECT count(*) kpl
              FROM rekisteritiedot a use index (rekno_index)
              JOIN rekisteritiedot b use index (merkki_malli) ON
              a.yhtio            = b.yhtio          AND
              a.merkki           = b.merkki           AND
              a.malli            = b.malli            AND
              a.ajoneuvolaji     = b.ajoneuvolaji     AND
              a.k_voima          = b.k_voima          AND
              a.moottorin_til    = b.moottorin_til    AND
              a.teho             = b.teho             AND
              a.vetavat_akselit  = b.vetavat_akselit  AND
              a.vahapaastoisyys  = b.vahapaastoisyys  AND
              a.variantti        = b.variantti        AND
              a.versio           = b.versio           AND
              a.moottoritunnus   = b.moottoritunnus   AND
              b.kohdistettu     != ''
              WHERE a.yhtio='{$kukarow['yhtio']}'
              AND a.rekno='{$rekkari}'
              GROUP BY a.merkki, a.malli, a.ajoneuvolaji, a.k_voima, a.moottorin_til, a.teho,a.vetavat_akselit, a.vahapaastoisyys, a.variantti, a.versio, a.moottoritunnus";
    $result_kpl = mysql_query($query, $link) or pupe_error($query);
    $kpl_row = mysql_fetch_assoc($result_kpl);
    $rekrivi['kpl'] = $kpl_row['kpl'];
  }
}
elseif ($tee == 'go' and $mitka == 'liita_rekkari' and trim($rekkari) != '') {

  //haetaan rekisterinumeron perusteella rivit muutettavaksi
  $query = "SELECT merkki, malli, ajoneuvolaji, k_voima, moottorin_til, teho, vetavat_akselit, vahapaastoisyys, variantti, versio, moottoritunnus {$mplisa3},
            count(*) kpl, min(valmistenumero) valmistenumero_min, max(valmistenumero) valmistenumero_max, group_concat(tunnus) rektun
            FROM rekisteritiedot use index (yhtio_maa_rekno_kohdistettu)
            WHERE yhtio     = '{$kukarow['yhtio']}'
            and maa         = '{$maa}'
            and rekno       = '{$rekkari}'
            and kohdistettu = ''
            GROUP BY merkki, malli, ajoneuvolaji, k_voima, moottorin_til, teho, vetavat_akselit, vahapaastoisyys, variantti, versio, moottoritunnus {$mplisa2}
            ORDER BY kpl DESC
            LIMIT 1";
  $result = mysql_query($query, $link) or pupe_error($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>", t("Rekisterinumero on jo liitetty"), " {$rekkari}!</font><br /><br />";
    $tee = "";
  }
  else {
    $rekrivi = mysql_fetch_array($result);

    //haetaan rekisterinumeron perusteella rivit muutettavaksi
    $query = "SELECT count(*) kpl
              FROM rekisteritiedot a use index (rekno_index)
              JOIN rekisteritiedot b use index (merkki_malli) ON
              a.yhtio           = b.yhtio          AND
              a.merkki          = b.merkki           AND
              a.malli           = b.malli            AND
              a.ajoneuvolaji    = b.ajoneuvolaji     AND
              a.k_voima         = b.k_voima          AND
              a.moottorin_til   = b.moottorin_til    AND
              a.teho            = b.teho             AND
              a.vetavat_akselit = b.vetavat_akselit  AND
              a.vahapaastoisyys = b.vahapaastoisyys  AND
              a.variantti       = b.variantti        AND
              a.versio          = b.versio           AND
              a.moottoritunnus  = b.moottoritunnus   AND
              b.kohdistettu     = ''
              WHERE a.yhtio='{$kukarow['yhtio']}'
              AND a.rekno='{$rekkari}'
              GROUP BY a.merkki, a.malli, a.ajoneuvolaji, a.k_voima, a.moottorin_til, a.teho,a.vetavat_akselit, a.vahapaastoisyys, a.variantti, a.versio, a.moottoritunnus";
    $result_kpl = mysql_query($query, $link) or pupe_error($query);
    $kpl_row = mysql_fetch_assoc($result_kpl);
    $rekrivi['kpl'] = $kpl_row['kpl'];
  }
}
else {
  if ($tee == 'go' and ($mitka == 'korjaa' or $mitka == 'liita_rekkari') and trim($rekkari) == '') {
    echo "<br><font class='error'>", t("Rekisterinumero ei saa olla tyhjä"), "!</font>";
    exit;
  }
  elseif ($tee != 'valitse') {
    $tee = '';
  }
}

if ($rekrivi['merkki'] != '') {
  //tehdään tästä stringi johon in voisi osua
  if (strpos($rekrivi['malli'], ' ') !== false) {

    $mallior = $malliorpc = $malliorlcv = '';

    $mallitekstin_osat = explode(' ', trim($rekrivi['malli']));

    $mallior .= " AND (";
    $malliorpc .= " AND (";
    $malliorlcv .= " AND (";

    foreach (explode(" ", str_replace("-", " ", trim($rekrivi['malli']))) as $osa) {
      $mallior .= " T_MS_DESIG.C_TEXT LIKE ('%$osa%') OR";
      $malliorpc .= " T_PC_DESIG.C_TEXT LIKE ('%$osa%') OR";
      $malliorlcv .= " T_LCV_DESIG.C_TEXT LIKE ('%$osa%') OR";
    }

    $mallior = substr($mallior, 0, -2);
    $malliorpc = substr($malliorpc, 0, -2);
    $malliorlcv = substr($malliorlcv, 0, -2);

    $mallior .= " )";
    $malliorpc .= " )";
    $malliorlcv .= " )";
  }
  else {
    $mallior = " AND T_MS_DESIG.C_TEXT LIKE ('".$rekrivi['malli']."%') ";
    $malliorpc = " AND T_PC_DESIG.C_TEXT LIKE ('".$rekrivi['malli']."%') ";
    $malliorlcv = " AND T_LCV_DESIG.C_TEXT LIKE ('".$rekrivi['malli']."%') ";
  }

  if ($rekrivi['malli'] != '' and strpos($rekrivi['malli'], "-") !== FALSE and in_array($rekrivi['ajoneuvolaji'], array('8', '9', 'A', 'B', 'C', 'D', 'E'))) {
    $mallispec = str_replace(" ", "", substr($rekrivi['malli'], 0, strpos($rekrivi['malli'], "-")));
  }
  else {
    $mallispec = $rekrivi['malli'];
  }

  $roundi = 2;

  if ($rekrivi['moottorin_til'] != '' and !in_array($rekrivi['ajoneuvolaji'], array('8', '9', 'A', 'B', 'C', 'D', 'E'))) {
    $roundi = strlen(substr(trim($rekrivi['moottorin_til']), strpos(trim($rekrivi['moottorin_til']), '.')+1));
    $rektila = "('".($rekrivi['moottorin_til']-0.01)."','".$rekrivi['moottorin_til']."','".($rekrivi['moottorin_til']+0.01)."','".round($rekrivi['moottorin_til'], 1)."')";
  }
  elseif ($rekrivi['moottorin_til'] != '' and in_array($rekrivi['ajoneuvolaji'], array('8', '9', 'A', 'B', 'C', 'D', 'E'))) {
    $roundi = strlen(substr(trim($rekrivi['moottorin_til']), strpos(trim($rekrivi['moottorin_til']), '.')+1));
    $rektila = "('".($rekrivi['moottorin_til']-1)."','".$rekrivi['moottorin_til']."','".($rekrivi['moottorin_til']+1)."','".round($rekrivi['moottorin_til'], 1)."')";
  }
  else {
    $roundi = strlen(substr(trim($rekrivi['moottorin_til']), strpos(trim($rekrivi['moottorin_til']), '.')+1));
    $rektila = "('".$rekrivi['moottorin_til']."')";
  }

  if ($rekrivi['teho'] > 0) {
    $rekteho = "'".((float) $rekrivi['teho'])."','".((float) $rekrivi['teho'] + 1)."'";
    $rekteho_ruudulle = (float) $rekrivi['teho'];
  }
  else {
    $rekteho = "";
    $rekteho_ruudulle = "";
  }

  if ($rekrivi["k_voima"] == 1) {
    $bensa = "Bensiini, Petrol";
  }
  elseif ($rekrivi["k_voima"] == 3) {
    $bensa = "Diesel";
  }
  else {
    $bensa = "";
  }

  if (strpos($rekrivi['merkki'], 'DATSUN') !== FALSE) {
    $matchmerkki = 'NISSAN';
    $matchlisa   = ' / NISSAN';
  }
  elseif (strpos($rekrivi['merkki'], 'DAEWOO') !== FALSE) {
    $matchmerkki = 'DAEWOO';
    $matchlisa   = ' / DAEWOO';
  }
  elseif (strpos($rekrivi['merkki'], 'HARLEY') !== FALSE) {
    $matchmerkki = 'HARLEY DAVIDSON';
    $matchlisa   = '';
  }
  elseif (strpos($rekrivi['merkki'], 'GUZZI') !== FALSE) {
    $matchmerkki = 'MOTO GUZZI';
    $matchlisa   = '';
  }
  elseif (strpos($rekrivi['merkki'], 'VOLKSWAGEN') !== FALSE) {
    $matchmerkki = 'VW';
    $matchlisa   = '';
  }
  else {
    $matchmerkki = $rekrivi['merkki'];
    $matchlisa   = '';
  }
}

if (trim($rekrivi['kayttoonotto']) != '' and trim($rekrivi['kayttoonotto']) > 0 and strpos($rekrivi['kayttoonotto'], ',') === false) {
  $pcyearlisa = " AND YEAR(T_PC.C_CONSTRYEARFROM) <= ($rekrivi[kayttoonotto] + 6) AND IFNULL(YEAR(T_PC.C_CONSTRYEARTO), 9999) >= ($rekrivi[kayttoonotto] - 6) ";
  $pcyearlisa2 = " WHERE YEAR(T_PC.C_CONSTRYEARFROM) <= ($rekrivi[kayttoonotto] + 6) AND IFNULL(YEAR(T_PC.C_CONSTRYEARTO), 9999) >= ($rekrivi[kayttoonotto] - 6) ";

  $lcvyearlisa = " AND YEAR(T_LCV.C_CONSTRYEARFROM) <= ($rekrivi[kayttoonotto] + 6) AND IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), 9999) >= ($rekrivi[kayttoonotto] - 6) ";
  $lcvyearlisa2 = " WHERE YEAR(T_LCV.C_CONSTRYEARFROM) <= ($rekrivi[kayttoonotto] + 6) AND IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), 9999) >= ($rekrivi[kayttoonotto] - 6) ";
}

if ($tee == 'go' and mysql_num_rows($result) > 0 and $rekrivi['merkki'] != '' and !in_array($rekrivi['ajoneuvolaji'], array('8', '9', 'A', 'B', 'C', 'D', 'E'))) {

  //vanhemmissa autoissa ei ole tehoa
  if ($rekteho != '') {
    $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
               T_PC.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
               T_PC.C_KWFROM AS teho_kw,
               T_PC.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_PC.C_CYL AS sylinterimaara,
               ROUND(T_PC.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_PC.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_PC
               JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
               LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%' {$mallior})
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE T_PC.C_KWFROM  IN ({$rekteho})
               AND ROUND(T_PC.C_CAPLIT, $roundi) IN {$rektila}
               $pcyearlisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               UNION
               (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
               T_LCV.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
               T_LCV.C_KWFROM AS teho_kw,
               T_LCV.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_LCV.C_CYL AS sylinterimaara,
               ROUND(T_LCV.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_LCV.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_LCV
               JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
               LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%' {$mallior})
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE T_LCV.C_KWFROM IN ({$rekteho})
               AND ROUND(T_LCV.C_CAPLIT, $roundi) IN {$rektila}
               $lcvyearlisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
    $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);

    if (mysql_num_rows($rekres) == 0) {
      $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
                 T_PC.C_LTTYPE_VALKEY AS tyyppi,
                 IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
                 IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
                 YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
                 IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
                 MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
                 MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
                 T_PC.C_KWFROM AS teho_kw,
                 T_PC.C_HPFROM AS teho_hv,
                 T_KEYVAL_NAME.C_TEXT AS polttoaine,
                 T_PC.C_CYL AS sylinterimaara,
                 ROUND(T_PC.C_CAPLIT, 1) AS cc,
                 IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
                 T_PC.C_VALVESTOTAL venttiilit,
                 T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
                 T_LTB.C_BRANDNAME AS merkki,
                 T_MS_DESIG.C_TEXT AS malliosa1,
                 GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
                 REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
                 FROM T_PC
                 JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
                 LEFT JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
                 LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
                 JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
                 JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
                 JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
                 JOIN T_PC_DESIG ON (T_PC_DESIG.C_FK = T_PC.C_PK {$malliorpc})
                 JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
                 LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
                 LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
                 LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
                 LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
                 WHERE T_PC.C_KWFROM  IN ({$rekteho})
                 AND ROUND(T_PC.C_CAPLIT, $roundi) IN {$rektila}
                 $pcyearlisa
                 GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
                 UNION
                 (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
                 T_LCV.C_LTTYPE_VALKEY AS tyyppi,
                 IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
                 IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
                 YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
                 IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
                 MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
                 MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
                 T_LCV.C_KWFROM AS teho_kw,
                 T_LCV.C_HPFROM AS teho_hv,
                 T_KEYVAL_NAME.C_TEXT AS polttoaine,
                 T_LCV.C_CYL AS sylinterimaara,
                 ROUND(T_LCV.C_CAPLIT, 1) AS cc,
                 IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
                 T_LCV.C_VALVESTOTAL venttiilit,
                 T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
                 T_LTB.C_BRANDNAME AS merkki,
                 T_MS_DESIG.C_TEXT AS malliosa1,
                 GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
                 REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
                 FROM T_LCV
                 JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
                 LEFT JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
                 LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
                 JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
                 JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
                 JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
                 JOIN T_LCV_DESIG ON (T_LCV_DESIG.C_FK = T_LCV.C_PK {$malliorlcv})
                 JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
                 LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
                 LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
                 LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
                 LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
                 WHERE T_LCV.C_KWFROM IN ({$rekteho})
                 AND ROUND(T_LCV.C_CAPLIT, $roundi) IN {$rektila}
                 $lcvyearlisa
                 GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
                 ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
      $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);
    }

    //match against AND
    if (mysql_num_rows($rekres) == 0) {
      $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
                 T_PC.C_LTTYPE_VALKEY AS tyyppi,
                 IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
                 IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
                 YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
                 IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
                 MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
                 MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
                 T_PC.C_KWFROM AS teho_kw,
                 T_PC.C_HPFROM AS teho_hv,
                 T_KEYVAL_NAME.C_TEXT AS polttoaine,
                 T_PC.C_CYL AS sylinterimaara,
                 ROUND(T_PC.C_CAPLIT, 1) AS cc,
                 IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
                 T_PC.C_VALVESTOTAL venttiilit,
                 T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
                 T_LTB.C_BRANDNAME AS merkki,
                 T_MS_DESIG.C_TEXT AS malliosa1,
                 GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
                 REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
                 FROM T_PC
                 JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
                 LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
                 LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
                 JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
                 JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
                 JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
                 JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_TEXT LIKE '%{$rekrivi['malli']}%' AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
                 LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
                 LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
                 LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
                 LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
                 WHERE T_PC.C_KWFROM  IN ({$rekteho})
                 AND ROUND(T_PC.C_CAPLIT, $roundi) IN {$rektila}
                 $pcyearlisa
                 GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
                 UNION
                 (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
                 T_LCV.C_LTTYPE_VALKEY AS tyyppi,
                 IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
                 IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
                 YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
                 IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
                 MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
                 MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
                 T_LCV.C_KWFROM AS teho_kw,
                 T_LCV.C_HPFROM AS teho_hv,
                 T_KEYVAL_NAME.C_TEXT AS polttoaine,
                 T_LCV.C_CYL AS sylinterimaara,
                 ROUND(T_LCV.C_CAPLIT, 1) AS cc,
                 IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
                 T_LCV.C_VALVESTOTAL venttiilit,
                 T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
                 T_LTB.C_BRANDNAME AS merkki,
                 T_MS_DESIG.C_TEXT AS malliosa1,
                 GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
                 REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
                 FROM T_LCV
                 JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
                 LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
                 LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
                 JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
                 JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
                 JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
                 JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_TEXT LIKE '%{$rekrivi['malli']}%' AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
                 LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
                 LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
                 LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
                 LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
                 WHERE T_LCV.C_KWFROM IN ({$rekteho})
                 AND ROUND(T_LCV.C_CAPLIT, $roundi) IN {$rektila}
                 $lcvyearlisa
                 GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
                 ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
      $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);
    }
  }
  else {
    //aloitetaan ilman tehoa, vanhemmissa autoissa ei ylläpidetty tehoa
    $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
               T_PC.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
               T_PC.C_KWFROM AS teho_kw,
               T_PC.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_PC.C_CYL AS sylinterimaara,
               ROUND(T_PC.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_PC.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_PC
               JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
               LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               LEFT JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%' {$mallior})
               LEFT JOIN T_PC_DESIG ON (T_PC_DESIG.C_FK = T_PC.C_PK {$malliorpc})
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE ROUND(T_PC.C_CAPLIT, $roundi) IN {$rektila}
               $pcyearlisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               UNION
               (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
               T_LCV.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
               T_LCV.C_KWFROM AS teho_kw,
               T_LCV.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_LCV.C_CYL AS sylinterimaara,
               ROUND(T_LCV.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_LCV.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_LCV
               JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
               LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               LEFT JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%' {$mallior})
               LEFT JOIN T_LCV_DESIG ON (T_LCV_DESIG.C_FK = T_LCV.C_PK {$malliorlcv})
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE ROUND(T_LCV.C_CAPLIT, $roundi) IN {$rektila}
               $lcvyearlisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
    $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);
  }
  //aloitetaan uusix, mutta ilman tehoa, vanhemmissa autoissa ei ylläpidetty tehoa
  if (mysql_num_rows($rekres) == 0 and $rekteho != '') {
    $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
               T_PC.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
               T_PC.C_KWFROM AS teho_kw,
               T_PC.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_PC.C_CYL AS sylinterimaara,
               ROUND(T_PC.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_PC.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_PC
               JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
               LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               LEFT JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%' {$mallior})
               LEFT JOIN T_PC_DESIG ON (T_PC_DESIG.C_FK = T_PC.C_PK {$malliorpc})
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE ROUND(T_PC.C_CAPLIT, $roundi) IN {$rektila}
               $pcyearlisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               UNION
               (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
               T_LCV.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
               T_LCV.C_KWFROM AS teho_kw,
               T_LCV.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_LCV.C_CYL AS sylinterimaara,
               ROUND(T_LCV.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_LCV.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_LCV
               JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
               LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               LEFT JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%' {$mallior})
               LEFT JOIN T_LCV_DESIG ON (T_LCV_DESIG.C_FK = T_LCV.C_PK {$malliorlcv})
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE ROUND(T_LCV.C_CAPLIT, $roundi) IN {$rektila}
               $lcvyearlisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
    $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);
  }

  //match against AND
  if (mysql_num_rows($rekres) == 0) {
    $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
               T_PC.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
               T_PC.C_KWFROM AS teho_kw,
               T_PC.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_PC.C_CYL AS sylinterimaara,
               ROUND(T_PC.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_PC.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_PC
               JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
               LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_TEXT LIKE '%{$rekrivi['malli']}%' AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE ROUND(T_PC.C_CAPLIT, $roundi) IN {$rektila}
               $pcyearlisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               UNION
               (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
               T_LCV.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
               T_LCV.C_KWFROM AS teho_kw,
               T_LCV.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_LCV.C_CYL AS sylinterimaara,
               ROUND(T_LCV.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_LCV.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_LCV
               JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
               LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_TEXT LIKE '%{$rekrivi['malli']}%' AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE ROUND(T_LCV.C_CAPLIT, $roundi) IN {$rektila}
               $lcvyearlisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
    $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);
  }

  //aloitetaan uusix, mutta ilman tilavuutta
  if (mysql_num_rows($rekres) == 0) {
    $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
               T_PC.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
               T_PC.C_KWFROM AS teho_kw,
               T_PC.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_PC.C_CYL AS sylinterimaara,
               ROUND(T_PC.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_PC.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_PC
               JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
               LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               LEFT JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%' {$mallior})
               LEFT JOIN T_PC_DESIG ON (T_PC_DESIG.C_FK = T_PC.C_PK {$malliorpc})
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               $pcyearlisa2
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               UNION
               (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
               T_LCV.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
               T_LCV.C_KWFROM AS teho_kw,
               T_LCV.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_LCV.C_CYL AS sylinterimaara,
               ROUND(T_LCV.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_LCV.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_LCV
               JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
               LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               LEFT JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%' {$mallior})
               LEFT JOIN T_LCV_DESIG ON (T_LCV_DESIG.C_FK = T_LCV.C_PK {$malliorlcv})
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               $lcvyearlisa2
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
    $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);
  }

  //match against AND
  if (mysql_num_rows($rekres) == 0) {
    $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
               T_PC.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
               T_PC.C_KWFROM AS teho_kw,
               T_PC.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_PC.C_CYL AS sylinterimaara,
               ROUND(T_PC.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_PC.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_PC
               JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
               LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_TEXT LIKE '%{$rekrivi['malli']}%' AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               $pcyearlisa2
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               UNION
               (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
               T_LCV.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
               T_LCV.C_KWFROM AS teho_kw,
               T_LCV.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_LCV.C_CYL AS sylinterimaara,
               ROUND(T_LCV.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_LCV.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_LCV
               JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
               LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_TEXT LIKE '%{$rekrivi['malli']}%' AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               $lcvyearlisa2
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
    $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);
  }

  //aloitetaan uusix, mutta ilman mallia ja tilavuus takas
  if (mysql_num_rows($rekres) == 0) {
    $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
               T_PC.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
               T_PC.C_KWFROM AS teho_kw,
               T_PC.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_PC.C_CYL AS sylinterimaara,
               ROUND(T_PC.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_PC.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_PC
               JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
               LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE ROUND(T_PC.C_CAPLIT, $roundi) IN {$rektila}
               $pcyearlisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               UNION
               (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
               T_LCV.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
               T_LCV.C_KWFROM AS teho_kw,
               T_LCV.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_LCV.C_CYL AS sylinterimaara,
               ROUND(T_LCV.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_LCV.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_LCV
               JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
               LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE ROUND(T_LCV.C_CAPLIT, $roundi) IN {$rektila}
               $lcvyearlisa
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
    $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);
  }

  if (mysql_num_rows($rekres) == 0) {
    $tarvitaanmerkki = 1;
  }
}
elseif ($tee == 'go' and mysql_num_rows($result) == 0) {
  echo "<br><font class='error'>", t("Ei löytynyt ajoneuvoja rekiteritiedoista merkillä"), " {$matchmerkki}!</font>";
  $tee = '';
}

// vähennetään kritereitä pelkkä merkki
if ($tarvitaanmerkki > 0 and $rekrivi['merkki'] != '') {

  if ($bensa != '') {
    $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
               T_PC.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
               T_PC.C_KWFROM AS teho_kw,
               T_PC.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_PC.C_CYL AS sylinterimaara,
               ROUND(T_PC.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_PC.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_PC
               JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
               LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS.C_PK = T_MS_CONSTRTYPE.C_FK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               $pcyearlisa2
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               UNION
               (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
               T_LCV.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
               T_LCV.C_KWFROM AS teho_kw,
               T_LCV.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_LCV.C_CYL AS sylinterimaara,
               ROUND(T_LCV.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_LCV.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_LCV
               JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
               LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%') AND T_KEYVAL_NAME.C_TEXT IN ('".str_replace(",", "','", $bensa)."'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS.C_PK = T_MS_CONSTRTYPE.C_FK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               $lcvyearlisa2
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
    $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);
  }
  else {
    // vähennetään kritereitä pelkkä merkki
    $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
               T_PC.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
               T_PC.C_KWFROM AS teho_kw,
               T_PC.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_PC.C_CYL AS sylinterimaara,
               ROUND(T_PC.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_PC.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_PC
               JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
               LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS.C_PK = T_MS_CONSTRTYPE.C_FK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               $pcyearlisa2
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               UNION
               (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
               T_LCV.C_LTTYPE_VALKEY AS tyyppi,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
               YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
               T_LCV.C_KWFROM AS teho_kw,
               T_LCV.C_HPFROM AS teho_hv,
               T_KEYVAL_NAME.C_TEXT AS polttoaine,
               T_LCV.C_CYL AS sylinterimaara,
               ROUND(T_LCV.C_CAPLIT, 1) AS cc,
               IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
               T_LCV.C_VALVESTOTAL venttiilit,
               T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
               REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
               FROM T_LCV
               JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
               LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
               LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%'))
               JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
               JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
               JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO AND T_LTB.C_BRANDNAME = '{$matchmerkki}')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS.C_PK = T_MS_CONSTRTYPE.C_FK)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               $lcvyearlisa2
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
    $rekres = mysql_query($query, $db_tecdoc) or pupe_error($query);
  }

  $tee = 'go';
}

if ($tee == 'go') {

  echo "<table>";
  echo "<tr>";
  echo "<th colspan='3'>", t("AKE:n tiedot"), " (", t("tätä mallia rekisteröity"), " {$rekrivi['kpl']} ", t("kpl"), ")</th>";
  echo "</tr>";
  echo "<tr>";
  echo "<td class='back' valign='top'><table>";
  echo "<tr><th>", t("Merkki"), "</th><td nowrap>{$rekrivi['merkki']} {$matchlisa}</td></tr>";
  echo "<tr><th>", t("Malli"), "</th><td nowrap>{$rekrivi['malli']}</td></tr>";
  echo "<tr><th>", t("K_voima"), "</th><td nowrap>", str_replace("Bensiini, Petrol", "Bensiini", $bensa), "</td></tr>";
  echo "<tr><th>", t("Moottorin til"), "</th><td nowrap>{$rekrivi['moottorin_til']}</td></tr>";
  echo "<tr><th>", t("Teho"), "</th><td nowrap>{$rekteho_ruudulle} KW (".round((int)$rekteho_ruudulle*1.36, 0)."HV)</td></tr>";

  echo "</table></td>";

  echo "<td class='back' valign='top'><table>";
  echo "<tr><th>", t("Variantti"), "</th><td nowrap>{$rekrivi['variantti']} {$matchlisa}</td></tr>";
  echo "<tr><th>", t("Versio"), "</th><td nowrap>{$rekrivi['versio']}</td></tr>";
  echo "<tr><th>", t("Moottoritunnus"), "</th><td nowrap>{$rekrivi['moottoritunnus']}</td></tr>";

  echo "</table></td>";

  echo "<td class='back' valign='top'><table>";
  echo "<tr><th>", t("Noin k.otettu"), "</th><td nowrap>{$rekrivi['kayttoonotto']}</td></tr>";
  echo "<tr><th>", t("Ajoneuvolaji"), "</th><td nowrap>{$rekrivi['ajoneuvolaji']}</td></tr>";
  echo "<tr><th>", t("Vetavat akselit"), "</th><td nowrap>{$rekrivi['vetavat_akselit']}</td></tr>";
  echo "<tr><th>", t("Vahapaastoisyys"), "</th><td nowrap>{$rekrivi['vahapaastoisyys']}</td></tr>";
  echo "<tr><th>", t('Valmistenumero'), "</th><td nowrap>{$rekrivi['valmistenumero_min']} ... {$rekrivi['valmistenumero_max']}</td></tr>";
  echo "</table></td>";
  echo "</tr>";
  echo "</table>";
  echo "<br>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee'           value='hyvaksy'>";
  echo "<input type='hidden' name='alkuvuosiraja'     value='{$alkuvuosiraja}'>";
  echo "<input type='hidden' name='loppuvuosiraja'     value='{$loppuvuosiraja}'>";
  echo "<input type='hidden' name='mitka'         value='{$mitka}'>";
  echo "<input type='hidden' name='maa'           value='{$maa}'>";
  echo "<input type='hidden' name='valmerkki'       value='{$valmerkki}'>";
  echo "<input type='hidden' name='limit'         value='{$limit}'>";
  echo "<input type='hidden' name='merkki'         value='{$rekrivi['merkki']}'>";
  echo "<input type='hidden' name='malli'         value='{$rekrivi['malli']}'>";
  echo "<input type='hidden' name='ajoneuvolaji'       value='{$rekrivi['ajoneuvolaji']}'>";
  echo "<input type='hidden' name='k_voima'         value='{$rekrivi['k_voima']}'>";
  echo "<input type='hidden' name='moottorin_til'      value='{$rekrivi['moottorin_til']}'>";
  echo "<input type='hidden' name='teho'           value='{$rekrivi['teho']}'>";
  echo "<input type='hidden' name='vetavat_akselit'    value='{$rekrivi['vetavat_akselit']}'>";
  echo "<input type='hidden' name='vahapaastoisyys'    value='{$rekrivi['vahapaastoisyys']}'>";
  echo "<input type='hidden' name='variantti'       value='{$rekrivi['variantti']}'>";
  echo "<input type='hidden' name='versio'         value='{$rekrivi['versio']}'>";
  echo "<input type='hidden' name='moottoritunnus'    value='{$rekrivi['moottoritunnus']}'>";
  echo "<input type='hidden' name='kayttoonotto'      value='{$rekrivi['kayttoonotto']}'>";
  echo "<input type='hidden' name='kpl'          value='{$rekrivi['kpl']}'>";
  echo "<input type='hidden' name='valmistenumero_min'  value='{$rekrivi['valmistenumero_min']}'>";
  echo "<input type='hidden' name='valmistenumero_max'  value='{$rekrivi['valmistenumero_max']}'>";
  echo "<input type='hidden' name='rektun'        value='{$rekrivi['rektun']}'>";
  echo "<input type='hidden' name='rekkari'        value='{$rekkari}'>";

  $korjattavat = '';

  //tästä querystä otettiin veke ajoneuvolaji, koska joukkoon oli (konversioissa) eksynyt yhteensopivuuksia, joissa oli väärät ajoneuvolajit, tämä query ei palauttanut niitä ja silloin niitä ei voinut korjata.
  if ($mitka == 'korjaa' and trim($rekkari) != '') {
    $query = "SELECT group_concat(distinct autoid) autoid
              FROM yhteensopivuus_rekisteri
              WHERE yhtio = '{$kukarow['yhtio']}'
              and maa     = '{$maa}'
              #and ajoneuvolaji = '{$rekrivi['ajoneuvolaji']}'
              and rekno   = '{$rekkari}'";
    $result = mysql_query($query, $link) or pupe_error($query);
    $korjsop = mysql_fetch_array($result);

    $korjattavat = explode(",", $korjsop["autoid"]);
  }

  if (!is_array($korjattavat)) {
    if (mysql_num_rows($rekres) == 1 and $mitka != 'hylatyt') {
      $chk = "CHECKED";
    }
    else {
      $chk = "";
    }
  }

  if (mysql_num_rows($rekres) > 10) echo "<div style='height:450px;overflow:auto;'>";

  echo "<table>";
  echo "<tr>";

  $cspan = "13";

  echo "<th colspan='".($cspan+1)."'>", t("Omat tiedot"), "</th>";
  echo "</tr>";
  echo "<tr>";

  echo "<th>", t("Autoid"), "</th>";
  echo "<th>", t("Merkki ja malli"), "</th>";
  echo "<th>CC</th>";
  echo "<th>", t("Mallitark"), "</th>";
  echo "<th>", t("Alkukk / vuosi"), "</th>";
  echo "<th>", t("Loppukk / vuosi"), "</th>";
  echo "<th>", t("Moottorityyppi"), "</th>";
  echo "<th>B/D</th>";
  echo "<th>", t("S.maara"), "</th>";
  echo "<th>", t("Venttiilit"), "</th>";
  echo "<th>", t("Teho"), "</th>";
  echo "<th>", t("Voimansiirto"), "</th>";

  echo "<th></th>";
  echo "</tr>";

  $idt = array();

  if (is_array($korjattavat)) {
    foreach ($korjattavat as $tunn) {

      $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS tunnus,
                 T_PC.C_LTTYPE_VALKEY AS tyyppi,
                 IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
                 IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
                 YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
                 IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
                 MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
                 MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
                 T_PC.C_KWFROM AS teho_kw,
                 T_PC.C_HPFROM AS teho_hv,
                 T_KEYVAL_NAME.C_TEXT AS polttoaine,
                 T_PC.C_CYL AS sylinterimaara,
                 ROUND(T_PC.C_CAPLIT, 1) AS cc,
                 IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
                 T_PC.C_VALVESTOTAL venttiilit,
                 T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
                 T_LTB.C_BRANDNAME AS merkki,
                 T_MS_DESIG.C_TEXT AS malliosa1,
                 GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
                 REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
                 FROM T_PC
                 JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO)
                 LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
                 LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
                 JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
                 JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_FUELTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%'))
                 JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
                 JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO)
                 LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
                 LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_ZZ ON (T_PC.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
                 LEFT JOIN T_MS_CONSTRTYPE ON (T_MS.C_PK = T_MS_CONSTRTYPE.C_FK)
                 LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
                 WHERE T_PC.C_PCTYPENO   = '{$tunn}'
                 GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
                 UNION
                 (SELECT DISTINCT T_LCV.C_LCVTYPENO AS tunnus,
                 T_LCV.C_LTTYPE_VALKEY AS tyyppi,
                 IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
                 IFNULL(T_KEYVAL_NAME_BODYTYPE.C_TEXT, T_BTSYN.C_SYN) AS malliapu,
                 YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
                 IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
                 MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
                 MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
                 T_LCV.C_KWFROM AS teho_kw,
                 T_LCV.C_HPFROM AS teho_hv,
                 T_KEYVAL_NAME.C_TEXT AS polttoaine,
                 T_LCV.C_CYL AS sylinterimaara,
                 ROUND(T_LCV.C_CAPLIT, 1) AS cc,
                 IFNULL(COMPDESIG_FI.C_TEXT, COMPDESIG_ZZ.C_TEXT) AS pitkateksti,
                 T_LCV.C_VALVESTOTAL venttiilit,
                 T_KEYVAL_NAME_POWER.C_TEXT voimansiirto,
                 T_LTB.C_BRANDNAME AS merkki,
                 T_MS_DESIG.C_TEXT AS malliosa1,
                 GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG ORDER BY T_MS_CONSTRTYPE.C_DESIG ASC) AS malliosa2,
                 REPLACE(GROUP_CONCAT(DISTINCT T_ENGINE.C_ENGINECODE ORDER BY T_ENGINE.C_ENGINECODE ASC), ',', ', ') moottori
                 FROM T_LCV
                 JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO)
                 LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
                 LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
                 JOIN T_KEYVAL AS T_KEYVAL_POWER ON (T_KEYVAL_POWER.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL_POWER.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_POWER ON (T_KEYVAL_NAME_POWER.C_FK = T_KEYVAL_POWER.C_PK AND (T_KEYVAL_NAME_POWER.C_LNG like '%FI%' or T_KEYVAL_NAME_POWER.C_LNG like '%ZZ%'))
                 JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_FUELTYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_FUELTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%'))
                 JOIN T_KEYVAL AS T_KEYVAL_BODYTYPE ON (T_KEYVAL_BODYTYPE.C_KEYTABNO = T_MS.C_PCBODYTYPE_KEYTABNO AND T_KEYVAL_BODYTYPE.C_VALKEY = T_MS.C_PCBODYTYPE_VALKEY)
                 JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_BODYTYPE ON (T_KEYVAL_NAME_BODYTYPE.C_FK = T_KEYVAL_BODYTYPE.C_PK AND (T_KEYVAL_NAME_BODYTYPE.C_LNG like '%FI%' or T_KEYVAL_NAME_BODYTYPE.C_LNG like '%ZZ%'))
                 JOIN T_MS_DESIG on (T_MS.C_PK = T_MS_DESIG.C_FK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 JOIN T_LTB ON (T_MS.C_LTBRANDREF_BRANDNO = T_LTB.C_BRANDNO)
                 LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
                 LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_ZZ ON (T_LCV.C_PK = COMPDESIG_ZZ.C_FK AND COMPDESIG_ZZ.C_COURESTR_COUCODELIST LIKE '%ZZ%')
                 LEFT JOIN T_BTSYN on (T_MS.C_BTSYNREF_BTSYNNO = T_BTSYN.C_BTSYNNO)
                 LEFT JOIN T_MS_CONSTRTYPE ON (T_MS.C_PK = T_MS_CONSTRTYPE.C_FK)
                 LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
                 WHERE T_LCV.C_LCVTYPENO = '{$tunn}'
                 GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
                 ORDER BY merkki ASC, malliosa1 ASC, C_GENERATION ASC, malliosa2 ASC, pitkateksti ASC, malliapu ASC, autoalkuvuosi ASC";
      $korj_res = mysql_query($query, $db_tecdoc) or pupe_error($query);

      while ($korj_row = mysql_fetch_array($korj_res)) {
        echo "<tr class='aktiivi'>";

        if (is_array($korjattavat)) {
          if (in_array($korj_row['tunnus'], $korjattavat) !== FALSE) {
            $chk = "CHECKED";
          }
          else {
            $chk = "";
          }
        }

        echo "<td>{$korj_row['tunnus']}</td>";

        echo "<td>{$korj_row['merkki']} {$korj_row['malliosa1']}{$korj_row['C_GENERATION']} ";
        if ($korj_row['malliosa2'] != '') echo "({$korj_row['malliosa2']}) ";
        echo "{$korj_row['pitkateksti']}</td>";

        echo "<td>{$korj_row['cc']}</td>";
        echo "<td>{$korj_row['malliapu']}</td>";
        echo "<td>{$korj_row['autoalkukk']} / {$korj_row['autoalkuvuosi']}</td>";

        echo "<td>";
        if ($korj_row['autoloppuvuosi'] != '') {
          echo "{$korj_row['autoloppukk']} / {$korj_row['autoloppuvuosi']}";
        }
        echo "</td>";

        echo "<td>{$korj_row['moottori']}</td>";
        echo "<td>{$korj_row['polttoaine']}</td>";
        echo "<td>{$korj_row['sylinterimaara']}</td>";
        echo "<td>{$korj_row['venttiilit']}</td>";
        echo "<td>{$korj_row['teho_kw']} ({$korj_row['teho_hv']})</td>";
        echo "<td>{$korj_row['voimansiirto']}</td>";

        echo "<td><input type='checkbox' name='idt[]' value='{$korj_row['tunnus']}' {$chk}></td>";
        echo "</tr>";
      }
    }

    echo "<tr class='aktiivi'><td colspan='".($cspan+1)."' class='back'><hr></td></tr>";
  }

  while ($yhtsop = mysql_fetch_array($rekres)) {

    $piirra = TRUE;

    if (is_array($korjattavat) and in_array($yhtsop['tunnus'], $korjattavat) !== FALSE) {
      $piirra = FALSE;
    }

    if ($piirra) {
      echo "<tr class='aktiivi'>";

      echo "<td>{$yhtsop['tunnus']}</td>";

      echo "<td>{$yhtsop['merkki']} {$yhtsop['malliosa1']}{$yhtsop['C_GENERATION']} ";
      if ($yhtsop['malliosa2'] != '') echo "({$yhtsop['malliosa2']}) ";
      echo "{$yhtsop['pitkateksti']}</td>";

      echo "<td>{$yhtsop['cc']}</td>";
      echo "<td>{$yhtsop['malliapu']}</td>";
      echo "<td>{$yhtsop['autoalkukk']} / {$yhtsop['autoalkuvuosi']}</td>";
      echo "<td>";
      if ($yhtsop['autoloppuvuosi'] != '') {
        echo "{$yhtsop['autoloppukk']} / {$yhtsop['autoloppuvuosi']}";
      }
      echo "</td>";
      echo "<td>{$yhtsop['moottori']}</td>";
      echo "<td>{$yhtsop['polttoaine']}</td>";
      echo "<td>{$yhtsop['sylinterimaara']}</td>";
      echo "<td>{$yhtsop['venttiilit']}</td>";
      echo "<td>{$yhtsop['teho_kw']} ({$yhtsop['teho_hv']})</td>";
      echo "<td>{$yhtsop['voimansiirto']}</td>";

      echo "<td><input type='checkbox' name='idt[]' value='{$yhtsop['tunnus']}'></td>";
      echo "</tr>";
    }
  }

  if ($mitka == 'hylatyt') {
    $hylcheck = "CHECKED";
  }
  else {
    $hylcheck = "";
  }

  echo "</table>";

  if (mysql_num_rows($rekres) > 10) echo "</div>";

  echo "<br>";
  echo "<input type='submit' value='", t("Hyväksy"), "/", t("Jatka"), "'>";

  echo "<input type='submit' name='idt[]' value='", t("Mallia ei löydy omasta kannasta"), ": ", t("Hylkää"), "/", t("Jatka"), "'>";

  echo "</form><br><br>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='tee'               value='merkki'>";
  echo "<input type='hidden' name='alkuvuosiraja'         value='{$alkuvuosiraja}'>";
  echo "<input type='hidden' name='loppuvuosiraja'         value='{$loppuvuosiraja}'>";
  echo "<input type='hidden' name='mitka'             value='{$mitka}'>";
  echo "<input type='hidden' name='maa'               value='{$maa}'>";
  echo "<input type='hidden' name='valmerkki'           value='{$valmerkki}'>";
  echo "<input type='hidden' name='limit'             value='{$limit}'>";
  echo "<input type='hidden' name='rekrivi[merkki]'         value='{$rekrivi['merkki']}'>";
  echo "<input type='hidden' name='rekrivi[malli]'         value='{$rekrivi['malli']}'>";
  echo "<input type='hidden' name='rekrivi[ajoneuvolaji]'     value='{$rekrivi['ajoneuvolaji']}'>";
  echo "<input type='hidden' name='rekrivi[k_voima]'         value='{$rekrivi['k_voima']}'>";
  echo "<input type='hidden' name='rekrivi[moottorin_til]'    value='{$rekrivi['moottorin_til']}'>";
  echo "<input type='hidden' name='rekrivi[teho]'         value='{$rekrivi['teho']}'>";
  echo "<input type='hidden' name='rekrivi[vetavat_akselit]'    value='{$rekrivi['vetavat_akselit']}'>";
  echo "<input type='hidden' name='rekrivi[vahapaastoisyys]'    value='{$rekrivi['vahapaastoisyys']}'>";
  echo "<input type='hidden' name='rekrivi[variantti]'       value='{$rekrivi['variantti']}'>";
  echo "<input type='hidden' name='rekrivi[versio]'         value='{$rekrivi['versio']}'>";
  echo "<input type='hidden' name='rekrivi[moottoritunnus]'    value='{$rekrivi['moottoritunnus']}'>";
  echo "<input type='hidden' name='rekrivi[kayttoonotto]'      value='{$rekrivi['kayttoonotto']}'>";
  echo "<input type='hidden' name='rekrivi[kpl]'          value='{$rekrivi['kpl']}'>";
  echo "<input type='hidden' name='rekrivi[valmistenumero_min]'  value='{$rekrivi['valmistenumero_min']}'>";
  echo "<input type='hidden' name='rekrivi[valmistenumero_max]'  value='{$rekrivi['valmistenumero_max']}'>";
  echo "<input type='hidden' name='rekrivi[rektun]'        value='{$rekrivi['rektun']}'>";
  echo "<input type='hidden' name='tarvitaanmerkki'         value='1'>";
  echo "<input type='hidden' name='rekkari'             value='{$rekkari}'>";
  echo "<input type='submit' value='", t("Näytä kaikki"), " {$rekrivi['merkki']} {$matchlisa} ", t("mallit"), "'>";

  echo "</form>";
}

if ($tee == '') {
  echo "<table>";
  echo "<tr>";
  echo "<th>Maa</th><th>", t("Vuosimalli alku"), "</th><th>", t("Vuosimalli loppu"), "</th><th>", t("Toiminto"), "</th>";
  echo "</tr>";

  echo "<tr><td>";
  echo "<form method='post'>";
  echo "<select name='maa'>";
  echo "<option value='FI'>", t("Suomi"), "</option>";
  echo "<option value='SE'>", t("Ruotsi"), "</option>";
  echo "</select></td><td>";

  echo "<select name='alkuvuosiraja'>";
  echo "<option value=''>", t("Ei rajausta"), "</option>";

  for ($y = date("Y") - 50; $y <= date("Y") + 50; $y++) {
    echo "<option value='{$y}'>{$y}</option>";
  }

  echo "</select></td><td>";

  echo "<select name='loppuvuosiraja'>";
  echo "<option value=''>", t("Ei rajausta"), "</option>";

  for ($y = date("Y")-50; $y <= date("Y")+50; $y++) {
    echo "<option value='{$y}'>{$y}</option>";
  }

  echo "</select></td><td>";

  echo "<input type='hidden' name='mitka' value='uudet'>";
  echo "<input type='hidden' name='tee' value='valitse'>";
  echo "<input type='submit' value='", t("Liitä uusia"), "'>";
  echo "</form></td>";

  echo "<tr><td>";
  echo "<form method='post'>";
  echo "<select name='maa'>";
  echo "<option value='FI'>", t("Suomi"), "</option>";
  echo "<option value='SE'>", t("Ruotsi"), "</option>";
  echo "</select></td><td>";

  echo "<select name='alkuvuosiraja'>";
  echo "<option value=''>", t("Ei rajausta"), "</option>";

  for ($y = date("Y") - 50; $y <= date("Y") + 50; $y++) {
    echo "<option value='{$y}'>{$y}</option>";
  }

  echo "</select></td><td>";

  echo "<select name='loppuvuosiraja'>";
  echo "<option value=''>", t("Ei rajausta"), "</option>";

  for ($y = date("Y") - 50; $y <= date("Y") + 50; $y++) {
    echo "<option value='{$y}'>{$y}</option>";
  }

  echo "</select></td><td>";

  echo "<input type='hidden' name='mitka' value='hylatyt'>";
  echo "<input type='hidden' name='tee' value='valitse'>";
  echo "<input type='submit' value='", t("Hylätyt"), "'>";
  echo "</form></td>";

  echo "<tr><td>";
  echo "<form method='post'>";
  echo "<select name='maa'>";
  echo "<option value='FI'>", t("Suomi"), "</option>";
  echo "<option value='SE'>", t("Ruotsi"), "</option>";
  echo "</select></td><td></td><td></td><td>";

  echo "<input type='hidden' name='mitka' value='korjaa'>";
  echo "<input type='hidden' name='tee' value='valitse'>";
  echo "<input type='submit' value='", t("Korjaa liitettyjä"), "'>";
  echo "</form></td>";
  echo "</tr>";

  echo "<tr><form method='post'>";
  echo "<td><select name='maa'><option value='FI'>", t("Suomi"), "</option><option value='SE'>", t("Ruotsi"), "</option></select></td><td>&nbsp;</td><td>&nbsp;</td><td>";
  echo "<input type='hidden' name='mitka' value='liita_rekkari' />";
  echo "<input type='hidden' name='tee' value='valitse' />";
  echo "<input type='submit' value='", t("Liitä rekkari"), "' />";
  echo "</td></form></tr>";

  echo "</table>";
  echo "<br>";
}

require "inc/footer.inc";
