<?php
$otsikko = "Toimitustavat";
$kentat = "tunnus, selite, tulostustapa, sopimusnro, nouto, ei_pakkaamoa, extranet, rahtikirja, jarjestys";
$jarjestys = "jarjestys, selite";

if ($tunnus != '' and $upd != 1) {
  // Toimitustavan saa poistaa mikäli millään asiakkaalla ei ole sitä käytössä (tsekataan tuoterivi.inc)
  $poistolukko = "ON";

  $query = "  SELECT selite, vak_kielto
        FROM toimitustapa
        WHERE yhtio = '{$kukarow['yhtio']}'
        AND tunnus = '{$tunnus}'";
  $toimitustapa_check_res = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($toimitustapa_check_res) == 1) {
    $toimitustapa_check_row = mysql_fetch_assoc($toimitustapa_check_res);

    $toita = $toimitustapa_check_row['selite'];

    $query = "  SELECT tunnus 
          FROM asiakas 
          WHERE toimitustapa = '{$toita}' 
          AND yhtio = '{$kukarow['yhtio']}' 
          AND laji NOT IN ('P', 'R')";
    $chkres1 = pupe_query($query);

    $query = "  SELECT tunnus
           FROM lasku 
          WHERE toimitustapa = '{$toita}' 
          AND yhtio = '{$kukarow['yhtio']}' 
          AND ((tila = 'L' AND alatila IN ('A','B','C')) OR tila = 'N')";
    $chkres2 = pupe_query($query);

    $query = "  SELECT tunnus 
          FROM rahtimaksut
          WHERE toimitustapa = '{$toita}' 
          AND yhtio = '{$kukarow['yhtio']}'";
    $chkres3 = pupe_query($query);

    $query = "  SELECT tunnus
          FROM rahtisopimukset 
          WHERE toimitustapa = '{$toita}' 
          AND yhtio = '{$kukarow['yhtio']}'";
    $chkres4 = pupe_query($query);

    $query = "  SELECT tunnus
          FROM asiakkaan_avainsanat
          WHERE avainsana = '{$toita}'
          AND yhtio = '{$kukarow['yhtio']}'";
    $chkres5 = pupe_query($query);

    if (mysql_num_rows($chkres1) == 0 and mysql_num_rows($chkres2) == 0 and mysql_num_rows($chkres3) == 0 and mysql_num_rows($chkres4) == 0 and mysql_num_rows($chkres5) == 0 and $toimitustapa_check_row['vak_kielto'] == '') {
      $poistolukko = '';
    }
    else {
      $otsikko_lisatiedot .= "<font class='message'>".t("HUOM: Toimitustapaa ei voi poistaa, koska se on käytössä").":";

      $otsikko_lisatiedot .= "<ul>";
      if (mysql_num_rows($chkres1) > 0) {
        $otsikko_lisatiedot .= "<li>".mysql_num_rows($chkres1)." ".t("asiakkaalla")."</li>";
      }

      if (mysql_num_rows($chkres2) > 0) {
        $otsikko_lisatiedot .= "<li>".mysql_num_rows($chkres2)." ".t("toimittamattomalla tai kesken olevalla myyntitilauksella")."</li>";
      }

      if (mysql_num_rows($chkres3) > 0) {
        $otsikko_lisatiedot .= "<li>".mysql_num_rows($chkres3)." ".t("rahtimaksulla")."</li>";
      }
      
      if (mysql_num_rows($chkres4) > 0) {
        $otsikko_lisatiedot .= "<li>".mysql_num_rows($chkres4)." ".t("rahtisopimuksella")."</li>";
      }

      if (mysql_num_rows($chkres5) > 0) {
        $otsikko_lisatiedot .= "<li>".mysql_num_rows($chkres5)." ".t("asiakkaan avainsanassa")."</li>";
      }

      if ($toimitustapa_check_row['vak_kielto'] != '' and $toimitustapa_check_row['vak_kielto'] != 'K') {
        $otsikko_lisatiedot .= "<li>".t("VAK-tuotteiden toimituksessa käytetään muuta toimitustapaa")."</li>";
      }

      $otsikko_lisatiedot .= "</ul></font>";
    }
  }
}
