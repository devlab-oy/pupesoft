<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t('Matkalasku / kulukorvaus')."</font><hr>";

if ($tee == "VALMIS") {
  $query = "SELECT tunnus, viite
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            and tunnus  = '{$tilausnumero}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  if (empty($row['viite'])) {
    echo "<br><font class='error'>".t('VIRHE: Viite on tyhjä, tämä on pakollinen kenttä')."!</font><br><br>";

    $tee = 'MUOKKAA';
  }
  else {
    $query = "UPDATE lasku SET
              alatila          = ''
              WHERE yhtio      = '$kukarow[yhtio]'
              and tunnus       = '$tilausnumero'
              and tila         = 'H'
              and tilaustyyppi = 'M'
              and alatila      = 'M'";
    $updres = pupe_query($query);

    $tee = "";
    $tunnus = 0;
    $tilausnumero = 0;

    # Refreshataan
    echo "<script>setTimeout(\"window.location.href='{$palvelin2}matkalasku.php?toim=$toim'\", 0);</script>";
    exit;
  }
}

if ($tee == "UUSI") {

  //  tarkastetaan että käyttäjälle voidaan perustaa matkalaskuja
  if ($toim == "SUPER" and $kayttaja != "") {
    $kayttaja_tsk = $kayttaja;
  }
  else {
    $kayttaja_tsk = $kukarow["kuka"];
  }

  $query = "SELECT toimi.*, kuka.kuka kuka, kuka.nimi kayttajanimi
            FROM toimi
            JOIN kuka ON (kuka.yhtio = toimi.yhtio and kuka.kuka = toimi.nimi)
            WHERE toimi.yhtio = '$kukarow[yhtio]'
            and toimi.nimi    = '$kayttaja_tsk'
            and toimi.tyyppi  = 'K'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {
    $trow = mysql_fetch_assoc($result);
  }
  else {
    if ($toim == "SUPER" and $kayttaja != "") {
      echo "<font class='error'>".t("VIRHE: Henkilölle ei voida perustaa matkalaskua")."!</font>";
      $tee = "";
    }
    else {
      echo "<font class='error'>".t("VIRHE: Matkustaja puuttuu Matkalaskukäyttäjärekisteristä")."!</font>";
      $tee = "";
    }
  }

  /*
    Täältä löytyy kaikki verottajan ulkomaanpäivärahat, sekä ohjeet niiden käsittelyyn
    http://www.vero.fi/fi-FI/Henkiloasiakkaat/Tyosuhde/Verohallinnon_paatos_verovapaista_matkak(12356)
    (tai hakusanalla päivärahat yyyy)
  */

  //  Tarkastetaan että päivärahat löytyy. Puolipäivä rahat löytyy päivärahan kanssa samalta riviltä
  $query = "SELECT tuote.tuoteno
            FROM tuote
            JOIN tili ON tili.yhtio = tuote.yhtio and tili.tilino = tuote.tilino
            WHERE tuote.yhtio      = '$kukarow[yhtio]'
            and tuote.tuotetyyppi  = 'A'
            and tuote.tuoteno      like 'PR%'
            and tuote.status      != 'P'
            and tuote.tilino      != ''
            and tuote.vienti      != '{$yhtiorow['maa']}'
            LIMIT 1";
  $result1 = pupe_query($query);

  if (mysql_num_rows($result1) == 0) {
    echo "<font class='error'>".t("VIRHE: Ulkomaanpäivärahat puuttuu")."!</font>";
    $tee = "";
  }

  $query = "SELECT tuote.tuoteno, tuote.nimitys, tuote.myyntihinta, tuote.malli, tuote.myymalahinta
            FROM tuote
            JOIN tili ON tili.yhtio = tuote.yhtio and tili.tilino = tuote.tilino
            WHERE tuote.yhtio      = '$kukarow[yhtio]'
            and tuote.tuotetyyppi  = 'A'
            and tuote.tuoteno      like 'PR%'
            and tuote.status      != 'P'
            and tuote.tilino      != ''
            and tuote.vienti       = '{$yhtiorow['maa']}'
            order by tuote.tunnus desc
            LIMIT 1";
  $result1 = pupe_query($query);
  $vtrow = mysql_fetch_assoc($result1);

  if (mysql_num_rows($result1) == 0) {
    echo "<font class='error'>".t("VIRHE: Kotimaanpäivärahat puuttuu")."!</font>";
    $tee = "";
  }
  elseif ($vtrow['nimitys'] == '' or $vtrow['myyntihinta'] == 0 or $vtrow['malli'] == '' or $vtrow['myymalahinta'] == 0) {
    echo "<font class='error'>".t("VIRHE: Kotimaanpäivärahan tiedot puutteelliset")."!</font>";
    $tee = "";
  }
}

if ($tee == "UUSI") {
  if ($ytunnus != '' or isset($EI_ASIAKASTA_X)) {

    if ($ytunnus != '') {
      require "inc/asiakashaku.inc";
      unset($EI_ASIAKASTA_X);
    }
    else {
      $asiakasrow = array();
    }

    if ($asiakasid > 0 or isset($EI_ASIAKASTA_X)) {

      if ($trow["oletus_erapvm"] > 0) $erpaivia = $trow["oletus_erapvm"];
      else $erpaivia = 1;

      // Katsotaan onko meillä "tuuraajia" hyväksynnässä
      for ($tuuraaja_i = 2; $tuuraaja_i < 6; $tuuraaja_i++) {
        $query = "SELECT if (tuuraaja != '', tuuraaja, kuka) kuka
                  FROM kuka
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND kuka     = '{$trow['oletus_hyvak'.$tuuraaja_i]}'
                  AND kuka    != ''";
        $result = pupe_query($query);
        $hyvak_row = mysql_fetch_assoc($result);

        $trow['oletus_hyvak'.$tuuraaja_i] = $hyvak_row['kuka'];
      }

      // haetaan seuraava vapaa matkalaskunumero
      $query  = "SELECT max(laskunro) laskunro
                 FROM lasku
                 WHERE yhtio      = '$kukarow[yhtio]'
                 and tila         IN ('H','Y','M','P','Q')
                 and alatila      IN ('','M','H')
                 and tilaustyyppi = 'M'";
      $result = pupe_query($query);
      $row    = mysql_fetch_assoc($result);
      $maxmatkalasku = $row["laskunro"]+1;

      // Perustetaan lasku
      $query = "INSERT into lasku set
                yhtio             = '$kukarow[yhtio]',
                valkoodi          = '{$yhtiorow['valkoodi']}',
                hyvak1            = '$trow[kuka]',
                hyvaksyja_nyt     = '$trow[kuka]',
                hyvak2            = '$trow[oletus_hyvak2]',
                hyvak3            = '$trow[oletus_hyvak3]',
                hyvak4            = '$trow[oletus_hyvak4]',
                hyvak5            = '$trow[oletus_hyvak5]',
                ytunnus           = '$trow[ytunnus]',
                toim_ovttunnus    = '$trow[kuka]',
                tilinumero        = '$trow[tilinumero]',
                ultilno           = '$trow[ultilno]',
                nimi              = '$trow[kayttajanimi]',
                nimitark          = '".t("Matkalasku")."',
                osoite            = '$trow[osoite]',
                osoitetark        = '$trow[osoitetark]',
                postino           = '$trow[postino]',
                postitp           = '$trow[postitp]',
                maa               = '$trow[maa]',
                toim_maa          = '$trow[verovelvollinen]',
                ultilno_maa       = '$trow[ultilno_maa]',
                vienti            = 'A',
                ebid              = '',
                tila              = 'H',
                alatila           = 'M',
                swift             = '$trow[swift]',
                pankki1           = '$trow[pankki1]',
                pankki2           = '$trow[pankki2]',
                pankki3           = '$trow[pankki3]',
                pankki4           = '$trow[pankki4]',
                vienti_kurssi     = '1',
                laatija           = '$kukarow[kuka]',
                luontiaika        = now(),
                tapvm             = current_date,
                erpcm             = date_add(current_date, INTERVAL $erpaivia day),
                liitostunnus      = '$trow[tunnus]',
                hyvaksynnanmuutos = '$trow[oletus_hyvaksynnanmuutos]',
                suoraveloitus     = '',
                tilaustyyppi      = 'M',
                laskunro          = '$maxmatkalasku'";
      $result = pupe_query($query);
      $tilausnumero = mysql_insert_id($GLOBALS["masterlink"]);

      //  Tänne voisi laittaa myös tuon asiakasidn jos tästä voitaisiin lähettää myös lasku asiakkaalle
      $query = "INSERT into laskun_lisatiedot set
                yhtio             = '$kukarow[yhtio]',
                laskutus_nimi     = '$asiakasrow[nimi]',
                laskutus_nimitark = '".t("Matkalasku")."',
                laskutus_osoite   = '$asiakasrow[osoite]',
                laskutus_postino  = '$asiakasrow[postino]',
                laskutus_postitp  = '$asiakasrow[postitp]',
                laskutus_maa      = '$asiakasrow[maa]',
                otunnus           = '$tilausnumero'";
      $result = pupe_query($query);

      $tee = "MUOKKAA";
    }
    else {
      $tee = "";
    }
  }
  else {
    echo "<font class='error'>".t("VIRHE: Anna asiakkaan nimi")."</font><br>";
    $tee = "";
  }
}

if ($tee != "") {
  $muokkauslukko = TRUE;

  if ((int) $tilausnumero == 0) {
    echo "<font class='error'>".t("VIRHE: Matkalaskun numero kateissa")."!</font>";
    $tee = "";
  }
  else {
    $query = "SELECT lasku.*,
              laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa,
              toimi.kustannuspaikka, toimi.kohde, toimi.projekti
              FROM lasku
              LEFT JOIN laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
              JOIN toimi on (toimi.yhtio = lasku.yhtio and toimi.tunnus = lasku.liitostunnus)
              WHERE lasku.tunnus     = '$tilausnumero'
              AND lasku.yhtio        = '$kukarow[yhtio]'
              AND lasku.tilaustyyppi IN ('M', '')
              AND lasku.tila         IN ('H','Y','M','P','Q')";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      echo "<font class='error'>".t("VIRHE: Matkalaskun numero kateissa")."!</font>";
      $tee = "";
    }
    else {
      $laskurow = mysql_fetch_assoc($result);

      if ($laskurow["tila"] == "H" and
        ($laskurow["h1time"] == "0000-00-00 00:00:00" or ($laskurow['hyvak2'] == $kukarow['kuka'] and $laskurow["h2time"] == "0000-00-00 00:00:00" and $yhtiorow['matkalaskun_tarkastus'] != '')) and
        ($laskurow["toim_ovttunnus"] == $kukarow["kuka"] or $toim == "SUPER") and
        strtotime($laskurow["tapvm"]) >= strtotime($yhtiorow["ostoreskontrakausi_alku"]) and
        strtotime($laskurow["tapvm"]) <= strtotime($yhtiorow["ostoreskontrakausi_loppu"])
      ) {
        $muokkauslukko = FALSE;
      }
      else {
        $tee = "MUOKKAA";
      }
    }
  }
}

if ($tee == "POISTA" and !$muokkauslukko) {
  $tunnus  = $tilausnumero;
  $tee    = "D";
  $kutsuja = "MATKALASKU";

  require "hyvak.php";

  $query = "DELETE
            FROM tilausrivi
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND otunnus  = '{$tilausnumero}'
            AND perheid2 = '{$perheid2}'";
  pupe_query($query);

  $tee       = "";
  $tunnus     = 0;
  $tilausnumero = 0;
}

if ($tee == "UUDELLEENKASITTELE" and $toim == "SUPER" and !$muokkauslukko) {

  echo "<font class='message'>".t("Poistetaan vanhat tiliöinnit ja laskut").".</font><br><br>";

  $query = "DELETE FROM tilausrivi
            WHERE yhtio = '$kukarow[yhtio]'
            AND otunnus = '$tilausnumero'
            AND tyyppi  = 'M'";
  $result = pupe_query($query);

  $tee = "ERITTELE";
}

if ($tee == "ERITTELE" and !$muokkauslukko) {

  //  Onko tässä jo jotain tilausrivejä?
  $query = "SELECT tunnus
            FROM tilausrivi
            WHERE yhtio = '$kukarow[yhtio]'
            and otunnus = '$tilausnumero'
            and tyyppi  = 'M'
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {

    $hinta = (float) erittele_rivit($tilausnumero);

    //  Lisätään korko ja korjataan ostovelka
    if ($hinta > 0) {
      $tuoteno = "KORKO";
      $lisaa    = "JOO";
      $kpl    = 1;
      $tyyppi  = "B";
    }
  }

  $tee = "MUOKKAA";
}

if ($tee == "TUO_KALENTERISTA" and !$muokkauslukko) {

  //  Onko tässä jo jotain tilausrivejä?
  $query = "SELECT kalenteri.*, asiakas.maa, concat_ws(' ', asiakas.nimi, asiakas.nimitark) asiakas
            FROM kalenteri
            LEFT JOIN asiakas ON asiakas.yhtio = kalenteri.yhtio and asiakas.tunnus = kalenteri.liitostunnus
            WHERE kalenteri.yhtio = '$kukarow[yhtio]' and (kentta10 = 'M' or kentta10 = '$tilausnumero') and kalenteri.kuka = '$kukarow[kuka]' and pvmloppu <= now()";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_assoc($result)) {

      $tapahtuma = "$row[tapa]: $row[pvmalku] - $row[pvmloppu]";

      if ($row["maa"] == "") $row["maa"] = "FI";

      $query = "SELECT tuoteno
                FROM tuote
                WHERE yhtio      = '$kukarow[yhtio]'
                and tuotetyyppi  = 'A'
                and status      != 'P'
                and vienti       = '$row[maa]'";
      $tres = pupe_query($query);

      if (mysql_num_rows($tres) > 0) {
        $trow = mysql_fetch_assoc($tres);
        $errori = lisaa_paivaraha($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $trow["tuoteno"], $row["pvmalku"], $row["pvmloppu"], "Asiakas: $row[asiakas]\nTapahtuma: $row[tapa]", "", "", "");

        if ($errori == "") {
          $query = "UPDATE kalenteri SET
                    kentta10    = '$tilausnumero'
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$row[tunnus]'";
          $updres = pupe_query($query);
        }
        else {
          echo "<font class='message'>".t("Tapahtumaa ei voitu siirtää.")." '$tapahtuma'</font><br>$errori<br><br>";
        }
      }
      else {
        echo "<font class='error'>".t("Siirto epäonnistui sopivaa päivärahaa ei löytynyt.")." '$tapahtuma'</font><br>";
      }
    }
  }
  else {
    echo "<font class='error'>".t("Sinulla ei ollut siirrettäviä kalenteritapahtumia.")."</font><br><br>";
  }

  $tee = "MUOKKAA";
}

if ($tee == "POIMI_KALENTERISTA" and !$muokkauslukko) {
  function erittele_rivit($tilausnumero) {
    global $kukarow, $yhtiorow, $verkkolaskuvirheet_ok;

    $query = "SELECT *
              FROM kalenteri
              WHERE yhtio='$kukarow[yhtio]' and kentta10='M'";
    $result = pupe_query($query);
    $row = mysql_fetch_assoc($result);

    $query = "DELETE FROM tiliointi
              WHERE yhtio='$kukarow[yhtio]' and ltunnus = '$tilausnumero'";
    $result = pupe_query($query);

    $file = $verkkolaskuvirheet_ok."/$laskurow[ebid]";
  }
}

