<?php

require "../inc/parametrit.inc";

echo "<font class='head'>", t("Vaihda tilauksen tila"), ":<hr></font>";

if (!isset($tunnus)) $tunnus = "";
if (!isset($tee)) $tee = "";

// sallitaan vain numerot 0-9
$tunnus = preg_replace("/[^0-9]/", "", $tunnus);

if ($tunnus != "" and $tee == "vaihda") {

  $tila_query  = "SELECT *
                  FROM lasku
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tila    IN ('L','N','A','V','C')
                  AND tunnus  = '{$tunnus}'";
  $tila_result = pupe_query($tila_query);

  if (mysql_num_rows($tila_result) == 1) {
    $tila_row = mysql_fetch_assoc($tila_result);

    // lock tables
    $query = "LOCK TABLES lasku WRITE,
              sanakirja WRITE,
              tilausrivi WRITE,
              rahtikirjat WRITE,
              tuote WRITE,
              sarjanumeroseuranta WRITE,
              kerayserat WRITE,
              sarjanumeroseuranta_arvomuutos READ,
              avainsana as avainsana_kieli READ";
    $locre = pupe_query($query);

    if ($tila_row['tila'] == "C") {
      if ($tila == "3") {
        $query = "UPDATE tilausrivi SET
                  keratty        = '',
                  kerattyaika    = '',
                  toimitettu     = '',
                  toimitettuaika = ''
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otunnus    = '{$tunnus}'";
        $tila_result = pupe_query($query);

        $query = "UPDATE lasku SET
                  tila        = 'C',
                  alatila     = 'B'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$tunnus}'";
        $tila_result = pupe_query($query);

        $query = "DELETE FROM rahtikirjat
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otsikkonro = '{$tunnus}'";
        $tila_result = pupe_query($query);
      }
    }
    else {
      // lähete tulostettu
      if ($tila == "3") {
        $query = "UPDATE tilausrivi SET
                  keratty        = '',
                  kerattyaika    = '',
                  toimitettu     = '',
                  toimitettuaika = ''
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otunnus    = '{$tunnus}'";
        $tila_result = pupe_query($query);

        if ($tila_row["tila"] == "V") {
          $uustila = "V";
        }
        else {
          $uustila = "L";
        }

        $query = "UPDATE lasku SET
                  tila        = '{$uustila}',
                  alatila     = 'A'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$tunnus}'";
        $tila_result = pupe_query($query);

        $query = "DELETE FROM rahtikirjat
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otsikkonro = '{$tunnus}'";
        $tila_result = pupe_query($query);
      }

      // tilaus kerätty
      if ($tila == "4") {
        $query = "UPDATE tilausrivi SET
                  toimitettu     = '',
                  toimitettuaika = ''
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otunnus    = '{$tunnus}'";
        $tila_result = pupe_query($query);

        if ($tila_row["tila"] == "V") {
          $uustila = "V";
        }
        else {
          $uustila = "L";
        }

        $query = "UPDATE lasku SET
                  tila        = '{$uustila}',
                  alatila     = 'C'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$tunnus}'";
        $tila_result = pupe_query($query);

        $query = "DELETE FROM rahtikirjat
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otsikkonro = '{$tunnus}'";
        $tila_result = pupe_query($query);
      }

      // rahtikirjatiedot syötetty
      if ($tila == "5") {
        $query = "UPDATE tilausrivi SET
                  toimitettu     = '',
                  toimitettuaika = ''
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otunnus    = '{$tunnus}'";
        $tila_result = pupe_query($query);

        $query = "UPDATE lasku SET
                  tila        = 'L',
                  alatila     = 'B'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$tunnus}'";
        $tila_result = pupe_query($query);

        $query = "UPDATE rahtikirjat
                  SET tulostettu = ''
                  WHERE yhtio    = '{$kukarow['yhtio']}'
                  AND otsikkonro = '{$tunnus}'";
        $tila_result = pupe_query($query);
      }
    }

    // tilaus kesken
    // tai tilaus lavakeraysjonossa
    if ($tila == "1" or $tila == "LJ") {
      $query = "UPDATE tilausrivi SET
                keratty        = '',
                kerattyaika    = '',
                toimitettu     = '',
                toimitettuaika = ''
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND otunnus    = '{$tunnus}'";
      $tila_result = pupe_query($query);

      if ($tila_row["tila"] == "V") {
        $uustila = "V";
      }
      elseif ($tila_row["tilaustyyppi"] == "A") {
        $uustila = "A";
      }
      elseif ($tila_row["tila"] == "C") {
        $uustila = "C";
      }
      else {
        $uustila = "N";
      }

      $alatila = "";

      if ($tila == "LJ") {
        $alatila = "FF";
      }

      $query = "UPDATE lasku SET
                tila        = '{$uustila}',
                alatila     = '{$alatila}',
                viite       = ''
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$tunnus}'";
      $tila_result = pupe_query($query);

      $query = "DELETE FROM kerayserat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$tunnus}'";
      $tila_result = pupe_query($query);

      $query = "DELETE FROM rahtikirjat
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND otsikkonro = '{$tunnus}'";
      $tila_result = pupe_query($query);
    }

    // tilaus tulostusjonossa
    if ($tila == "2") {
      $query = "UPDATE tilausrivi SET
                keratty        = '',
                kerattyaika    = '',
                toimitettu     = '',
                toimitettuaika = ''
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND otunnus    = '{$tunnus}'";
      $tila_result = pupe_query($query);

      if ($tila_row["tila"] == "V") {
        $uustila   = "V";
        $uusalatila = "J";
      }
      elseif ($tila_row["tila"] == "C") {
        $uustila   = "C";
        $uusalatila = "A";
      }
      else {
        $uustila   = "N";
        $uusalatila = "A";
      }

      $query = "UPDATE lasku SET
                tila        = '{$uustila}',
                alatila     = '{$uusalatila}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$tunnus}'";
      $tila_result = pupe_query($query);

      $query = "DELETE FROM kerayserat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$tunnus}'";
      $tila_result = pupe_query($query);

      $query = "DELETE FROM rahtikirjat
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND otsikkonro = '{$tunnus}'";
      $tila_result = pupe_query($query);
    }

    // mitätöi
    if ($tila == "999") {

      $query = "UPDATE tilausrivi SET
                tyyppi      = 'D'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$tunnus}'";
      $tila_result = pupe_query($query);

      $query = "UPDATE lasku SET
                tila         = 'D',
                alatila      = tila,
                comments     = '{$kukarow['nimi']} ({$kukarow['kuka']}) ".t("mitätöi tilauksen ohjelmassa vaihda_tila.php")." ".date("d.m.y @ G:i:s")."'
                 WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus   = '{$tunnus}'";
      $tila_result = pupe_query($query);

      $query = "DELETE FROM rahtikirjat
                WHERE yhtio    = '{$kukarow['yhtio']}'
                AND otsikkonro = '{$tunnus}'";
      $tila_result = pupe_query($query);

      //Nollataan sarjanumerolinkit
      $query = "SELECT tilausrivi.tunnus, (tilausrivi.varattu + tilausrivi.jt) varattu
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.sarjanumeroseuranta != '')
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus = '{$tunnus}'";
      $sres = pupe_query($query);

      while ($srow = mysql_fetch_assoc($sres)) {

        if ($srow["varattu"] > 0) {
          $tunken = "myyntirivitunnus";
        }
        else {
          $tunken = "ostorivitunnus";
        }

        $query = "UPDATE sarjanumeroseuranta SET
                  {$tunken} = 0
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND {$tunken} = '{$srow['tunnus']}'";
        $sarjares = pupe_query($query);
      }
    }

    // poistetaan lukot
    $query = "UNLOCK TABLES";
    $locre = pupe_query($query);
  }

  $tee = "valitse";
}

