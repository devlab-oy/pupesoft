<?php

$otsikko = 'Maksuehdot';
$kentat = 'tunnus, teksti, rel_pvm, abs_pvm, kassa_relpvm, kassa_abspvm, kassa_alepros, jarjestys, tunnus, kaytossa HIDDEN_kaytossa';
$jarjestys = 'jarjestys, teksti';

if ($nayta_poistetut == "") {
  $rajauslisa = " and maksuehto.kaytossa != 'E' ";
}
else {
  $rajauslisa  = "";
}

if ($tunnus != '' and $upd != 1) {
  // Toimitustavan saa poistaa mikäli millään asiakkaalla ei ole sitä käytössä (tsekataan tuoterivi.inc)
  $kaytossa_disabled = "disabled";

  $query = "SELECT teksti
            FROM maksuehto
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$tunnus}'";
  $toimitustapa_check_res = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($toimitustapa_check_res) == 1) {

    $query = "SELECT tunnus
              FROM asiakas
              WHERE maksuehto = '{$tunnus}'
              AND yhtio       = '{$kukarow['yhtio']}'
              AND laji        NOT IN ('P', 'R')";
    $chkres1 = mysql_query($query) or pupe_error($query);

    $query = "SELECT tunnus
              FROM lasku
              WHERE maksuehto = '{$tunnus}'
              AND yhtio       = '{$kukarow['yhtio']}'
              AND ((tila = 'L' AND alatila IN ('A','C')) OR tila = 'N')";
    $chkres2 = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($chkres1) == 0 and mysql_num_rows($chkres2) == 0) {
      $kaytossa_disabled = '';
    }
    else {
      $otsikko_lisatiedot .= "<font class='message'>".t("HUOM: Maksuehtoa ei voi poistaa, koska se on käytössä").":";

      $otsikko_lisatiedot .= "<ul>";
      if (mysql_num_rows($chkres1) > 0) {
        $otsikko_lisatiedot .= "<li>".mysql_num_rows($chkres1)." ".t("asiakkaalla")."</li>";
      }

      if (mysql_num_rows($chkres2) > 0) {
        $otsikko_lisatiedot .= "<li>".mysql_num_rows($chkres2)." ".t("toimittamattomalla tai kesken olevalla myyntitilauksella")."</li>";
      }

      $otsikko_lisatiedot .= "</ul></font>";
    }
  }
}