if ($tee == "TALLENNA" and !$muokkauslukko) {
  if ((int) $tilausnumero == 0) {
    echo "<font class='error'>".t("Matkalaskun numero puuttuu")."</font>";
    $tee = "";
  }
  else {

    $errormsg = "";

    //  Koitetaan tallennella kuva
    if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
      if ($kuvaselite == "") {
        $errormsg = t("Anna kuvalle selite");
      }
      else {
        //  chekataan erroit
        switch ($_FILES['userfile']['error']) {
        case 1:
        case 2:
          $errormsg .= t("Kuva on liian suuri, suurin sallittu koko on")." ".ini_get('post_max_size');
          break;
        case 3:
          $errormsg .= t("Kuvan lataus keskeytyi")."!";
          break;
        case 6:
        case 7:
        case 8:
          $errormsg .= t("Tallennus epäonnistui")."!";
          break;
        case 0:
          //  OK tallennetaan

          // otetaan file extensio
          $path_parts = pathinfo($_FILES['userfile']['name']);
          $ext = $path_parts['extension'];
          if (strtoupper($ext) == "JPEG") $ext = "jpg";

          $query = "SHOW variables like 'max_allowed_packet'";
          $result = pupe_query($query);
          $varirow = mysql_fetch_row($result);

          if ($_FILES['userfile']['size'] > $varirow[1]) {
            $errormsg .= "<font class='error'>".t("Liitetiedosto on liian suuri")."! ($varirow[1]) </font>";
          }
          else {
            // lisätään kuva
            $kuva = tallenna_liite("userfile", "lasku", $tilausnumero, $kuvaselite, "", 0, 0, "");
          }
          break;
        }
      }
    }

    if (isset($skannattukuitti) and count($skannattukuitti) > 0) {

      if ($kuvaselite == "") {
        $errormsg = t("Anna kuvalle selite");
      }
      else {
        $query = "SHOW variables like 'max_allowed_packet'";
        $result = pupe_query($query);
        $varirow = mysql_fetch_row($result);

        $dir = $yhtiorow['skannatut_laskut_polku']."/matkalaskut/$laskurow[toim_ovttunnus]";

        foreach ($skannattukuitti as $kuitti) {
          if (filesize($dir."/".$kuitti) > $varirow[1]) {
            $errormsg .= "<font class='error'>".t("Liitetiedosto on liian suuri")."! ($varirow[1]) </font>";
          }
          else {
            // lisätään kuva
            $kuva = tallenna_liite($dir."/".$kuitti, "lasku", $tilausnumero, $kuvaselite, "", 0, 0, "");

            if ((int) $kuva > 0) {
              unlink($dir."/".$kuitti);
            }
          }
        }
      }
    }

    if ($errormsg != "") {
      echo "<font class='error'>$errormsg</font><br>";
    }
    else {
      $kuvaselite = "";
    }

    $query = "UPDATE lasku SET
              viite       = '$viesti'
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$tilausnumero'";
    $updres = pupe_query($query);

    $laskurow["viite"] = $viesti;

    $tee = "MUOKKAA";
  }
}

if ($tee == 'TARKISTA_ILMAISET_LOUNAAT' and !$muokkauslukko) {

  //nimitys = kokopäivärahan nimitys
  //myyntihinta = kokopäivärahan hinta
  //malli = osapäivärahan nimitys
  //myymalahinta = osapäivärahan hinta

  //var = 1 Kotimaan kokopäiväraha
  //var = 2 Kotimaan osapäiväraha
  //var = 3 Kotimaan puolitettu kokopäiväraha
  //var = 4 Kotimaan puolitettu osapäiväraha
  //var = 5 Ulkomaan kokopäiväraha
  //var = 6 Ulkomaan puolitettu päiväraha
  //var = 7 Ulkomaan kahteenkertaan puolitettu päiväraha
  $query = "SELECT
            tilausrivi.tunnus,
            tilausrivi.alv,
            tilausrivi.kpl,
            tilausrivi.nimitys tilausrivi_nimitys,
            tilausrivi.hinta,
            tilausrivi.var,
            tilausrivi.rivihinta,
            tilausrivi.otunnus,
            tilausrivi.erikoisale,
            tuote.nimitys,
            tuote.myyntihinta,
            tuote.malli,
            tuote.myymalahinta,
            tuote.vienti,
            tili.tilino,
            lasku.tapvm
            FROM tilausrivi
            JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
            JOIN tili ON (tili.yhtio = tuote.yhtio and tili.tilino = tuote.tilino)
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
            AND tilausrivi.tunnus  = '$rivitunnus'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  $ilmaiset_lounaat = (int) trim($ilmaiset_lounaat);

  if ($row['vienti'] == 'FI') {
    if ($ilmaiset_lounaat >= 1) {
      if ($row['var'] == 1 and $ilmaiset_lounaat >= 2) {
        //kotimaan kokopäiväraha ----> kotimaan puolitettu kokopäiväraha
        $rivihinta = $row['kpl'] * ($row['myyntihinta'] / 2);
        $tilausrivi_uusi_hinta = ($row['myyntihinta'] / 2);
        $tilausrivi_uusi_nimitys = $row['tilausrivi_nimitys'] . ' ' . t("Puolitettu korvaus");
        $tilausrivi_uusi_var = 3;
      }
      elseif ($row['var'] == 3 and $ilmaiset_lounaat < 2) {
        //kotimaan puolitettu kokopäiväraha ----> kotimaan kokopäiväraha
        $rivihinta = $row['kpl'] * ($row['myyntihinta']);
        $tilausrivi_uusi_hinta = ($row['myyntihinta']);
        $tilausrivi_uusi_nimitys = $row['nimitys'];
        $tilausrivi_uusi_var = 1;
      }
      elseif ($row['var'] == 2) {
        //kotimaan osapäiväraha ----> kotimaan puolitettu osapäiväraha
        $rivihinta = $row['kpl'] * ($row['myymalahinta'] / 2);
        $tilausrivi_uusi_hinta = ($row['myymalahinta'] / 2);
        $tilausrivi_uusi_nimitys = $row['tilausrivi_nimitys'] . ' ' . t("Puolitettu korvaus");
        $tilausrivi_uusi_var = 4;
      }
      else {
        //pidetään tiedot ennallaan
        $rivihinta = $row['rivihinta'];
        $tilausrivi_uusi_hinta = $row['hinta'];
        $tilausrivi_uusi_nimitys = $row['tilausrivi_nimitys'];
        $tilausrivi_uusi_var = $row['var'];
      }
    }
    else {
      if ($row['erikoisale'] >= 1) {
        if ($row['var'] == 3) {
          //kotimaan puolitettu kokopäiväraha ----> kotimaan kokopäiväraha
          $rivihinta = $row['kpl'] * ($row['myyntihinta']);
          $tilausrivi_uusi_hinta = ($row['myyntihinta']);
          $tilausrivi_uusi_nimitys = $row['nimitys'];
          $tilausrivi_uusi_var = 1;
        }
        elseif ($row['var'] == 4) {
          //kotimaan puolitettu osapäiväraha ----> kotimaan puolitettu osapäiväraha
          $rivihinta = $row['kpl'] * ($row['myymalahinta']);
          $tilausrivi_uusi_hinta = ($row['myymalahinta']);
          $tilausrivi_uusi_nimitys = $row['malli'];
          $tilausrivi_uusi_var = 2;
        }
        else {
          //pidetään tiedot ennallaan
          $rivihinta = $row['rivihinta'];
          $tilausrivi_uusi_hinta = $row['hinta'];
          $tilausrivi_uusi_nimitys = $row['tilausrivi_nimitys'];
          $tilausrivi_uusi_var = $row['var'];
        }
      }
      else {
        //pidetään tiedot ennallaan
        $rivihinta = $row['rivihinta'];
        $tilausrivi_uusi_hinta = $row['hinta'];
        $tilausrivi_uusi_nimitys = $row['tilausrivi_nimitys'];
        $tilausrivi_uusi_var = $row['var'];
      }
    }
  }
  else {
    if (($row['erikoisale'] < 2) and $ilmaiset_lounaat >= 2) {
      if ($row['var'] == 5) {
        //ulkomaan kokopäiväraha ----> ulkomaan puolitettu kokopäiväraha
        $rivihinta = $row['kpl'] * ($row['myyntihinta'] / 2);
        $tilausrivi_uusi_hinta = ($row['myyntihinta'] / 2);
        $tilausrivi_uusi_nimitys = $row['tilausrivi_nimitys'] . ' ' . t("Puolitettu korvaus");
        $tilausrivi_uusi_var = 6;
      }
      elseif ($row['var'] == 6) {
        //ulkomaan puolitettu päiväraha ----> ulkomaan kahteen kertaan puolitettu päiväraha
        $rivihinta = $row['kpl'] * ($row['hinta'] / 2);
        $tilausrivi_uusi_hinta = ($row['hinta'] / 2);
        $tilausrivi_uusi_nimitys = $row['nimitys'] . ' ' . t("Kahteen kertaan puolitettu korvaus");
        $tilausrivi_uusi_var = 7;
      }
      else {
        //pidetään tiedot ennallaan
        $rivihinta = $row['rivihinta'];
        $tilausrivi_uusi_hinta = $row['hinta'];
        $tilausrivi_uusi_nimitys = $row['tilausrivi_nimitys'];
        $tilausrivi_uusi_var = $row['var'];
      }
    }
    else {
      if ($row['erikoisale'] >= 2) {
        if ($row['var'] == 6) {
          //ulkomaan puolitettu kokopäiväraha ----> ulkomaan kokopäiväraha
          $rivihinta = $row['kpl'] * ($row['myyntihinta']);
          $tilausrivi_uusi_hinta = ($row['myyntihinta']);
          $tilausrivi_uusi_nimitys = $row['nimitys'];
          $tilausrivi_uusi_var = 5;
        }
        elseif ($row['var'] == 7) {
          //ulkomaan kahteen kertaan puolitettu päiväraha ----> ulkomaan puolitettu päiväraha
          $rivihinta = $row['kpl'] * ($row['myyntihinta'] / 2);
          $tilausrivi_uusi_hinta = ($row['myyntihinta'] / 2);
          $tilausrivi_uusi_nimitys = $row['nimitys'] . ' ' . t('Puolitettu korvaus');
          $tilausrivi_uusi_var = 6;
        }
        else {
          //pidetään tiedot ennallaan
          $rivihinta = $row['rivihinta'];
          $tilausrivi_uusi_hinta = $row['hinta'];
          $tilausrivi_uusi_nimitys = $row['tilausrivi_nimitys'];
          $tilausrivi_uusi_var = $row['var'];
        }
      }
      else {
        //pidetään tiedot ennallaan
        $rivihinta = $row['rivihinta'];
        $tilausrivi_uusi_hinta = $row['hinta'];
        $tilausrivi_uusi_nimitys = $row['tilausrivi_nimitys'];
        $tilausrivi_uusi_var = $row['var'];
      }
    }
  }

  $query = "UPDATE tilausrivi
            SET nimitys = '{$tilausrivi_uusi_nimitys}',
            hinta       = '{$tilausrivi_uusi_hinta}',
            rivihinta   = '{$rivihinta}',
            erikoisale  = '{$ilmaiset_lounaat}',
            var         = '{$tilausrivi_uusi_var}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$row['tunnus']}'";
  $result = pupe_query($query);

  if ($rivihinta != $row['rivihinta']) {
    // yliviivataan vanha tiliointi rivi
    $query = "SELECT tilausrivitunnus, tiliointirivitunnus
              FROM tilausrivin_lisatiedot
              WHERE yhtio          = '{$kukarow['yhtio']}'
              AND tilausrivitunnus = '{$row['tunnus']}'";
    $result = pupe_query($query);
    $tiliointi_tilausrivi_row = mysql_fetch_assoc($result);

    kopioitiliointi($tiliointi_tilausrivi_row['tiliointirivitunnus'], $kukarow['kuka']);

    $query = "UPDATE tiliointi
              SET summa = '{$rivihinta}',
              laadittu    = now(),
              laatija     = '$kukarow[kuka]'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$tiliointi_tilausrivi_row['tiliointirivitunnus']}'";
    pupe_query($query);

    korjaa_ostovelka($tilausnumero);
  }

  // koska poista edelliset matkalasku rivit update tyyppi D
  // siivotaan deleted tilausrivit pois
  $query = "DELETE FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$tilausnumero}'
             AND tyyppi = 'D'";
  pupe_query($query);

  $tee     = 'MUOKKAA';
  $tapa     = '';
  $kuivat   = '';
  $rivitunnus = '';
  $perheid2   = "";
}