if ($tunnus != "" and $tee == "valitse") {

  $tila_query  = "SELECT *
                  FROM lasku
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tila    in ('L','N','A','V','C')
                  AND tunnus  = '{$tunnus}'";
  $tila_result = pupe_query($tila_query);

  if (mysql_num_rows($tila_result) == 1) {
    $tila_row = mysql_fetch_assoc($tila_result);

    // vain laskuttamattomille myyntitilaukille voi tehdä jotain
    if (  ($tila_row["tila"] == "L" and $tila_row["alatila"] != "X") or
      ($tila_row["tila"] == "N" and in_array($tila_row["alatila"], array('A', '', 'FF'))) or
      ($tila_row["tila"] == "V" and in_array($tila_row["alatila"], array('', 'A', 'J', 'C'))) or
      ($tila_row["tila"] == "C" and in_array($tila_row["alatila"], array('', 'A', 'B', 'C')))) {

      echo "<form method='post'>";
      echo "<input type='hidden' name='parametrit' value='{$parametrit}' />";
      echo "<input type='hidden' name='tee' value='vaihda' />";
      echo "<input type='hidden' name='tunnus' value='{$tila_row['tunnus']}' />";

      echo "<table><tr>";
      echo "<th>", t("Vaihda tilauksen tila"), ": </th>";
      echo "<td><select name='tila'>";
      echo "<option value = ''>", t("Valitse uusi tila"), "</option>";
      echo "<option value = '999'>", t("Mitätöity"), "</option>";

      if ($tila_row['tila'] == "C") {

        if ($tila_row["alatila"] != "") {
          echo "<option value = '1'>", t("Reklamaatio kesken"), "</option>";
        }

        if ($yhtiorow['reklamaation_kasittely'] == 'U') {
          if (in_array($tila_row["alatila"], array('B', 'C'))) {
            echo "<option value = '2'>", t("Reklamaatio odottaa tuotteita"), "</option>";
          }
          if ($tila_row["alatila"] == "C") {
            echo "<option value = '3'>", t("Reklamaatio vastaanotettu"), "</option>";
          }
        }
      }
      else {

        if ($tila_row["alatila"] != "") {
          echo "<option value = '1'>", t("Tilaus kesken"), "</option>";
        }

        $asq = "SELECT kerayserat
                FROM asiakas
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$tila_row['liitostunnus']}'";
        $asr = pupe_query($asq);
        $asiakas_row = mysql_fetch_assoc($asr);

        if ($asiakas_row['kerayserat'] == "H" and $tila_row["alatila"] != 'FF') {
          echo "<option value = 'LJ'>", t("Lavakeräysjonoon"), "</option>";
        }

        if (($tila_row["tila"] == "L" or $tila_row["tila"] == "V") and in_array($tila_row["alatila"], array('A', 'B', 'C', 'D'))) {
          echo "<option value = '2'>", t("Tilaus tulostusjonossa"), "</option>";
        }
        if (in_array($tila_row["alatila"], array('B', 'C', 'D', 'E'))) {
          echo "<option value = '3'>", t("Keräyslista tulostettu"), "</option>";
        }
        if (in_array($tila_row["alatila"], array('B', 'D', 'E'))) {
          echo "<option value = '4'>", t("Tilaus kerätty"), "</option>";
        }
        if (in_array($tila_row["alatila"], array('D'))) {
          echo "<option value = '5'>", t("Rahtikirjatiedot syötetty"), "</option>";
        }

      }

      echo "</select></td>";
      echo "<td class='back'><input type='submit' value='", t("Vaihda tila"), "'></td>";
      echo "</form>";

      echo "</tr>";
      echo "</table><br>";
    }

    require "raportit/naytatilaus.inc";

    echo "<form method='post'>";
    echo "<input type='hidden' name='parametrit' value='{$parametrit}' />";
    echo "<td class='back'><input type='submit' value='", t("Peruuta"), "'></td>";
    echo "</form>";

  }
  else {
    echo "<font class='error'>", t("Tilausta ei löydy"), "!</font>";
    $tee = "";
  }

}

if ($tee == "") {
  echo "<form method='post'>";
  echo "<input type='hidden' name='tee' value='valitse'>";
  echo "<table>";
  echo "<tr>";
  echo "<th>", t("Anna tilausnumero"), ":</th>";
  echo "<td><input type='text' name='tunnus' value='' /></td>";
  echo "<td class='back'><input type='submit' value='", t("Hae"), "' /></td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";
}

require "inc/footer.inc";