if ($tee == "MUOKKAA") {

  // Onko tosite liitetty saapumiseen
  $query = "SELECT nimi, laskunro
            from lasku
            where yhtio     = '$kukarow[yhtio]'
            and tila        = 'K'
            and vanhatunnus = '$laskurow[tunnus]'";
  $keikres = pupe_query($query);

  if (mysql_num_rows($keikres) > 0) {
    $keikrow = mysql_fetch_assoc($keikres);

    echo "<br><font class='message'>".t("Lasku on liitetty saapumiseen, alv tiliöintejä ei voi muuttaa")."! ".t("Saapuminen").": $keikrow[nimi] / $keikrow[laskunro]</font>";
  }

  if ($poistakuva > 0 and !$muokkauslukko) {
    $query = "DELETE FROM liitetiedostot
              WHERE yhtio      = '$kukarow[yhtio]'
              and liitos       = 'lasku'
              and liitostunnus = '$tilausnumero'
              and tunnus       = '$poistakuva'";
    $result = pupe_query($query);

    if (mysql_affected_rows() == 0) {
      echo "<font class='error'>".t("VIRHE: Poistettavaa liitetiedostoa ei löydy")."!$query</font><br>";
    }

    $poistakuva = 0;
  }

  if ($kuivat != "JOO" and !isset($lisaa) and !$muokkauslukko) {
    //rivitunnus = yhtäkuin ensimmäisen rivin tunnus sekä kaikkien haettavien perheid2. haemme ne rivit ja poistamme ne
    if (!empty($rivitunnus)) {

      if ($perheid2 > 0) {
        $tapahtumarow = hae_ensimmainen_matkalaskurivi($kukarow, $tilausnumero, $perheid2);
      }

      if ($rivitunnus > 0) {
        $tilausrivi = hae_kaikki_matkalaskurivit($kukarow, $tilausnumero, $rivitunnus);
      }

      poista_edelliset_matkalaskurivit($tilausnumero, $rivitunnus, $kukarow);

      if ($tapa == "MUOKKAA") {
        list($pv, $aika) = explode(" ", $tilausrivi["kerattyaika"]);
        list($alkuvv, $alkukk, $alkupp) = explode("-", $pv);
        list($alkuhh, $alkumm) = explode(":", $aika);

        list($pv, $aika) = explode(" ", $tilausrivi["toimitettuaika"]);
        list($loppuvv, $loppukk, $loppupp) = explode("-", $pv);
        list($loppuhh, $loppumm) = explode(":", $aika);

        $kpl     = $tilausrivi["kpl"];
        $vero     = $tilausrivi["tiliointialv"];
        $hinta     = round($tilausrivi["hinta"], 2);
        $kommentti   = $tilausrivi["kommentti"];
        $perheid2   = 0;
        $kustp     = $tilausrivi["kustp"];
        $kohde     = $tilausrivi["kohde"];
        $projekti   = $tilausrivi["projekti"];

        if ($toim == "SUPER") {
          $tilino = $tilausrivi["tiliointitili"];
        }

        //  Otetaan tuote suoraan riviltä
        if ($vaihda_tuote != "") {
          $tuoteno = $vaihda_tuote;
          $tyyppi = $vaihda_tyyppi;
        }
        else {
          $tuoteno = $tilausrivi["tuoteno"];
          $tyyppi = $tilausrivi["tuotetyyppi"];
        }

        $alvulk = $tilausrivi["kulun_kohdemaan_alv"];
        $maa = $tilausrivi["kulun_kohdemaa"];
      }
      else {
        $query = "DELETE
                  FROM tilausrivi
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND otunnus  = '{$tilausnumero}'
                  AND perheid2 = '{$rivitunnus}'";
        pupe_query($query);

        $tyhjenna = "joo";
        unset($tapahtumarow);
        $perheid2 = 0;
      }
    }
  }

  //  Koitetaan lisätä uusi rivi!
  if ($tuoteno != "" and isset($lisaa) and $kuivat != "JOO" and !$muokkauslukko) {
    if ($tyyppi == "A") {
      $errori = lisaa_paivaraha($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, "$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm", "$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm", $kommentti, $kustp, $kohde, $projekti);
    }
    else {
      $errori =
        lisaa_kulu($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $kpl, $vero,
        $hinta, $kommentti, $maa, $kustp, $kohde, $projekti,
        "$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm",
        "$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm");
    }

    if ($errori == "") {
      $tyhjenna = "JOO";
    }
  }

  if ($tyhjenna != "") {
    $tuoteno  = "";
    $tyyppi    = "";
    $kommentti  = "";
    $rivitunnus  = "";
    $perheid2  = "";

    $kpl    = "";
    $hinta    = "";

    unset($alkupp);
    unset($alkukk);
    unset($alkuvv);
    unset($alkuhh);
    unset($alkumm);

    unset($loppupp);
    unset($loppukk);
    unset($loppuvv);
    unset($loppuhh);
    unset($loppumm);

  }

  //  Haetaan tapahtuman rivitiedot jos se on valittu

  // kirjoitellaan otsikko
  echo "<table>";

  echo "<tr>";
  echo "<th align='left'>".t("Henkilö")."</th>";
  echo "<td>$laskurow[nimi]<br>$laskurow[nimitark]<br>$laskurow[osoite]<br>$laskurow[postino] $laskurow[postitp]</td>";
  echo "</tr>";

  if ($laskurow["laskutus_nimi"] != "") {
    echo "<tr>";
    echo "<th align='left'>".t("Asiakas").":</th>";
    echo "<td>$laskurow[laskutus_nimi]<br>$laskurow[laskutus_nimitark]<br>$laskurow[laskutus_osoite]<br>$laskurow[laskutus_postino] $laskurow[laskutus_postitp]</td>";
    echo "</tr>";
  }

  // Näytetäänkö käyttöliittymä
  if (!$muokkauslukko) {

    if ($rivitunnus == "") {
      // tässä alotellaan koko formi.. tämä pitää kirjottaa aina
      echo "  <form action='matkalasku.php' name='tilaus' method='post' autocomplete='off' enctype='multipart/form-data'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='hidden' name='tee' value='TALLENNA'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='tapa' value='$tapa'>";

      if ($laskurow["tilaustyyppi"] == "M") {
        echo "  <tr><th>".t("Viite")."</th>
            <td><input type='text' size='30' maxlength='25' name='viesti' value='$laskurow[viite]'><input type='submit' value='".t("Tallenna viite")."'></td></tr>";
      }
      else {
        echo "  <tr><th>".t("Viite")."</th>
            <td>$laskurow[viite]</td></tr>";
      }

      echo "<tr>";
      echo "<th align='left'>".t("Liitteet")."</th>";

      echo "<td>";

      $query = "SELECT *
                from liitetiedostot
                where yhtio      = '$kukarow[yhtio]'
                and liitos       = 'lasku'
                and liitostunnus = '$tilausnumero'";
      $liiteres = pupe_query($query);

      if (mysql_num_rows($liiteres) > 0) {
        while ($liiterow = mysql_fetch_assoc($liiteres)) {
          echo "<button onclick=\"window.open('".$palvelin2."view.php?id=$liiterow[tunnus]', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=800,height=600'); return false;\">$liiterow[selite]</button>";

          if (!$muokkauslukko) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='matkalasku.php?toim=$toim&tee=$tee&tilausnumero=$tilausnumero&poistakuva=$liiterow[tunnus]'>*".t("poista")."*</a>";
          }

          echo "<br>\n";
        }
      }

      echo "</td></tr>";
      echo "</table>";

      echo "<br>";
      echo "<font class='message'>".t("Lisää kuittikopio tai liite")."</font><hr>";

      echo "<table>";
      echo "<tr>
          <th>".t("Valitse tiedosto")."</th>
          <td><input name='userfile' type='file'></td>
          </tr>";

      $dir = $yhtiorow['skannatut_laskut_polku']."/matkalaskut/$laskurow[toim_ovttunnus]";

      if (is_dir($dir) and is_writable($dir)) {
        // käydään läpi ensin käsiteltävät kuvat
        $files = listdir($dir);

        if (count($files) > 0) {

          echo "<tr>
              <th>".t("Tai valitse skannattu kuitti")."</th>
              <td>";

          foreach ($files as $kuitti) {
            $tiedostonimi = basename($kuitti);

            echo "<input type='checkbox' name='skannattukuitti[]' value='$tiedostonimi'> <button onclick=\"window.open('$kuitti', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=800,height=600'); return false;\">$tiedostonimi</button><br>";
          }

          echo "</td></tr>";
        }
      }

      echo "<th>".t("Liitteen kuvaus")."</th>
          <td><input type='text' size='40' name='kuvaselite' value='$kuvaselite'></td>
          <td class='back'><input type='submit' value='".t("Tallenna liite")."'></td>
          </tr>
          </table><br>";
      echo "</form>";
    }
    else {
      echo "</table><br>";
    }

    if (mysql_num_rows($keikres) == 0 and ($laskurow["tilaustyyppi"] == "M" or $lisaatapahtumaan == "OK" or $toim == "SUPER")) {

      echo "<font class='message'>".t("Lisää uusi kulu")."</font><hr>";

      //  Vaihdettaessa tuotetta pitää pysyä oikeassa tyypissä
      if ($matkalaskupaivat_vain_kalenterista != "") {
        if ($rivitunnus != "") {
          $a = array($tyyppi);
        }
        else {
          $a = array("B");
        }
      }
      else {
        if ($tyyppi != "" and $tuoteno != "") {
          $a = array($tyyppi);
        }
        else {
          $a = array("A", "B");
        }
      }

      echo "<table>";

      foreach ($a as $viranomaistyyppi) {

        $tlisa = "";

        if (isset($tuoteno) and $tuoteno != "") {
          $tlisa = " or tuote.tuoteno='$tuoteno' ";
        }

        $query = "SELECT tuote.tuoteno, tuote.nimitys, tuote.vienti,
                  if (tuote.vienti = '$yhtiorow[maa]' or tuote.nimitys like '%ateria%', 1, if (tuote.vienti != '', 2, 3)) sorttaus
                  FROM tuote
                  JOIN tili ON (tili.yhtio = tuote.yhtio and tili.tilino = tuote.tilino)
                  WHERE tuote.yhtio      = '$kukarow[yhtio]'
                  and tuote.tuotetyyppi  = '$viranomaistyyppi'
                  and (tuote.status != 'P' $tlisa)
                  and tuote.tilino      != ''
                  ORDER BY sorttaus, tuote.nimitys";
        $tres = pupe_query($query);
        $valinta = "";

        if (mysql_num_rows($tres) > 0) {

          if ($rivitunnus > 0) {
            $onchange = "document.getElementById('tuoteno').value=this.value; document.getElementById('kuivat').value='JOO'; document.getElementById('lisaarivi').submit(); return false;";
          }
          else {
            $onchange = "submit();";
          }

          if ($viranomaistyyppi == "A") {
            $valinta = "<tr><th>".t("Päiväraha")."</th>";
          }
          else {
            $valinta = "<tr><th>".t("Muu kulu")."</th>";
          }

          $valinta .= "<td>";
          $valinta .= "<select style=\"width: 350px\" name='tuoteno' $extra onchange=\"$onchange\">";

          if ($tapa != 'MUOKKAA' and $kuivat != 'JOO') {
            $valinta .= "<option value=''>".t("Valitse")."</option>";
          }

          while ($trow = mysql_fetch_assoc($tres)) {

            $trow["nimitys"] = t_tuotteen_avainsanat($trow, 'nimitys', $kukarow["kieli"]);

            if ($trow["tuoteno"] == $tuoteno) {
              $sel = "selected";
            }
            else {
              $sel = "";
            }

            if ($viranomaistyyppi == "A" and $trow["vienti"] != $yhtiorow["maa"] and $trow["vienti"] != '') {
              $valinta .= "<option value='$trow[tuoteno]' $sel>$trow[vienti] - $trow[nimitys]</option>";
            }
            else {
              $valinta .= "<option value='$trow[tuoteno]' $sel>$trow[nimitys]</option>";
            }

          }
          $valinta .= "</select>";
          $valinta .= "</td></tr>";
        }

        if ($valinta != "") {
          echo "<form action='matkalasku.php' method='post' autocomplete='off'>";
          echo "<input type='hidden' name='tee' value='$tee'>";
          echo "<input type='hidden' name='lopetus' value='$lopetus'>";
          echo "<input type='hidden' name='toim' value='$toim'>";
          echo "<input type='hidden' name='perheid2' value='$perheid2'>";
          echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
          echo "<input type='hidden' name='tyyppi' value='$viranomaistyyppi'>";
          echo "$valinta";
          echo "</form>";
        }
      }

      echo "</table>";
    }
  }
  else {
    echo "  <tr><th>".t("Viite")."</th>
        <td>$laskurow[viite]</td></tr>";

    echo "<tr>";
    echo "<th align='left'>".t("Liitteet")."</th>";

    echo "<td>";

    $query = "SELECT *
              from liitetiedostot
              where yhtio      = '$kukarow[yhtio]'
              and liitos       = 'lasku'
              and liitostunnus = '$tilausnumero'";
    $liiteres = pupe_query($query);

    if (mysql_num_rows($liiteres) > 0) {
      while ($liiterow = mysql_fetch_assoc($liiteres)) {
        echo "<button onclick=\"window.open('".$palvelin2."view.php?id=$liiterow[tunnus]', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=800,height=600'); return false;\">$liiterow[selite]</button>";
        echo "<br>\n";
      }
    }

    echo "</td></tr>";
    echo "</table><br>";
  }

  if ($tyyppi != "" and $tuoteno != "" and !$muokkauslukko) {

    $query = "SELECT *
              from tuote
              where yhtio     = '$kukarow[yhtio]'
              and tuoteno     = '$tuoteno'
              and tuotetyyppi = '$tyyppi'";
    $tres = pupe_query($query);

    if (mysql_num_rows($tres) == 1) {
      $trow = mysql_fetch_assoc($tres);
    }
    else {
      die("<font class='error'>".t("VIRHE: Viranomaistuote puuttuu")." (3)</font><br>");
    }

    echo "<br><font class='message'>".t("Lisää")." $trow[nimitys]</font><hr>$errori";
    echo "<form action='matkalasku.php' id='lisaarivi' method='post' autocomplete='off'>";
    echo "<input type='hidden' name='tee' value='$tee'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
    echo "<input type='hidden' id='kuivat' name='kuivat' value=''>";
    echo "<input type='hidden' id='tuoteno' name='tuoteno' value='$tuoteno'>";
    echo "<input type='hidden' name='rivitunnus' value='$rivitunnus'>";
    echo "<input type='hidden' name='perheid2' value='$perheid2'>";
    echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";

    if ($rivitunnus > 0) {
      echo "<font class='error'>".t("HUOM: Jos et lisää riviä se poistetaan erittelystä/matkalaskusta")."</font><br>";
    }

    if ($trow['myymalahinta'] > 0 and strpos($trow['nimitys'], 'Kilometrikorvaus') !== false) {
      $korvatut_kilometrit = hae_matkustajan_kilometrit($tuoteno, $laskurow['toim_ovttunnus']);
      echo "<br><font class='message'>".t("Matkustajalle kirjatut kilometrit").": $korvatut_kilometrit</font><hr>";
    }

    echo "<table><tr>";

    if ($tapa != "MUOKKAA" and $perheid2 > 0) {
      if (!isset($kustp)) {
        $kustp = $tapahtumarow["kustp"];
      }
      if (!isset($kohde)) {
        $kohde = $tapahtumarow["kohde"];
      }
      if (!isset($projekti)) {
        $projekti = $tapahtumarow["projekti"];
      }
      if (!isset($maa)) {
        $maa = $tapahtumarow["maa"];
      }
    }

    //  Tehdään kustannuspaikkamenut
    $query = "SELECT tunnus, nimi, koodi
              FROM kustannuspaikka
              WHERE yhtio   = '$kukarow[yhtio]'
              and kaytossa != 'E'
              and tyyppi    = 'K'
              ORDER BY koodi+0, koodi, nimi";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      $kustannuspaikka = "<select name = 'kustp' style=\"width: 100px\"><option value = ' '>".t("Ei kustannuspaikkaa")."</option>";

      if (!isset($kustp)) {
        if ($trow["kustp"] > 0) {
          $kustp = $trow["kustp"];
        }
        else {
          $kustp = $laskurow["kustannuspaikka"];
        }
      }

      while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
        $valittu = "";

        if ($kustannuspaikkarow["tunnus"] == $kustp) {
          $valittu = "selected";
        }

        $kustannuspaikka .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]</option>";
      }

      $kustannuspaikka .= "</select>";
    }

    $query = "SELECT tunnus, nimi, koodi
              FROM kustannuspaikka
              WHERE yhtio   = '$kukarow[yhtio]'
              and kaytossa != 'E'
              and tyyppi    = 'O'
              ORDER BY koodi+0, koodi, nimi";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      $kustannuspaikka .= " <select name = 'kohde' style=\"width: 100px\"><option value = ' '>".t("Ei kohdetta");

      if ($trow["kohde"] > 0) {
        $kohde = $trow["kohde"];
      }
      else {
        $kohde = $laskurow["kohde"];
      }

      while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
        $valittu = "";
        if ($kustannuspaikkarow["tunnus"] == $kohde) {
          $valittu = "selected";
        }

        $kustannuspaikka .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]</option>";
      }

      $kustannuspaikka .= "</select>";
    }

    $query = "SELECT tunnus, nimi, koodi
              FROM kustannuspaikka
              WHERE yhtio   = '$kukarow[yhtio]'
              and kaytossa != 'E'
              and tyyppi    = 'P'
              ORDER BY koodi+0, koodi, nimi";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      $kustannuspaikka .= " <select name = 'projekti' style=\"width: 100px\"><option value = ' '>".t("Ei projektia");

      if ($trow["projekti"] > 0) {
        $projekti = $trow["projekti"];
      }
      else {
        $projekti = $laskurow["projekti"];
      }

      while ($kustannuspaikkarow = mysql_fetch_assoc($result)) {
        $valittu = "";
        if ($kustannuspaikkarow["tunnus"] == $projekti) {
          $valittu = "selected";
        }
        $kustannuspaikka .= "<option value = '$kustannuspaikkarow[tunnus]' $valittu>$kustannuspaikkarow[koodi] $kustannuspaikkarow[nimi]</option>";
      }

      $kustannuspaikka .= "</select>";
    }

    if (!isset($alkukk)) $alkukk = date("m");
    if (!isset($alkuvv)) $alkuvv = date("Y");

    if (!isset($loppukk)) $loppukk = date("m");
    if (!isset($loppuvv)) $loppuvv = date("Y");

    if ($tyyppi == "A") {
      echo "<th>".t("Kustannuspaikka")."</th><th>".t("Alku")."</th><th>".t("Loppu")."</th><th>".t("Hinta")."</th></tr>";
      echo "<tr><td>";

      if ($kustannuspaikka != "") {
        echo $kustannuspaikka;
      }
      else {
        echo t("Ei kustannuspaikkaa");
      }

      echo "</td><td><input type='text' name='alkupp' value='$alkupp' size='3' maxlength='2'> <input type='text' name='alkukk' value='$alkukk' size='3' maxlength='2'> <input type='text' name='alkuvv' value='$alkuvv' size='5' maxlength='4'> ".t("klo").":<input type='text' name='alkuhh' value='$alkuhh' size='3' maxlength='2'>:<input type='text' name='alkumm' value='$alkumm' size='3' maxlength='2'>&nbsp;</td>
          <td>&nbsp;<input type='text' name='loppupp' value='$loppupp' size='3' maxlength='2'> <input type='text' name='loppukk' value='$loppukk' size='3' maxlength='2'> <input type='text' name='loppuvv' value='$loppuvv' size='5' maxlength='4'> ".t("klo").":<input type='text' name='loppuhh' value='$loppuhh' size='3' maxlength='2'>:<input type='text' name='loppumm' value='$loppumm' size='3' maxlength='2'></td><td align='center'>$trow[myyntihinta]</td>";

      $cols = 4;
      $leveys = 80;
    }
    elseif ($tyyppi == "B") {
      $alkumm = isset($alkumm) ? $alkumm : date("i");
      $alkuhh = isset($alkuhh) ? $alkuhh : date("H");
      $alkupp = isset($alkupp) ? $alkupp : date("d");

      $loppumm = isset($loppumm) ? $loppumm : date("i");
      $loppuhh = isset($loppuhh) ? $loppuhh : date("H");
      $loppupp = isset($loppupp) ? $loppupp : date("d");

      $lisa = "";
      if ($maa != "" and $maa != $yhtiorow["maa"]) {
        $lisa = "<th>".t("Ulkomaan ALV")."</th>";
        $cols = 6;
      }
      else {
        $cols = 5;
      }

      echo "<th>".t("Kustannuspaikka")."</th><th>".t("Kohdemaa")."</th><th>".t("Määrä")."</th><th>".t("Hinta")."</th><th>".t("Alv")."</th>$lisa</tr>";
      echo "<tr><td>";

      if ($kustannuspaikka != "") {
        echo $kustannuspaikka;
      }
      else {
        echo t("Ei kustannuspaikkaa");
      }

      echo "</td>";

      $query = "SELECT distinct koodi, nimi
                FROM maat
                WHERE nimi != ''
                ORDER BY koodi";
      $vresult = pupe_query($query);

      echo "<td><select name='maa' onchange='submit();' style='width: 150px;'>";

      while ($vrow = mysql_fetch_assoc($vresult)) {
        $sel = "";
        if ($maa == "" and $yhtiorow["maa"] == $vrow["koodi"]) {
          $sel = "selected";
        }
        elseif ($maa == $vrow["koodi"]) {
          $sel = "selected";
        }

        echo "<option value = '$vrow[koodi]' $sel>".t($vrow["nimi"])."</option>";
      }

      echo "</select></td>";
      echo "<td><input type='text' name='kpl' value='$kpl' size='6'></td>";

      //  Hinta saadaan antaa, jos meillä ei ole ennettu hintaa
      if ($trow["myyntihinta"] > 0) {
        echo "<td align='center'><input type='hidden' name='hinta' value='$trow[myyntihinta]'>$trow[myyntihinta]</td>";
      }
      else {
        echo "<td><input type='text' name='hinta' value='$hinta' size='8'></td>";
      }

      if ($maa != "" and $maa != $yhtiorow["maa"]) {
        echo "<td>0 %</td>";

        //  Haetaan oletusalv tuotteelta
        if ($alvulk == "") {
          $query = "SELECT * from tuotteen_alv where yhtio = '$kukarow[yhtio]' and maa = '$maa' and tuoteno = '$tuoteno' limit 1";
          $alhire = pupe_query($query);
          $alvrow = mysql_fetch_assoc($alhire);

          $alvulk = $alvrow["alv"];

          if ($alvulk == "") {
            echo "<font class='error'>".t("Kulun arvonlisäveroa kohdemaassa ei ole määritelty")."</font><br>";
          }
        }
        echo "<td><input type='hidden' name='vero' value='0'>".alv_popup_oletus("alvulk", $alvulk, $maa, 'lista')."</td>";
      }
      else {
        echo "<td><input type='hidden' name='vero' value='$trow[alv]'> $trow[alv] %</td>";
      }

      $leveys = 50;
    }

    echo "<td class='back'><input type='submit' name='lisaa' value='".t("Lisää")."'></td></tr>";

    if ($tyyppi == "B") {
      echo "<tr>";
      echo "<th colspan='2'>" . t("Alku") . "</th><th colspan='3'>" . t("Loppu") .
        "</th>";
      echo "</tr>";
      echo "<tr>";
      echo
      "<td colspan='2'><input type='text' name='alkupp' value='{$alkupp}' size='3' maxlength='2'>
           <input type='text' name='alkukk' value='{$alkukk}' size='3' maxlength='2'>
           <input type='text' name='alkuvv' value='{$alkuvv}' size='5' maxlength='4'> " . t("klo") .
        ":<input type='text' name='alkuhh' value='{$alkuhh}' size='3' maxlength='2'>:
           <input type='text' name='alkumm' value='{$alkumm}' size='3' maxlength='2'>&nbsp;</td>
           <td colspan='3'>&nbsp;
           <input type='text' name='loppupp' value='{$loppupp}' size='3' maxlength='2'>
           <input type='text' name='loppukk' value='{$loppukk}' size='3' maxlength='2'>
           <input type='text' name='loppuvv' value='{$loppuvv}' size='5' maxlength='4'> " .
        t("klo") .
        ":<input type='text' name='loppuhh' value='{$loppuhh}' size='3' maxlength='2'>:
           <input type='text' name='loppumm' value='{$loppumm}' size='3' maxlength='2'>";
      echo "</tr>";
    }

    echo "<tr><th colspan='$cols'>".t("Kommentti")."</th></tr>";
    echo "<tr><td colspan='$cols'><textarea name='kommentti' rows='4' cols='80'>".str_replace("<br>", "\n", $kommentti)."</textarea></td>";

    if ($toim == "SUPER") {
      echo "<tr>";

      $cspan = $tyyppi == "B" ? 4 : 3;

      echo "<th colspan='$cspan'>".t("Poikkeava tilinumero")." (".t("oletus on")." $trow[tilino])</th>";

      if ($tilino == $trow["tilino"] or $kuivat == "JOO" or $kulupoiminta == "JOO") {
        $tilino  = "";
      }

      echo "<td colspan='1'><input type='text' name='tilino' value = '$tilino' size='20'></td>";
    }

    echo "<td class='back'><input type='submit' name='tyhjenna' value='".t("Tyhjennä")."'></td>";

    if ($laskurow["tilaustyyppi"] != "M") {
      //  Jos laskun ja rivien loppusumma heittää näytetään erotus..
      $query = "SELECT sum(rivihinta) summa
                FROM tilausrivi
                WHERE yhtio='$kukarow[yhtio]' and otunnus='$tilausnumero' and tyyppi='M'";
      $result = pupe_query($query);
      $rivisumma = mysql_fetch_assoc($result);

      if ((float) $rivisumma["summa"] + ((float) $hinta * (float) $kpl) !=  (float) $laskurow["summa"]) {
        echo "<tr><td class='back' align='right' colspan='3'>".("Käsittelemättä")."</td><td class='back' align='right' colspan='2'><font clasS='error'>".number_format(( (float) $laskurow["summa"] - ((float) $rivisumma["summa"] + ((float) $hinta * (float) $kpl))), 2, ', ', ' ')."</font></td></tr>";
      }
    }

    echo "</tr></table></form>";
  }

  /*
    Piilotetaan rivit joilla ei ole kappaleita (päiväraha, jos vain puolikas..)
  */
  $query = "SELECT tilausrivi.*, tuotetyyppi,
            if (tuote.tuotetyyppi='A' or tuote.tuotetyyppi='B', concat(date_format(kerattyaika, '%d.%m.%Y %k:%i'),' - ',date_format(toimitettuaika, '%d.%m.%Y %k:%i')), '') ajalla,
            concat_ws('/',kustp.nimi,kohde.nimi,projekti.nimi) kustannuspaikka,
            if (tilausrivi.perheid=0, tilausrivi.tunnus, (select max(tunnus) from tilausrivi t use index(yhtio_otunnus) where tilausrivi.yhtio = t.yhtio and tilausrivi.otunnus = t.otunnus and tilausrivi.perheid=t.perheid and tilausrivi.tyyppi=t.tyyppi)) viimonen,
            if (tilausrivi.perheid=0, tilausrivi.tunnus, tilausrivi.perheid) perhe,
            if (tilausrivi.perheid=0, 1,(select count(*) from tilausrivi t use index(yhtio_otunnus) where tilausrivi.yhtio = t.yhtio and tilausrivi.otunnus = t.otunnus and tilausrivi.perheid=t.perheid and tilausrivi.tyyppi=t.tyyppi)) montako,
            tiliointi.tilino tilino,
            tilausrivin_lisatiedot.kulun_kohdemaa, kulun_kohdemaa
            FROM tilausrivi use index(yhtio_otunnus)
            LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
            LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
            LEFT JOIN tiliointi ON tiliointi.yhtio=tilausrivin_lisatiedot.yhtio and tiliointi.tunnus=tilausrivin_lisatiedot.tiliointirivitunnus
            LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus
            LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus
            LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus
            WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
            and tilausrivi.otunnus = '$tilausnumero'
            and tilausrivi.tyyppi  = 'M'
            ORDER BY tilausrivi.perheid2 desc, tilausrivi.tunnus";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    echo "<br><font class='message'>" . t("Kulurivit") . "</font><hr>";
    echo "<table>";
    echo "<tr>";
    echo "<th>#</th>";
    echo "<th>".t("Kulu")."</th>";
    echo "<th>".t("Kustannuspaikka")."</th>";
    echo "<th>".t("Kpl")."</th>";
    echo "<th>".t("Hinta")."</th>";
    echo "<th>".t("Alv")."</th>";
    echo "<th>".t("Yhteensä")."</th>";
    echo "<th>".t("Pvm")."</th>";
    echo "<th>".t("Ilmaiset ateriat")."</th>";
    echo "</tr>";

    $query2 = "SELECT count(*)
               FROM tilausrivi
               WHERE yhtio  = '{$kukarow["yhtio"]}'
               AND otunnus  = '{$tilausnumero}'
               AND tyyppi  != 'D'
               GROUP BY perheid2
               ORDER BY perheid2 desc";
    $result2 = pupe_query($query2);

    $summa     = 0;
    $tapahtumia = 1;
    $aikajana   = array();
    $lask     = 0;

    while ($row = mysql_fetch_assoc($result)) {

      if ($row['tunnus'] == $row['perheid2']) {
        $tilausrivien_lkm1 = mysql_fetch_row($result2);
        $tilausrivien_lkm  = (int) $tilausrivien_lkm1[0];
      }

      if (!isset($aikajana['ensimmainen_aika'])) {
        $aikajana['ensimmainen_aika'] = tv1dateconv($row['kerattyaika'], "P");
      }

      echo_matkalasku_row($row, $kukarow, $laskurow, $yhtiorow, $tee, $lopetus, $toim, $tilausnumero, $PHP_SELF, $tapahtumia, $tilausrivien_lkm, $muokkauslukko);

      $summa += $row["rivihinta"];
      $tapahtumia++;
      $lask++;

      if ($tilausrivien_lkm == $lask) {
        $aikajana['viimeinen_aika'] = tv1dateconv($row['toimitettuaika'], "P");

        echo_kommentit($row, $toim, $kukarow, $aikajana);

        unset($aikajana);
        $lask = 0;
      }
    }

    echo_summa($summa);

    echo "</table>";
  }

  echo "<br><hr>";

  if ($laskurow["tilaustyyppi"] != "M") {
    // Jos laskun ja rivien loppusumma heittää näytetään erotus..
    $query = "SELECT sum(rivihinta) summa
              FROM tilausrivi
              WHERE yhtio = '$kukarow[yhtio]'
              and otunnus = '$tilausnumero'
              and tyyppi  = 'M'";
    $result = pupe_query($query);
    $rivisumma = mysql_fetch_assoc($result);

    echo t("Laskun summa")." ".number_format($laskurow["summa"], 2, ', ', ' ')."<br>";

    if (round($rivisumma["summa"], 2) != round($laskurow["summa"], 2)) {
      echo "<font class='error'>".t("Käsittelemättä")." ".number_format(($laskurow["summa"] - $rivisumma["summa"]), 2, ', ', ' ')."</font><br>";
    }
  }

  if ($lopetus == "") {
    if ($laskurow["mapvm"] != "0000-00-00") {
      echo "  <form action='matkalasku.php' name='palaa' method='post'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='tee' value=''>";

      if (isset($listaa_kayttaja)) {
        echo "  <input type='hidden' name='listaa_kayttaja' value='$listaa_kayttaja'>";
      }

      echo "  <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='submit' value='".t("Valmis")."'>
          </form>";
    }
    else {
      echo "  <form action='matkalasku.php' name='palaa' method='post'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='tee' value='VALMIS'>
          <input type='hidden' name='lopetus' value='$lopetus'>
          <input type='hidden' name='tilausnumero' value='$tilausnumero'>
          <input type='submit' value='".t("Matkalasku valmis")."'>
          </form>";
    }
  }

  if ($id > 0) {
    echo "<iframe src='view.php?id=$id' name='alaikkuna' width='100%' height='60%' align='bottom' scrolling='auto'></iframe>";
  }
}

if ($tee == "") {

  echo "<br><br><font class='message'>".t("Perusta uusi matkalasku")."</font><hr>";

  echo "<form action='matkalasku.php' method='post' autocomplete='off'>";
  echo "<input type='hidden' name='tee' value='UUSI'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='toim' value='$toim'>";

  echo "<br><table>";
  echo "<tr>";
  echo "<th>".t("Matkustaja")."</th>";
  echo "<td>";

  if ($toim == "SUPER") {
    $query = "SELECT toimi.nimi kayttaja, kuka.nimi kayttajanimi
              FROM toimi
               JOIN kuka ON kuka.yhtio=toimi.yhtio and kuka.kuka=toimi.nimi
               WHERE toimi.yhtio = '$kukarow[yhtio]'
              and toimi.tyyppi   = 'K'
              ORDER BY kayttajanimi";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      echo "<select name = 'kayttaja'>";

      while ($krow = mysql_fetch_assoc($result)) {
        $valittu = "";

        if ($krow["kayttaja"] == $kukarow["kuka"]) {
          $valittu = "selected";
        }

        echo "<option value = '$krow[kayttaja]' $valittu>$krow[kayttajanimi]</option>";
      }

      echo "</select>";
    }
  }
  else {
    echo "$kukarow[nimi]";
  }

  echo "</td>";
  echo "<td class='back'><input type='submit' name='EI_ASIAKASTA_X' value='".t("Perusta")."'></td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";

  echo "<br><br><font class='message'>".t("Perusta uusi matkalasku ja liitä asiakas laskuun")."</font><hr>";

  echo "<form action='matkalasku.php' method='post' autocomplete='off'>";
  echo "<input type='hidden' name='tee' value='UUSI'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='toim' value='$toim'>";

  echo "<br><table>";
  echo "<tr>";
  echo "<th>".t("Matkustaja")."</th>";
  echo "<td>";

  if ($toim == "SUPER") {
    $query = "SELECT toimi.nimi kayttaja, kuka.nimi kayttajanimi
              FROM toimi
               JOIN kuka ON kuka.yhtio=toimi.yhtio and kuka.kuka=toimi.nimi
               WHERE toimi.yhtio='$kukarow[yhtio]'
              and toimi.tyyppi='K'
              ORDER BY kayttajanimi";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      echo "<select name = 'kayttaja'>";

      while ($krow = mysql_fetch_assoc($result)) {
        $valittu = "";

        if ($krow["kayttaja"] == $kukarow["kuka"]) {
          $valittu = "selected";
        }

        echo "<option value = '$krow[kayttaja]' $valittu>$krow[kayttajanimi]</option>";
      }

      echo "</select>";
    }
  }
  else {
    echo "$kukarow[nimi]";
  }

  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Asiakas")."</th>";
  echo "<td><input type='text' size='20' name='ytunnus'></td>";
  echo "<td class='back'><input type='submit' value='".t("Perusta")."'></td>";
  echo "</tr>";

  echo "</table>";
  echo "</form>";

  $query = "SELECT lasku.*,
            laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa,
            kuka.nimi kayttajanimi
            FROM lasku
            LEFT JOIN laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
            LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and kuka.kuka=lasku.hyvak1
            WHERE lasku.yhtio      = '$kukarow[yhtio]'
            and lasku.tila         = 'H'
            and lasku.mapvm        = '0000-00-00'
            and (
              (lasku.hyvak2 = '$kukarow[kuka]' and lasku.h2time = '0000-00-00 00:00:00' and lasku.hyvaksyja_nyt = '$kukarow[kuka]') or
              (lasku.hyvak3 = '$kukarow[kuka]' and lasku.h3time = '0000-00-00 00:00:00' and lasku.hyvaksyja_nyt = '$kukarow[kuka]') or
              (lasku.hyvak4 = '$kukarow[kuka]' and lasku.h4time = '0000-00-00 00:00:00' and lasku.hyvaksyja_nyt = '$kukarow[kuka]') or
              (lasku.hyvak5 = '$kukarow[kuka]' and lasku.h5time = '0000-00-00 00:00:00' and lasku.hyvaksyja_nyt = '$kukarow[kuka]')
            )
            and lasku.tilaustyyppi = 'M'";
  $result = pupe_query($query);

  if (mysql_num_rows($result)) {

    echo "<br><br><font class='message'>".t("Hyväksynnässä olevat matkalaskut")."</font><hr>";
    echo "<table><tr><th>".t("Laskunro")."</th><th>".t("Käyttäjä")."</th><th>".t("Asiakas")."</th><th>".t("Viesti")."</th><th>".t("Summa")."</th><tr>";

    while ($row = mysql_fetch_assoc($result)) {
      echo "<tr>";
      echo "<td>$row[laskunro]</td>";
      echo "<td>$row[kayttajanimi]</td>";
      echo "<td>$row[laskutus_nimi]</td>";
      echo "<td>$row[viite]</td>";
      echo "<td>$row[summa]</td>";
      echo "<form action='matkalasku.php' method='post' autocomplete='off'>";
      echo "<input type='hidden' name='tee' value='MUOKKAA'>";
      echo "<input type='hidden' name='lopetus' value='$lopetus'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";

      if ($kukarow["taso"] == 2) {
        echo "<td class='back'><input type='submit' value='".t("Muokkaa")."'></td>";
      }
      else {
        echo "<td class='back'><input type='submit' value='".t("Tarkastele")."'></td>";
      }

      echo "</form>";
      echo "</tr>";

    }
    echo "</table>";
  }

  $super_kuka = "and lasku.toim_ovttunnus = '$kukarow[kuka]'";

  if ($toim == "SUPER") {
    $super_kuka = "and (lasku.toim_ovttunnus = '$kukarow[kuka]' or lasku.laatija = '$kukarow[kuka]')";
  }

  $query = "SELECT lasku.*,
            laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
            FROM lasku
            LEFT JOIN laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
            WHERE lasku.yhtio        = '$kukarow[yhtio]'
            and lasku.tila           = 'H'
            and lasku.mapvm          = '0000-00-00'
            $super_kuka
            and lasku.h1time         = '0000-00-00 00:00:00'
            and lasku.tilaustyyppi   = 'M'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<br><br><font class='message'>".t("Avoimet matkalaskusi")."</font><hr>";
    echo "<table><tr><th>".t("Laskunro")."</th><th>".t("Henkilö")."</th><th>".t("Asiakas")."</th><th>".t("Viesti")."</th><th>".t("Summa")."</th><tr>";

    while ($row = mysql_fetch_assoc($result)) {
      echo "<tr>";
      echo "<td>$row[laskunro]</td>";
      echo "<td>$row[nimi]</td>";
      echo "<td>$row[laskutus_nimi]</td>";
      echo "<td>$row[viite]</td>";
      echo "<td>$row[summa]</td>";
      echo "<td class='back'><form action='matkalasku.php' method='post' autocomplete='off'>";
      echo "<input type='hidden' name='tee' value='MUOKKAA'>";
      echo "<input type='hidden' name='lopetus' value='$lopetus'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
      echo "<input type='submit' value='".t("Muokkaa")."'>";
      echo "</form></td>";
      echo "<td class='back'><form action='matkalasku.php' method='post' autocomplete='off'>";
      echo "<input type='hidden' name='tee' value='POISTA'>";
      echo "<input type='hidden' name='lopetus' value='$lopetus'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
      echo "<input type='submit' value='".t("Poista")."'>";
      echo "</form></td>";
      echo "</tr>";
    }
    echo "</table>";
  }


  echo "<br><br><font class='message'>".t("Vanhat matkalaskusi")."</font><hr>";

  if ($toim == "SUPER") {

    echo "<form action='matkalasku.php' method='post' autocomplete='off'>";
    echo "<input type='hidden' name='tee' value=''>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='toim' value='$toim'>";

    echo "<br><table>";
    echo "<tr>";
    echo "<th>".t("Matkustaja")."</th>";
    echo "<td>";

    $query = "SELECT toimi.nimi kayttaja, kuka.nimi kayttajanimi
              FROM toimi
               JOIN kuka ON kuka.yhtio=toimi.yhtio and kuka.kuka=toimi.nimi
               WHERE toimi.yhtio='$kukarow[yhtio]'
              and toimi.tyyppi='K'
              ORDER BY kayttajanimi";
    $result = pupe_query($query);

    if (!isset($listaa_kayttaja)) $listaa_kayttaja = $kukarow["kuka"];


    if (mysql_num_rows($result) > 0) {
      echo "<select name = 'listaa_kayttaja' onchange='submit()'>";

      while ($krow = mysql_fetch_assoc($result)) {
        $valittu = "";

        if ($krow["kayttaja"] == $listaa_kayttaja) {
          $valittu = "selected";
        }

        echo "<option value = '$krow[kayttaja]' $valittu>$krow[kayttajanimi]</option>";
      }

      echo "</select>";
    }

    echo "</td>";
    echo "</tr>";
    echo "</table></form><br>";
  }
  else {
    $listaa_kayttaja = $kukarow['kuka'];
  }

  $query = "SELECT lasku.*,
            laskun_lisatiedot.laskutus_nimi, laskun_lisatiedot.laskutus_nimitark, laskun_lisatiedot.laskutus_osoite, laskun_lisatiedot.laskutus_postino, laskun_lisatiedot.laskutus_postitp, laskun_lisatiedot.laskutus_maa
            FROM lasku
            LEFT JOIN laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
            WHERE lasku.yhtio         = '$kukarow[yhtio]'
            and lasku.tila            IN ('H','Y','M','P','Q')
            and lasku.toim_ovttunnus  = '$listaa_kayttaja'
            and lasku.h1time         != '0000-00-00 00:00:00'
            and lasku.tilaustyyppi    = 'M'
            ORDER BY luontiaika DESC";
  $result = pupe_query($query);

  if (mysql_num_rows($result)) {

    echo "<table><tr><th>".t("Laskunro")."</th><th>".t("Asiakas")."</th><th>".t("Viesti")."</th><th>".t("Summa")."</th><th>".t("Tila")."</th><tr>";

    while ($row = mysql_fetch_assoc($result)) {
      $laskutyyppi = $row["tila"];
      $alatila = $row["alatila"];

      //tehdään selväkielinen tila/alatila
      require "inc/laskutyyppi.inc";

      echo "<form action='matkalasku.php' method='post' autocomplete='off'>";
      echo "<input type='hidden' name='tee' value='MUOKKAA'>";
      echo "<input type='hidden' name='lopetus' value='$lopetus'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='tilausnumero' value='$row[tunnus]'>";
      echo "  <input type='hidden' name='listaa_kayttaja' value='$listaa_kayttaja'>";
      echo "<tr>";
      echo "<td>$row[laskunro]</td>";
      echo "<td>$row[laskutus_nimi]</td>";
      echo "<td>$row[viite]</td>";
      echo "<td>$row[summa]</td>";
      echo "<td>".t($laskutyyppi)."</td>";
      echo "<td class='back'><input type='submit' value='".t("Tarkastele")."'></td>";
      echo "</tr>";
      echo "</form>";
    }
    echo "</table>";
  }
}

function echo_matkalasku_row($row, $kukarow, $laskurow, $yhtiorow, $tee, $lopetus, $toim, $tilausnumero, $PHP_SELF, $tapahtumia, $tilausrivien_lkm, $muokkauslukko) {
  static $riviTemp = 0;

  $row["nimitys"] = t_tuotteen_avainsanat($row, 'nimitys', $kukarow["kieli"]);

  echo '<tr class="aktiivi">';

  if ($row['tunnus'] == $row['perheid2']) {
    $riviTemp++;
    echo '<td rowspan="'.$tilausrivien_lkm.'">' . ($riviTemp) . '</td>';
  }

  if ($laskurow["tilaustyyppi"] != "M" and $row["tuoteno"] == "") {
    echo_checks($kukarow, $yhtiorow, $row, $PHP_SELF, $tee, $lopetus, $toim, $tilausnumero, $edrivi);
  }
  else {
    echo "<td style='font-weight:bold'>$row[nimitys]<a name='ankkuri_$row[tunnus]'></a></td>";
  }

  echo_td($row);

  $kustannuspaikat = array(
    'kustp' => $_POST['kustp'],
    'kohde' => $_POST['kohde'],
    'projekti' => $_POST['projekti']
  );

  echo_ilmaiset_lounaat($lopetus, $toim, $tilausnumero, $row, $kustannuspaikat, $muokkauslukko);

  if ($row['tunnus'] == $row['perheid2'] and !$muokkauslukko) {
    echo_nappulat($laskurow, $tee, $lopetus, $toim, $tilausnumero, $row['perhe'], $row['perheid2']);
  }

  echo '</tr>';
}

function echo_checks($kukarow, $yhtiorow, $row, $PHP_SELF, $tee, $lopetus, $toim, $tilausnumero, $edrivi) {
  $query = "SELECT tuote.tuoteno, tuote.nimitys, tuote.vienti
            FROM tuote
            JOIN tili ON tili.yhtio = tuote.yhtio and tili.tilino = tuote.tilino
            WHERE tuote.yhtio      = '$kukarow[yhtio]'
            and tuote.tuotetyyppi  = 'B'
            and tuote.status      != 'P'
            and tuote.tilino      != ''
            ORDER BY tuote.vienti IN ('$yhtiorow[maa]') DESC, tuote.vienti ASC, tuote.nimitys";
  $tres = pupe_query($query);
  $valinta = "";

  if (mysql_num_rows($tres) > 0) {
    $valinta = "<select name='vaihda_tuote' onchange='submit();' style='width: 125px;'>";
    $valinta .= "<option value=''>" . t("Määrittele kulu") . "</option>";

    while ($trow = mysql_fetch_assoc($tres)) {
      if ($trow["tuoteno"] == $row["tuoteno"]) {
        $sel = "selected";
      }
      else {
        $sel = "";
      }

      $valinta .= "<option value='$trow[tuoteno]' $sel>$trow[nimitys]</option>";
    }
    $valinta .= "</select>";
  }

  echo "<td>
      <form action='matkalasku.php#ankkuri_$row[tunnus]' method='post' autocomplete='off'>
        <input type='hidden' name='tee' value='$tee'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='tilausnumero' value='$tilausnumero'>
        <input type='hidden' name='tapa' value='MUOKKAA'>
        <input type='hidden' name='vaihda_tyyppi' value='B'>
        <input type='hidden' name='kulupoiminta' value='JOO'>
        <input type='hidden' name='rivitunnus' value='$row[perhe]'>
        $valinta
      </form>
    </td>";
  $edrivi = $row["tunnus"];
}

function echo_td($row) {
  echo "<td>$row[kustannuspaikka]</td>";
  echo "<td align='right'>$row[kpl]</td>";
  echo "<td align='right'>" . number_format($row["hinta"], 2, ', ', ' ') . "</td>";
  echo "<td align='right'>" . number_format($row["alv"], 2, ', ', ' ') . "</td>";
  echo "<td align='right'>" . number_format($row["rivihinta"], 2, ', ', ' ') . "</td>";
  echo "<td align='right'>" . tv1dateconv($row['kerattyaika']) . "</td>";
}

function echo_ilmaiset_lounaat($lopetus, $toim, $tilausnumero, $row, $kustannuspaikat, $muokkauslukko) {
  $selected_lounas = (int) $row['erikoisale'];
  $ilmaiset_lounaat_kpl = array(0, 1, 2);

  echo "<td>";

  if ($row['tuotetyyppi'] == 'A' and !$muokkauslukko) {
    echo "<form action='matkalasku.php' autocomplete='off' method='post'>
          <input type='hidden' value='TARKISTA_ILMAISET_LOUNAAT' name='tee'>
          <input type='hidden' value='$lopetus' name='lopetus'>
          <input type='hidden' value='$toim' name='toim'>
          <input type='hidden' value='$tilausnumero' name='tilausnumero'>
          <input type='hidden' value='MUOKKAA' name='tapa'>
          <input type='hidden' value='{$row['tunnus']}' name='rivitunnus'>
          <input type='hidden' value='{$row['perheid2']}' name='perheid2'>
        <input type='hidden' value='{$kustannuspaikat['kustp']}' name='kustp'>
        <input type='hidden' value='{$kustannuspaikat['kohde']}' name='kohde'>
        <input type='hidden' value='{$kustannuspaikat['projekti']}' name='projekti'>";


    echo "<select name='ilmaiset_lounaat' onchange='submit();' align='right'>";

    foreach ($ilmaiset_lounaat_kpl as $lounas) {
      if ($lounas == 2) {
        if ($lounas == $selected_lounas) {
          echo "<option selected='selected' value='$lounas'>$lounas tai enemmän</option>";
        }
        else {
          echo "<option value='$lounas'>$lounas tai enemmän</option>";
        }
      }
      else {
        if ($lounas == $selected_lounas) {
          echo "<option selected='selected' value='$lounas'>$lounas</option>";
        }
        else {
          echo "<option value='$lounas'>$lounas</option>";
        }
      }
    }
    echo "</select>";
    echo "</form>";
  }
  elseif ($row['tuotetyyppi'] == 'A') {
    echo $selected_lounas;
  }

  echo "</td>";
}

function echo_nappulat($laskurow, $tee, $lopetus, $toim, $tilausnumero, $perhe, $perheid2) {
  echo "<td class='back'>";
  echo "<form action='matkalasku.php' method='post' autocomplete='off'>";
  echo "<input type='hidden' name='tee' value='$tee'>";
  echo "<input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
  echo "<input type='hidden' name='tapa' value='MUOKKAA'>";
  echo "<input type='hidden' name='rivitunnus' value='$perhe'>";
  echo "<input type='hidden' name='perheid2' value='$perheid2'>";
  echo "<input type='submit' value='" . t("Muokkaa") . "'>";
  echo "</form>";
  echo "</td>";

  if ($laskurow["tilaustyyppi"] == "M") {
    echo "<td class='back'>";
    echo "<form action='matkalasku.php' method='post' autocomplete='off'>";
    echo "<input type='hidden' name='tee' value='$tee'>";
    echo "<input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
    echo "<input type='hidden' name='tapa' value='POISTA'>";
    echo "<input type='hidden' name='rivitunnus' value='$perhe'>";
    echo "<input type='hidden' name='perheid2' value='$perheid2'>";
    echo "<input type='submit' value='" . t("Poista") . "'>";
    echo "</form>";
    echo "</td>";
  }
}

function echo_kommentit($row, $toim, $kukarow, $aikajana) {
  if ($row["tuotetyyppi"] == "A") {
    echo "<tr class='aktiivi'>";
    echo '<td></td>';
    echo "<td>" . t("Ajalla") . "</td>";
    echo "<td colspan='7' style='font-style:italic'>" . $aikajana['ensimmainen_aika'] . ' - ' . $aikajana['viimeinen_aika'] . "</td>";
    echo "</tr>";
  }
  else {
    echo "<tr class='aktiivi'>";
    echo '<td></td>';
    echo "<td>" . t("Kohdemaa") . "</td>";
    echo "<td colspan='7' style='font-style:italic'>" . maa($row["kulun_kohdemaa"]) . "</td>";
    echo "</tr>";
  }

  if ($row["kommentti"] != "") {
    echo "<tr class='aktiivi'>";
    echo '<td></td>';
    echo "<td colspan='8' style='font-style:italic'>$row[kommentti]</td></tr>";
  }

  if ($toim == "SUPER") {
    $query = "SELECT nimi
              FROM tili
              WHERE yhtio = '$kukarow[yhtio]'
              and tilino  = '$row[tilino]'";
    $tilires = pupe_query($query);
    $tilirow = mysql_fetch_assoc($tilires);

    echo "<tr class='aktiivi'>";
    echo "<td></td>";
    echo "<td colspan='8'>" . t("Kirjanpidon tili") . ": $row[tilino] $tilirow[nimi]</td>";
    echo "</tr>";
  }
}

function echo_summa($summa) {
  echo "<tr>";
  echo "<th colspan='6' style='text-align:right;'>" . t("Yhteensä") . "</th>";
  echo "<th style='text-align:right;'>" . number_format($summa, 2, ', ', ' ') . "</th>";
  echo "</tr>";
}

function poista_edelliset_matkalaskurivit($tilausnumero, $rivitunnus, $kukarow) {
  //haetaan kaikki matkalaskuun kuuluvat rivit, jotta osataan poistaa tilausrivin_lisatiedot
  $query = "SELECT group_concat(tunnus) tunnukset
            FROM tilausrivi
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND otunnus  = '{$tilausnumero}'
            AND perheid2 = '{$rivitunnus}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  if ($row['tunnukset'] == "") {
    $row['tunnukset'] = 0;
  }

  // ei poisteta päiväraha tilausrivejä kokonaan, koska niistä tarvitaan erikoisale kentästä ilmaiset lounaat jos on annettu
  $query = "UPDATE tilausrivi
            JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno and tuote.tuotetyyppi='A'
            SET tilausrivi.tyyppi = 'D'
            WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus  = '{$tilausnumero}'
            AND tilausrivi.perheid2 = '{$rivitunnus}'";
  pupe_query($query);

  // kulurivit poistetaan kokonaan
  $query = "DELETE tilausrivi.*
            FROM tilausrivi
            JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno and tuote.tuotetyyppi='B'
            WHERE tilausrivi.yhtio  = '{$kukarow['yhtio']}'
            AND tilausrivi.otunnus  = '{$tilausnumero}'
            AND tilausrivi.perheid2 = '{$rivitunnus}'";
  pupe_query($query);

  // haetaan tiliöintirivien tunnukset
  $query = "SELECT group_concat(tiliointirivitunnus) tiliointirivitunnukset
            FROM tilausrivin_lisatiedot
            WHERE yhtio          = '{$kukarow['yhtio']}'
            AND tilausrivitunnus IN ({$row['tunnukset']})";
  $result = pupe_query($query);
  $trow = mysql_fetch_assoc($result);

  if ($trow['tiliointirivitunnukset'] != "") {
    // Yliviivataan tiliöinnit
    $query = "UPDATE tiliointi
              SET korjattu = '$kukarow[kuka]',
              korjausaika = now()
              WHERE yhtio = '$kukarow[yhtio]'
              and ltunnus = '$tilausnumero'
              and tunnus  in ({$trow['tiliointirivitunnukset']})";
    $result = pupe_query($query);

    // Yliviivataan ALV-tiliöinnit
    $query = "UPDATE tiliointi
              SET korjattu = '$kukarow[kuka]',
              korjausaika   = now()
              WHERE yhtio   = '$kukarow[yhtio]'
              and ltunnus   = '$tilausnumero'
              and aputunnus in ({$trow['tiliointirivitunnukset']})";
    $result = pupe_query($query);
  }

  // Poistetaan tilausrivin lisätiedot
  $query = "DELETE
            FROM tilausrivin_lisatiedot
            WHERE yhtio          = '{$kukarow['yhtio']}'
            AND tilausrivitunnus IN ({$row['tunnukset']})";
  pupe_query($query);

  korjaa_ostovelka($tilausnumero);
}

function hae_ensimmainen_matkalaskurivi($kukarow, $tilausnumero, $perheid2) {
  $query = "SELECT tilausrivi.*, tuote.tuotetyyppi, tuote.tilino,
            tilausrivin_lisatiedot.tiliointirivitunnus,
            tilausrivin_lisatiedot.kulun_kohdemaa,
            tilausrivin_lisatiedot.kulun_kohdemaan_alv,
            kustp.tunnus kustp,
            kohde.tunnus kohde,
            projekti.tunnus projekti,
            if (tuotetyyppi='A', tuote.vienti, tilausrivin_lisatiedot.kulun_kohdemaa) vienti
            FROM tilausrivi use index (PRIMARY)
            LEFT JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
            LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
            LEFT JOIN tiliointi ON tiliointi.yhtio=tilausrivin_lisatiedot.yhtio and tiliointi.tunnus=tilausrivin_lisatiedot.tiliointirivitunnus
            LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus
            LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus
            LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus
            WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
            and tilausrivi.otunnus  = '$tilausnumero'
            and tilausrivi.perheid2 = $perheid2
            and tilausrivi.tunnus   = tilausrivi.perheid2";
  $abures = pupe_query($query);

  return mysql_fetch_assoc($abures);
}

function hae_kaikki_matkalaskurivit($kukarow, $tilausnumero, $rivitunnus) {
  //eli tän funkkarun idis on varmaan hakee sit kaikki kyseisen matkalaskun laskurivit perheid2 perusteella ja täyttää ne formiin
  $query = 'SELECT tilausrivi.*,
            tuote.tuotetyyppi,
            tuote.tilino,
            tilausrivin_lisatiedot.tiliointirivitunnus,
            tilausrivin_lisatiedot.kulun_kohdemaa,
            tilausrivin_lisatiedot.kulun_kohdemaan_alv,
            kustp.tunnus kustp,
            kohde.tunnus kohde,
            tiliointi.tilino tiliointitili,
            tiliointi.vero tiliointialv,
            projekti.tunnus projekti
            FROM tilausrivi use index (yhtio_perheid2)
            LEFT JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
            LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus
            LEFT JOIN tiliointi ON tiliointi.yhtio=tilausrivin_lisatiedot.yhtio and tiliointi.tunnus=tilausrivin_lisatiedot.tiliointirivitunnus
            LEFT JOIN kustannuspaikka kustp ON tiliointi.yhtio=kustp.yhtio and tiliointi.kustp=kustp.tunnus
            LEFT JOIN kustannuspaikka projekti ON tiliointi.yhtio=projekti.yhtio and tiliointi.projekti=projekti.tunnus
            LEFT JOIN kustannuspaikka kohde ON tiliointi.yhtio=kohde.yhtio and tiliointi.kohde=kohde.tunnus
            WHERE tilausrivi.yhtio ="' . $kukarow['yhtio'] . '"
            AND tilausrivi.perheid2 ="' . $rivitunnus . '"
            ORDER BY tilausrivi.kerattyaika';

  $result = pupe_query($query);

  //puukko
  //laiteaan ekan rivin tiedot talteen koska niitä tietoja käytetään myöhemmin funkkarin jälkeen. ajat pitää kuintekin tulla ensimmäisestä ja viimeisestä matkalaskun päivämäärästä
  $eka_matkalasku = null;
  while ($row = mysql_fetch_assoc($result)) {
    if ($eka_matkalasku == null) {
      $eka_matkalasku = $row;
    }
    $rowTemp = $row;//laiteaan viimonen matkalaskurivi talteen
  }

  $rowTemp['kerattyaika'] = $eka_matkalasku['kerattyaika'];

  return $rowTemp;
}

function tarkista_loytyyko_paivalle_matkalasku($alkuaika, $loppuaika, $tilausnumero) {
  //tarkistetaan löytyykö käyttäjälle matkapäivä tilasta D
  global $kukarow;

  $query = "SELECT erikoisale, tunnus
            FROM tilausrivi
            WHERE yhtio        = '{$kukarow['yhtio']}'
            AND otunnus        = '{$tilausnumero}'
            AND kerattyaika    = '{$alkuaika}'
            AND toimitettuaika = '{$loppuaika}'
            AND tyyppi         = 'D'";
  $result = pupe_query($query);

  if ($tilausrivi_row = mysql_fetch_assoc($result)) {
    dellaa_mitatoity_rivi($tilausnumero, $tilausrivi_row['tunnus']);

    return $tilausrivi_row['erikoisale'];
  }

  return 0;
}

function dellaa_mitatoity_rivi($tilausnumero, $rivitunnus) {
  global $kukarow;

  $query = "DELETE FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '$tilausnumero'
            AND tyyppi  = 'D'
            AND tunnus  = '$rivitunnus'";
  pupe_query($query);
}

function lisaa_paivaraha($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $alku, $loppu, $kommentti, $kustp, $kohde, $projekti) {
  return lisaa_kulurivi($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $alku, $loppu, "", 0, "", "", $kommentti, "A", $kustp, $kohde, $projekti);
}

function lisaa_kulu($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $kpl, $vero,
  $hinta, $kommentti, $maa, $kustp, $kohde, $projekti, $alku, $loppu) {
  return lisaa_kulurivi($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $alku,
    $loppu, $kpl, $vero, $hinta, $maa, $kommentti, "B", $kustp, $kohde,
    $projekti);
}

function lisaa_kulurivi($tilausnumero, $rivitunnus, $perheid, $perheid2, $tilino, $tuoteno, $alku, $loppu, $kpl, $vero, $hinta, $maa, $kommentti, $tyyppi, $kustp, $kohde, $projekti) {
  global $yhtiorow, $kukarow, $toim, $muokkauslukko, $laskurow;

  if ($muokkauslukko) {
    echo "<font class='error'>".t("VIRHE: Matkalaskua ei voi muokata")."!</font><br>";
    return false;
  }

  $query = "SELECT *
            from tuote
            where yhtio     = '$kukarow[yhtio]'
            and tuoteno     = '$tuoteno'
            and tuotetyyppi = '$tyyppi'";
  $tres = pupe_query($query);

  if (mysql_num_rows($tres) != 1) {
    echo "<font class='error'>".t("VIRHE: Viranomaistuote puuttuu")." (2) $tuoteno</font><br>";
    return;
  }
  else {
    $trow = mysql_fetch_assoc($tres);
  }

  $tyyppi      = $trow["tuotetyyppi"];
  $tuoteno_array = array();
  $kpl_array      = array();
  $hinta_array   = array();
  $nimitys_array = array();
  $varri_array   = array();
  $selite_array  = array();
  $errori      = "";

  list($alkupaiva, $alkuaika)      = explode(" ", $alku);
  list($alkuvv, $alkukk, $alkupp)    = explode("-", $alkupaiva);
  list($alkuhh, $alkumm)          = explode(":", $alkuaika);

  list($loppupaiva, $loppuaika)      = explode(" ", $loppu);
  list($loppuvv, $loppukk, $loppupp) = explode("-", $loppupaiva);
  list($loppuhh, $loppumm)        = explode(":", $loppuaika);

  /*
    Päivärahoilla ratkaistaan päivät
    Samalla oletetaan että osapäiväraha on aina P+tuoteno
  */

  //  Lasketaan tunnit
  $alkupp = sprintf("%02d", $alkupp);
  $alkukk = sprintf("%02d", $alkukk);
  $alkuvv = (int) $alkuvv;
  $alkuhh = sprintf("%02d", $alkuhh);
  $alkumm = sprintf("%02d", $alkumm);

  $loppupp = sprintf("%02d", $loppupp);
  $loppukk = sprintf("%02d", $loppukk);
  $loppuvv = (int) $loppuvv;
  $loppuhh = sprintf("%02d", $loppuhh);
  $loppumm = sprintf("%02d", $loppumm);

  if ($tyyppi == "A") {

    if (($alkupp >= 1 and $alkupp <= 31) and ($alkukk >= 1 and $alkukk <= 12) and $alkuvv > 0 and ($alkuhh >= 0 and $alkuhh <= 24) and ($loppupp >= 1 and $loppupp <= 31) and ($loppukk >= 1 and $loppukk <= 12) and $loppuvv > 0 and ($loppuhh >= 0 and $loppuhh <= 24)) {
      $alku  = mktime($alkuhh, $alkumm, 0, $alkukk, $alkupp, $alkuvv);
      $loppu = mktime($loppuhh, $loppumm, 0, $loppukk, $loppupp, $loppuvv);

      //  Tarkastetaan että tällä välillä ei jo ole jotain arvoa
      //  HUOM: Koitetaan tarkastaa kaikki käyttäjän matkalaskut..
      $query = "SELECT laskun_lisatiedot.laskutus_nimi,
                lasku.summa,
                lasku.tapvm tapvm,
                tilausrivi.nimitys,
                tilausrivi.tuoteno,
                date_format(tilausrivi.kerattyaika, '%d.%m.%Y') kerattyaika,
                date_format(tilausrivi.toimitettuaika, '%d.%m.%Y') toimitettuaika,
                tilausrivi.kommentti kommentti
                FROM lasku
                LEFT JOIN laskun_lisatiedot use index (yhtio_otunnus) on lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus
                LEFT JOIN tilausrivi on tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi = 'M'
                JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.tuotetyyppi = 'A'
                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                and lasku.tilaustyyppi = 'M'
                and lasku.tila         IN ('H','Y','M','P','Q')
                and lasku.liitostunnus = '$laskurow[liitostunnus]'
                and ((tilausrivi.kerattyaika >= '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and tilausrivi.kerattyaika < '$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm') or
                  (tilausrivi.kerattyaika < '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and tilausrivi.toimitettuaika > '$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm') or
                  (tilausrivi.toimitettuaika > '$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm' and tilausrivi.toimitettuaika <= '$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm'))
                GROUP BY tilausrivi.otunnus";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        $errori .= "<font class='error'>".t("VIRHE: Päivämäärä on menee päällekkäin toisen matkalaskun kanssa")."</font><br>";

        $errori .= "<table><tr><th>".t("Asiakas")."</th><th>".t("viesti")."</th><th>".t("Summa/tapvm")."</th><th>".t("Tuote")."</th><th>".t("Ajalla")."</th><th>".t("Viesti")."</th></tr>";

        while ($erow = mysql_fetch_assoc($result)) {
          $errori .=  "<tr>
                  <td>$erow[laskutus_nimi]</td>
                  <td>$erow[viesti]</td>
                  <td>$erow[summa]@$erow[tapvm]</td>
                  <td>$erow[tuoteno] - $erow[nimitys]</td>
                  <td>$erow[kerattyaika] - $erow[toimitettuaika]</td>
                  <td>$erow[kommentti]</td>
                </tr>";
        }
        $errori .= "</table><br>";
      }

      if ($loppuvv.$loppukk.$loppupp > date("Ymd")) {
        $errori .= "<font class='error'>".t("VIRHE: Matkalaskua ei voi tehdä etukäteen!")."</font><br>";
      }

      $paivat    = 0;
      $osapaivat    = 0;
      $puolipaivat = 0;
      $ylitunnit    = 0;
      $tunnit    = 0;
      $varri     = "1"; // Kotimaan kokopäiväraha oletuksena

      // Montako tuntia on oltu matkalla?
      $tunnit = ($loppu - $alku) / 3600;
      $paivat = floor($tunnit / 24);

      $alkuaika  = "$alkuvv-$alkukk-$alkupp $alkuhh:$alkumm:00";
      $loppuaika = "$loppuvv-$loppukk-$loppupp $loppuhh:$loppumm:00";

      $ylitunnit = $tunnit - ($paivat * 24);

      //var = 1 Kotimaan kokopäiväraha
      //var = 2 Kotimaan osapäiväraha
      //var = 3 Kotimaan puolitettu kokopäiväraha
      //var = 4 Kotimaan puolitettu osapäiväraha
      //var = 5 Ulkomaan kokopäiväraha
      //var = 6 Ulkomaan puolitettu päiväraha
      //var = 7 Ulkomaan kahteenkertaan puolitettu päiväraha

      // Kotimaan matkat
      if ($trow["vienti"] == "FI") {

        if (($ylitunnit > 10 and $paivat == 0) or ($ylitunnit > 6 and $paivat > 0)) {
          /*
          Työmatkan kestoaika yli 10 tuntia --> kokopäiväraha
          Kun matkaan käytetty aika ylittää viimeisen täyden matkavuorokauden vähintään 6 tunnilla --> kokopäiväraha
          */

          $paivat++;
        }
        elseif ((($ylitunnit > 6 and $paivat == 0) or ($ylitunnit >= 2 and $paivat > 0))) {
          /*
          Työmatkan kestoaika yli 6 tuntia --> osapäiväraha
          Kun matkaan käytetty aika ylittää viimeisen täyden matkavuorokauden vähintään 2 tunnilla --> osapäiväraha
          */

          //Osapäivärahan tiedot löytyvät $trow malli ja myymalahinta
          $osapaivat++;

          $tuoteno_array[1] = $tuoteno;
          $kpl_array[1]    = $osapaivat;
          $hinta_array[1]    = $trow['myymalahinta'];
          $nimitys_array[1] = $trow['malli'];
          $varri_array[1]   = "2";   // Kotimaan osapäiväraha
          $selite_array[1]  = "$tuoteno - {$trow['malli']} á ".(float) round($trow['myymalahinta'], 2);
        }
      }
      // Ulkomaanmatkat
      elseif ($trow["vienti"] != "FI") {

        // Ulkomaan kokopäiväraha
        $varri = "5";

        if ($ylitunnit > 6 and $ylitunnit < 10 and $paivat == 0) {
          /*Palkansaajalla on oikeus kysymyksessä olevaa maata varten vahvistettuun päivärahaan,
           * jos ulkomaille tehty työmatka on kestänyt vähintään 10 tuntia.
           * Mikäli työmatkaan käytetty kokonaisaika jää alle 10 tunnin,
           * suoritetaan päiväraha kotimaan matkojen säännösten ja määrien mukaisesti.
           */
          //Kotimaan matkojen säännöstö
          /*
           *  Työmatkan kestoajasta riippuen päivärahan enimmäismäärät ovat:
            Työmatkan kestoaika   Päivärahan enimmäismäärä
            euro
            yli 6 tuntia (osapäiväraha)   16,00
            yli 10 tuntia (kokopäiväraha)   36,00
            kun matkaan käytetty aika ylittää
            viimeisen täyden matkavuorokauden
            - vähintään 2 tunnilla   16,00
            - yli 6 tunnilla   36,00

            Jos palkansaaja jonakin matkavuorokautena saa ilmaisen tai matkalipun hintaan sisältyneen ruoan,
           * päivärahan enimmäismäärä on puolet 1 momentin mukaisista määristä.
           * Ilmaisella ruoalla tarkoitetaan kokopäivärahan kysymyksessä ollen kahta ja osapäivärahan kysymyksessä ollen yhtä ilmaista ateriaa.
           */
          //eli yli 6 alle 10 tarkoittaa puolitettua ulkomaanpäivärahaa

          $puolipaivat++;

          $tuoteno_array[1] = $tuoteno;
          $kpl_array[1]    = $puolipaivat;
          $hinta_array[1]    = round($trow['myyntihinta']/2, 2);
          $nimitys_array[1] = $trow['nimitys'] . " " .t("Puolitettu korvaus");
          $varri_array[1]   = "6";   // Ulkomaan puolipäiväraha
          $selite_array[1]  = "$tuoteno - {$nimitys_array[1]} á ".(float) $hinta_array[1];
        }
        elseif ($ylitunnit >= 10) {
          /*
          Jos työmatkaan käytetty aika ylittää viimeisen ulkomaan alueella tai sieltä lähteneessä laivassa tai lentokoneessa päättyneen täyden matkavuorokauden yli kymmenellä tunnilla,
          palkansaajalla on oikeus viimeksi päättyneeltä matkavuorokaudelta maksettuun ulkomaanpäivärahaan.
          */

          $paivat++;
        }
        elseif ($paivat > 0 and $ylitunnit >= 2 and $ylitunnit < 10) {
          /*
          Suomeen palattaessa palkansaajalla on oikeus puoleen viimeksi päättyneeltä matkavuorokaudelta maksetusta ulkomaanpäivärahasta,
          jos työmatkaan käytetty aika ylittää viimeisen ulkomaan alueella tai sieltä lähteneessä laivassa tai lentokoneessa päättyneen täyden matkavuorokauden yli kahdella tunnilla.
          */
          //Eli ei osapäiväraha vaan puolitettu korvaus ulkomaanpäivärahasta -> puolipäiväraha

          $puolipaivat++;

          $tuoteno_array[1] = $tuoteno;
          $kpl_array[1]    = $puolipaivat;
          $hinta_array[1]    = round($trow['myyntihinta']/2, 2);
          $nimitys_array[1] = $trow['nimitys'] . " " .t("Puolitettu korvaus");
          $varri_array[1]   = "6";   // Ulkomaan puolipäiväraha
          $selite_array[1] = "$tuoteno - {$nimitys_array[1]} á ".(float) $hinta_array[1];
        }
      }

      if ($paivat == 0 and $osapaivat == 0 and $puolipaivat == 0) {
        $errori .= "<font class='error'>".t("VIRHE: Liian lyhyt aikaväli")."</font><br>";
      }

      //  Lisätään myös saldoton isatuote jotta tiedämme mistä osapäiväraha periytyy!
      if ($paivat > 0 or $osapaivat > 0 or $puolipaivat > 0) {
        $tuoteno_array[0] = $tuoteno;
        $kpl_array[0]     = $paivat;
        $hinta_array[0]   = $trow["myyntihinta"];
        $nimitys_array[0] = $trow["nimitys"];
        $varri_array[0]    = $varri;
        $selite_array[0]  = trim("$trow[tuoteno] - $trow[nimitys] á ".(float) $trow["myyntihinta"]);
      }

    }
    else {
      $errori .= "<font class='error'>".t("VIRHE: Päivärahalle on annettava alku ja loppuaika")."</font><br>";
    }
  }
  elseif ($tyyppi == "B") {
    if ($kpl == 0) {
      $errori .= "<font class='error'>".t("VIRHE: kappalemäärä on annettava")."</font><br>";
    }

    if ($kommentti == "" and $trow["kommentoitava"] != "") {
      $errori .= "<font class='error'>".t("VIRHE: Kululle on annettava selite")."</font><br>";
    }

    if ($trow["myyntihinta"] > 0) {
      $hinta = $trow["myyntihinta"];
    }

    $hinta = (float) str_replace(",", ".", $hinta);
    $kpl = (float) str_replace(",", ".", $kpl);

    if ($hinta <= 0) {
      $errori .= "<font class='error'>".t("VIRHE: Kulun hinta puuttuu")."</font><br>";
    }

    $tuoteno_array[0] = $trow["tuoteno"];
    $kpl_array[0]    = $kpl;
    $hinta_array[0]    = $hinta;
    $nimitys_array[0] = $trow["nimitys"];
    $varri_array[0]    = 0;
    $selite_array[0]  = "$trow[tuoteno] - $trow[nimitys] $kpl kpl á ".(float) $hinta;
  }

  $selite_array[0] .= "<br>".t("Ajalla").": $alkupp.$alkukk.$alkuvv ".t("klo").". $alkuhh:$alkumm - $loppupp.$loppukk.$loppuvv ".t("klo").". $loppuhh:$loppumm";
  $selite_array[1] .= "<br>".t("Ajalla").": $alkupp.$alkukk.$alkuvv ".t("klo").". $alkuhh:$alkumm - $loppupp.$loppukk.$loppuvv ".t("klo").". $loppuhh:$loppumm";

  //  poistetan return carriage ja newline -> <br>
  $kommentti = str_replace("\n", "<br>", str_replace("\r", "", $kommentti));

  if ($kommentti != "") {
    $selite_array[0] .= "<br><i>$kommentti</i>";
    $selite_array[1] .= "<br><i>$kommentti</i>";
  }

  //  Lisätään annetut rivit
  $perhe_id = null;

  if ($errori == "") {

    ksort($tuoteno_array);

    foreach ($tuoteno_array as $indeksi => $lisaa_tuoteno) {

      //  Haetaan tuotteen tiedot
      $query = "SELECT *
                FROM tuote
                JOIN tili ON (tili.yhtio = tuote.yhtio and tili.tilino = tuote.tilino)
                WHERE tuote.yhtio      = '$kukarow[yhtio]'
                and tuote.tuotetyyppi  = '$tyyppi'
                and tuote.tuoteno      = '$lisaa_tuoteno'
                and tuote.tilino      != ''";
      $tres = pupe_query($query);

      if (mysql_num_rows($tres) == 1) {

        $trow = mysql_fetch_assoc($tres);

        //  Ratkaistaan alv..
        if ($tyyppi == "B") {
          //  Haetaan tuotteen oletusalv jos ollaan ulkomailla, tälläin myös kotimaan alv on aina zero
          if ($maa != "" and $maa != $yhtiorow["maa"]) {
            if ($alvulk == "") {
              $query = "SELECT *
                        FROM tuotteen_alv
                        WHERE yhtio = '$kukarow[yhtio]'
                        AND maa     = '$maa'
                        AND tuoteno = '$tuoteno'
                        LIMIT 1";
              $alhire = pupe_query($query);
              $alvrow = mysql_fetch_assoc($alhire);
              $alvulk = $alvrow["alv"];
            }
            $vero = 0;
          }
        }
        else {
          $vero = 0;
        }

        //  Otetaan korvaava tilinumero
        if ($tilino == "" or $toim != "SUPER") {
          $tilino = $trow["tilino"];
        }

        //  Matkalaskujen päivät pitää saada omille riveilleen päivän mukaan, eli jokainen matkalaskun päivä on omalla tilausrivillään
        //  Kulut viedään aina vain yhdelle riville
        //  Indeksi on 0 kun käsitellään kokopäivärahoja ja 1 kun käsitellään puolipäivärahoja
        for ($i = 1; $i <= $kpl_array[$indeksi]; $i++) {
          // Setataan nämä tarkoituksella loopin sisällä, ku niitä säädetään joskus täs loopis
          $hinta          = str_replace(",", ".", $hinta_array[$indeksi]);
          $nimitys       = $nimitys_array[$indeksi];
          $var         = $varri_array[$indeksi];
          $ilmaiset_lounaat = 0;

          if ($tyyppi == "B") {
            // Kulut viedään aina vain yhdelle riville (siksi $i = $kpl_array[$indeksi])
            $_alkuaika  = $alku ? $alku : date('Y-m-d H:i:s');
            $_loppuaika = $loppu ? $loppu : date('Y-m-d H:i:s');
            $ins_kpl    = $kpl_array[$indeksi];
            $i         = $kpl_array[$indeksi];
          }
          else {
            $ins_kpl = 1;

            if (!isset($_alkuaika) or ($indeksi == 0 and $i == 1)) {
              $_alkuaika = $alkuaika;
            }
            else {
              $_alkuaika = $_loppuaika;
            }

            if ($indeksi == 0 and (($kpl_array[$indeksi] > 1 and $i != $kpl_array[$indeksi]) or isset($kpl_array[1]))) {
              $_loppuaika = date('Y-m-d H:i:s', strtotime($_alkuaika . ' + 24 hours'));
            }
            else {
              $_loppuaika = $loppuaika;
            }
          }

          if ($tyyppi == "A") {
            $ilmaiset_lounaat = tarkista_loytyyko_paivalle_matkalasku($_alkuaika, $_loppuaika, $tilausnumero);

            if ($trow["vienti"] == 'FI') {
              if ($var == 1 and $ilmaiset_lounaat >= 2) {
                $var    = 3;
                $hinta    = $hinta / 2;
                $nimitys = $nimitys.' '.t("Puolitettu korvaus");
              }
              elseif ($var == 2 and $ilmaiset_lounaat >= 1) {
                $var    = 4;
                $hinta    = $hinta / 2;
                $nimitys = $nimitys.' '.t("Puolitettu korvaus");
              }
            }
            else {
              if ($ilmaiset_lounaat >= 2) {
                $var    = 6;
                $hinta    = $hinta / 2;
                $nimitys = $nimitys.' '.t("Puolitettu korvaus");
              }
            }
          }

          if ($trow['myymalahinta'] > 0 and strpos($trow['nimitys'], 'Kilometrikorvaus') !== false) {
            // Kilometriraja HUOM! Tätä pitää muuttaa jos TES muuttuu
            $kilometriraja = 5000;

            $korvatut_kilometrit = hae_matkustajan_kilometrit($tuoteno, $laskurow['toim_ovttunnus']);

            $kokonaiskappalemaara = $ins_kpl;
            $jaljellaoleva_maara = 0;

            // Kun rivin hintaa tarvitsee muuttaa
            if ($korvatut_kilometrit + $ins_kpl > $kilometriraja) {

              if ($korvatut_kilometrit <= $kilometriraja) {
                $ins_kpl = $kilometriraja - $korvatut_kilometrit;
              }
              else {
                $ins_kpl = 0;
              }

              $jaljellaoleva_maara = $kokonaiskappalemaara - $ins_kpl;
              // Jos normihinnalla jää vielä lisättävää
              if ($ins_kpl > 0) {
                list ($perhe_id, $perheid2, $rivitunnus) = tee_matkalaskurivin_kirjaukset(get_defined_vars());
              }

              // Jos erikoishinnalla on lisättävää
              if ($jaljellaoleva_maara > 0 and $trow['myymalahinta'] > 0) {
                $ins_kpl = $jaljellaoleva_maara;
                $hinta = $trow['myymalahinta'];
                list ($perhe_id, $perheid2, $rivitunnus) = tee_matkalaskurivin_kirjaukset(get_defined_vars());
              }
            }
            else {
              list ($perhe_id, $perheid2, $rivitunnus) = tee_matkalaskurivin_kirjaukset(get_defined_vars());
            }
          }
          else {
            list ($perhe_id, $perheid2, $rivitunnus) = tee_matkalaskurivin_kirjaukset(get_defined_vars());
          }
        }
      }
      else {
        echo "<font class='error'>".t("VIRHE: Viranomaistuote puuttuu")." (1) $lisaa_tuoteno</font><br>";
      }
    }

    //tehdään koko matkalaskusta yksi ostovelka tiliöinti
    korjaa_ostovelka($tilausnumero);
  }

  //koska poista edelliset matkalasku rivit update tyyppi D
  //siivotaan deleted tilausrivit pois
  $query = "DELETE
            FROM tilausrivi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND otunnus = '{$tilausnumero}'
            AND tyyppi  = 'D'";
  pupe_query($query);

  return $errori;
}

function korjaa_ostovelka($tilausnumero) {
  global $yhtiorow, $kukarow, $toim, $muokkauslukko, $laskurow;

  if ($muokkauslukko or $tilausnumero != $laskurow["tunnus"]) {
    echo "<font class='error'>".t("VIRHE: Matkalaskua ei voi muokata")."!</font><br>";
    return false;
  }

  $debug = 0;

  if ($debug == 1) echo "Korjataan ostovelka laskulle $tilausnumero<br>";

  if ($yhtiorow["ostovelat"] == "") {
    echo t("VIRHE: Yhtiön ostovelkatili puuttuu")."!<br>";
    return false;
  }

  // haetaan tiliöinti tilauskohtaisesti
  $query = "SELECT sum((-1*tiliointi.summa)) summa, count(*) kpl
            FROM tiliointi
            WHERE tiliointi.yhtio   = '$kukarow[yhtio]'
            AND tiliointi.ltunnus   = '$tilausnumero'
            AND tiliointi.korjattu  = ''
            AND tiliointi.tilino   != '$yhtiorow[ostovelat]'";
  $summares = pupe_query($query);
  $summarow = mysql_fetch_assoc($summares);

  if ($yhtiorow["kirjanpidon_tarkenteet"] == "K") {
    // Etsitään kulutiliöinnit
    $query = "SELECT tiliointi.kustp, tiliointi.kohde, tiliointi.projekti
              FROM tiliointi
              JOIN tili ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino)
              LEFT JOIN taso ON (tili.yhtio = taso.yhtio and tili.ulkoinen_taso = taso.taso and taso.tyyppi = 'U')
              WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
              AND tiliointi.ltunnus  = '$tilausnumero'
              AND tiliointi.korjattu = ''
              AND tiliointi.tilino   not in ('$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]')
              AND (taso.kayttotarkoitus is null or taso.kayttotarkoitus  in ('','O'))
              ORDER BY abs(tiliointi.summa) DESC
              LIMIT 1";
    $kpres = pupe_query($query);
    $kprow = mysql_fetch_assoc($kpres);

    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["ostovelat"], $kprow["kustp"], $kprow["kohde"], $kprow["projekti"]);
  }
  else {
    list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["ostovelat"]);
  }

  //  Onko meillä jo ostovelkatiliöinti vai perustetaanko uusi?
  $query = "SELECT tiliointi.tunnus
            FROM tiliointi
            WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
            and tiliointi.ltunnus  = '$tilausnumero'
            and tiliointi.tilino   = '$yhtiorow[ostovelat]'
            and tiliointi.korjattu = ''";
  $velkares = pupe_query($query);

  if (mysql_num_rows($velkares) == 1) {
    $velkarow = mysql_fetch_assoc($velkares);
    if ($debug == 1) echo "Löydettiin ostovelkatiliöinti tunnuksella $velkarow[tunnus] tiliöintejä ($summarow[kpl]) kpl<br>";

    $query = "UPDATE tiliointi SET
              summa       = '$summarow[summa]',
              tapvm       = '$laskurow[tapvm]',
              kustp       = '{$kustp_ins}',
              kohde       = '{$kohde_ins}',
              projekti    = '{$projekti_ins}',
              vero        = 0,
              tosite      = '$tositenro'
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$velkarow[tunnus]'";
    $updres = pupe_query($query);
  }
  else {
    if ($debug == 1) echo "Luodaan uusi ostovelkatiliöinti<br>";

    // Ostovelka
    $query = "INSERT into tiliointi SET
              yhtio    = '$kukarow[yhtio]',
              ltunnus  = '$tilausnumero',
              lukko    = '1',
              tilino   = '$yhtiorow[ostovelat]',
              summa    = '$summarow[summa]',
              kustp    = '{$kustp_ins}',
              kohde    = '{$kohde_ins}',
              projekti = '{$projekti_ins}',
              tapvm    = '$laskurow[tapvm]',
              vero     = 0,
              tosite   = '$tositenro',
              selite   = '".t("Ostovelka")."',
              laatija  = '$kukarow[kuka]',
              laadittu = now()";
    $updres = pupe_query($query);
  }

  if ($debug == 1) echo "Korjattiin ostovelkatiliöinti uusi summa on $summarow[summa]";

  //  Päivitetään laskun summa
  if ($laskurow["tilaustyyppi"] == "M") {
    $query = "UPDATE lasku
              set summa = '".(-1 * $summarow["summa"])."'
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$tilausnumero'";
    $updres = pupe_query($query);
  }

  //haetaan matkalaskuun liittyvän tiliöinnen kokosumma
  $query = "SELECT sum((-1*summa)) summa, count(*) kpl
            FROM tiliointi
            WHERE yhtio   = '$kukarow[yhtio]'
            AND ltunnus   = '$tilausnumero'
            AND korjattu  = ''
            AND tilino   != '$yhtiorow[ostovelat]'";
  $result = pupe_query($query);
  $tiliointi_summa = mysql_fetch_assoc($result);

  //  Ollaanko vielä synkissä?
  $query = "SELECT sum(rivihinta) summa
            FROM tilausrivi
            WHERE yhtio = '$kukarow[yhtio]'
            and otunnus = '$tilausnumero'
            and tyyppi  = 'M'";
  $result = pupe_query($query);
  $rivisumma = mysql_fetch_assoc($result);

  $ero = round($rivisumma["summa"], 2) + round($tiliointi_summa["summa"], 2);

  if ($ero <> 0) {
    echo "  <font class='error'>".t("VIRHE: Matkalasku ja kirjanpito ei täsmää!!!")."</font><br>
        <font class='message'>".t("Heitto on")." $ero [rivit $rivirow[summa]] (kp $tiliointi_summa[summa])</font><br>";
  }
}

function erittele_rivit($tilausnumero) {
  global $yhtiorow, $kukarow, $toim, $muokkauslukko, $laskurow, $verkkolaskuvirheet_ok;

  if ($muokkauslukko or $tilausnumero != $laskurow["tunnus"]) {
    echo "<font class='error'>".t("VIRHE: Matkalaskua ei voi muokata")."!</font><br>";
    return false;
  }

  if ($laskurow["ebid"] == "") {
    return false;
  }

  $query = "SELECT tunnus
            FROM toimi
            WHERE yhtio          = '$kukarow[yhtio]'
            and tunnus           = '$laskurow[liitostunnus]'
            and laskun_erittely != ''";
  $toimires = pupe_query($query);

  if (mysql_num_rows($toimires) == 0) {
    echo t("VIRHE: Toimittajan laskuja ei saa eritellä")."!";
    return false;
  }

  $query = "SELECT tunnus
            FROM tilausrivi
            WHERE yhtio  = '$kukarow[yhtio]'
            and otunnus  = '$tilausnumero'
            and tyyppi  != 'D'
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    return true;
  }

  $query = "UPDATE tiliointi
            SET korjattu = '$kukarow[kuka]',
            korjausaika = now()
            WHERE yhtio = '$kukarow[yhtio]'
            and ltunnus = '$tilausnumero'";
  $result = pupe_query($query);

  $file = $verkkolaskuvirheet_ok."/$laskurow[ebid]";

  if (file_exists($file)) {

    // luetaan file muuttujaan
    $xmlstr = file_get_contents($file);

    if ($xmlstr === FALSE) {
      echo "Tiedosto $file luku epäonnistui!\n";
      return "Tiedosto $file luku epäonnistui!";
    }

    // luetaan sisään xml
    $xml = simplexml_load_string($xmlstr);

    require "inc/verkkolasku-in-pupevoice.inc";

    if (count($tuotetiedot) > 0) {
      for ($i=0; $i < count($tuotetiedot); $i++) {

        //  Näyttäisikö tämä korkoriviltä, otetaan se talteen ja laitetaan myös riville?
        if (preg_match("/KORKO\s+[0-9]{2}\.[0-9]{2}\.\s+-\s+[0-9]{2}\.[0-9]{2}\./", $rtuoteno[$i]["riviinfo"])) {
          $korkosumma = (float) $rtuoteno[$i]["rivihinta"];
        }

        if ($rtuoteno[$i]["kpl"] > 0) {

          $kpl = (float) $rtuoteno[$i]["kpl"];
          $hinta = (float) $rtuoteno[$i]["rivihinta"];

          if ($hinta < 0) {
            $kpl = $kpl * -1;
            $hinta = $hinta * -1;
          }

          $kommentti = "";
          $rivihinta = (float) $kpl * (float) $hinta;

          if ($rtuoteno[$i]["laskutettuaika"] != "") {
            $kommentti .= t("Tapahtuma-aika").": ".preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})/", "\$3.\$2. \$1", $rtuoteno[$i]["laskutettuaika"]);
          }

          $kommentti .= "<br>".t("Tapahtuman selite").": ".$rtuoteno[$i]["riviinfo"];

          if (preg_match("/([A-Z]{3})\s*([0-9\.,]*)/", $rtuoteno[$i]["riviviite"], $match)) {
            $kommentti .= "<br>".t("Alkupeärinen summa").": $match[2] $match[1] ($hinta $yhtiorow[valkoodi])";
          }

          $kommentti = preg_replace("/\.{2,}/", "", $kommentti);

          //  Laitetaan tilausrivi kantaan
          $query = "INSERT into tilausrivi set
                    hyllyalue      = '0',
                    hyllynro       = '0',
                    hyllytaso      = '0',
                    hyllyvali      = '0',
                    laatija        = '$kukarow[kuka]',
                    laadittu       = now(),
                    yhtio          = '$kukarow[yhtio]',
                    tuoteno        = '$tno',
                    varattu        = '0',
                    yksikko        = '',
                    kpl            = '$kpl',
                    tilkpl         = '$kpl',
                    ale1           = '0',
                    alv            = '0',
                    netto          = 'N',
                    hinta          = '$hinta',
                    rivihinta      = '$rivihinta',
                    otunnus        = '$tilausnumero',
                    tyyppi         = 'M',
                    toimaika       = '',
                    kommentti      = '".mysql_real_escape_string($kommentti)."',
                    var            = '',
                    try            = '$trow[try]',
                    osasto         = '$trow[osasto]',
                    perheid        = '0',
                    perheid2       = '0',
                    tunnus         = '0',
                    nimitys        = '',
                    kerattyaika    = '$alkuaika',
                    toimitettuaika = '$loppuaika'";
          $insres = pupe_query($query);
          $lisatty_tun = mysql_insert_id($GLOBALS["masterlink"]);

          //  Päivitetään perheid2
          $query = "UPDATE tilausrivi
                    set perheid2 = '$lisatty_tun'
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tunnus  = '$lisatty_tun'";
          $updres = pupe_query($query);

          //  Tehdään oletustiliöinti
          $query = "INSERT into tiliointi set
                    yhtio    = '$kukarow[yhtio]',
                    ltunnus  = '$tilausnumero',
                    tilino   = '$yhtiorow[selvittelytili]',
                    kustp    = 0,
                    kohde    = 0,
                    projekti = 0,
                    tapvm    = '$laskurow[tapvm]',
                    summa    = '$rivihinta',
                    vero     = 0,
                    selite   = 'EC selvittely',
                    lukko    = '1',
                    tosite   = '$tositenro',
                    laatija  = '$kukarow[kuka]',
                    laadittu = now()";
          $result = pupe_query($query);
          $isa = mysql_insert_id($GLOBALS["masterlink"]);

          $query = "INSERT INTO tilausrivin_lisatiedot SET
                    yhtio               = '$kukarow[yhtio]',
                    luontiaika          = now(),
                    tilausrivitunnus    = '$lisatty_tun',
                    laatija             = '$kukarow[kuka]',
                    tiliointirivitunnus = '$isa',
                    kulun_kohdemaa      = '$yhtiorow[maa]',
                    kulun_kohdemaan_alv = '',
                    muutospvm           = now(),
                    muuttaja            = '$kukarow[kuka]'";
          $updres = pupe_query($query);

          korjaa_ostovelka($tilausnumero);
        }
      }
    }

    return $korkosumma;
  }
  else {
    return false;
  }
}

function listdir($start_dir = '.') {

  $files = array();
  $ohitetut_laskut = array();

  if (!is_dir($start_dir)) {
    return false;
  }

  $start_dir = rtrim($start_dir, '/');
  $file_list = explode("\n", trim(`ls $start_dir/ | sort`));

  foreach ($file_list as $file) {

    if ($file == "") {
      continue;
    }

    $filepath = $start_dir.'/'.$file;

    if (is_file($filepath)) {
      array_push($files, $filepath);
    }
  }

  return $files;
}

function hae_matkustajan_kilometrit($tuoteno, $kuka) {
  global $kukarow;

  $kilometrit = 0;
  $query = "SELECT sum(tilausrivi.kpl) yhteensa
            FROM tilausrivi
            JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND lasku.tila in ('H','Y','M','P','Q'))
            WHERE lasku.yhtio          = '{$kukarow['yhtio']}'
              AND tilausrivi.tyyppi    = 'M'
              AND tilausrivi.tuoteno   = '{$tuoteno}'
              AND lasku.toim_ovttunnus = '{$kuka}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  if (!empty($row['yhteensa'])) {
    $kilometrit = floor($row['yhteensa']);
  }
  return $kilometrit;
}

function tee_matkalaskurivin_kirjaukset($variables) {
  extract($variables);

  $rivihinta = round($ins_kpl*$hinta, 2);
  if ((int) $rivitunnus > 0) dellaa_mitatoity_rivi($tilausnumero, $rivitunnus);

  $query = "INSERT into tilausrivi set
            hyllyalue      = '0',
            hyllynro       = '0',
            hyllytaso      = '0',
            hyllyvali      = '0',
            laatija        = '$kukarow[kuka]',
            laadittu       = now(),
            yhtio          = '$kukarow[yhtio]',
            tuoteno        = '$lisaa_tuoteno',
            varattu        = '0',
            yksikko        = '$trow[yksikko]',
            kpl            = '$ins_kpl',
            tilkpl         = '$ins_kpl',
            ale1           = '0',
            erikoisale     = '{$ilmaiset_lounaat}',
            alv            = '$vero',
            netto          = 'N',
            hinta          = '$hinta',
            rivihinta      = '$rivihinta',
            otunnus        = '$tilausnumero',
            tyyppi         = 'M',
            toimaika       = '',
            kommentti      = '{$kommentti}',
            var            = '$var',
            try            = '$trow[try]',
            osasto         = '$trow[osasto]',
            perheid        = '$perheid',
            perheid2       = '$perheid2',
            tunnus         = '$rivitunnus',
            nimitys        = '$nimitys',
            kerattyaika    = '$_alkuaika',
            toimitettuaika = '$_loppuaika'";
  $insres = pupe_query($query);

  $perhe_id = ($perhe_id == null) ? mysql_insert_id($GLOBALS["masterlink"]) : $perhe_id;
  $lisatty_tun = mysql_insert_id($GLOBALS["masterlink"]);

  //  Jos meillä on splitattu rivi niin pidetään nippu kasassa
  if (count($tuoteno_array) > 1) {
    $query = "UPDATE tilausrivi
              SET perheid = '$perhe_id'
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$lisatty_tun'";
    $updres = pupe_query($query);
  }

  if ((int) $perheid2 == 0) {
    $perheid2 = $lisatty_tun;

    $query = "UPDATE tilausrivi
              SET perheid2 = '$lisatty_tun'
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$lisatty_tun'";
    $updres = pupe_query($query);
  }

  //  Jos muokattiin perheen isukkia halutaan oikea kommentti!
  if ((int) $perheid2 == 0 or $perheid2 == $lisatty_tun) {
    $tapahtumarow["kommentti"] = $kommentti;
  }

  $rivitunnus = 0;

  // lisätään tiliointiin tilausrivikohtaisesti tiliöintejä
  // Netotetaan alvi
  if ($vero != 0) {
    $alv = round($rivihinta - $rivihinta / (1 + ($vero / 100)), 2);
    $rivihinta -= $alv;
  }

  if ($kpexport != 1 and strtoupper($yhtiorow['maa']) == 'FI') $tositenro = 0; // Jos tätä ei tarvita

  if ($toim == "SUPER" and $tilino > 0 and $trow["tilino"] != $tilino) {
    echo "<font class='message'>".t("HUOM: tiliöidään poikkeavalle tilille '$tilino'<br>");
    $trow["tilino"] = $tilino;
  }

  list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($tilino, $kustp, $kohde, $projekti);

  // Kulutili
  $query = "INSERT into tiliointi set
            yhtio    = '$kukarow[yhtio]',
            ltunnus  = '$tilausnumero',
            tilino   = '{$tilino}',
            kustp    = '{$kustp_ins}',
            kohde    = '{$kohde_ins}',
            projekti = '{$projekti_ins}',
            tapvm    = '$laskurow[tapvm]',
            summa    = '$rivihinta',
            vero     = '$vero',
            selite   = '".mysql_real_escape_string($selite_array[$indeksi])."',
            lukko    = '',
            tosite   = '$tositenro',
            laatija  = '$kukarow[kuka]',
            laadittu = now()";
  $result = pupe_query($query);
  $isa = mysql_insert_id($GLOBALS["masterlink"]); // Näin löydämme tähän liittyvät alvit....

  if ($vero != 0) {
    // jos tilausrivillä on alvi tehdään sille oma tiliointi
    $query = "INSERT into tiliointi set
              yhtio     = '$kukarow[yhtio]',
              ltunnus   = '$tilausnumero',
              tilino    = '$yhtiorow[alv]',
              kustp     = 0,
              kohde     = 0,
              projekti  = 0,
              tapvm     = '$laskurow[tapvm]',
              summa     = '$alv',
              vero      = 0,
              selite    = '".mysql_real_escape_string($selite_array[$indeksi])."',
              lukko     = '1',
              laatija   = '$kukarow[kuka]',
              laadittu  = now(),
              aputunnus = $isa";
    $result = pupe_query($query);
  }

  // koska tilointi tehdään tilausrivi kohtaisesti, niin jokaiselle tilausriville pitää liittää myös tilausrivin_lisatiedot,
  // jotta pystymme linkkaamaan tilausrivin sekä sen tiliointirivin
  $query = "INSERT INTO tilausrivin_lisatiedot SET
            yhtio               = '$kukarow[yhtio]',
            luontiaika          = now(),
            tilausrivitunnus    = '$lisatty_tun',
            laatija             = '$kukarow[kuka]',
            tiliointirivitunnus = '$isa',
            kulun_kohdemaa      = '$maa',
            kulun_kohdemaan_alv = '$alvulk',
            muutospvm           = now(),
            muuttaja            = '$kukarow[kuka]'";
  $updres = pupe_query($query);

  if (strpos($lisaa_tuoteno, "PR") === false) {
    $perheid = 0;
    $perheid2 = 0;
  }

  return array ($perhe_id, $perheid2, $rivitunnus);

}

require "inc/footer.inc";
