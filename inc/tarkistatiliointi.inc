<?php

$ok    = 0;
$summa = (float) $summa;
$tili  = trim($tili);
$ulos  = "";
$asiakastoimittajavirhe = FALSE;

if (!isset($mistatullaan)) $mistatullaan = "";

if ($summa != 0 or strlen($tili) > 0) {

  if (strlen($tili) > 0) {

    // Onko suoraan tilinumero
    $query = "SELECT tilino, toimijaliitos, kustp, kohde, projekti,tiliointi_tarkistus, nimi, manuaali_esto
              FROM tili
              WHERE yhtio = '$kukarow[yhtio]'
              AND tilino  = '$tili'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {

      $ok = 1;

      // Kokeillaan nimihakua
      $query = "SELECT tilino, toimijaliitos, kustp, kohde, projekti,tiliointi_tarkistus, nimi
                FROM tili
                WHERE yhtio = '$kukarow[yhtio]'
                AND nimi    LIKE '%$tili%'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        $virhe .= t("Yksikään tili ei sovi");
      }
      elseif (mysql_num_rows($result) == 1) {
        $ok = 0;
        $xrow = mysql_fetch_assoc($result);
      }
      else {
        $virhe .= t("Useita tilejä löytyi");
        $ulos = "<select name='$selausnimi'>";

        while ($xrow = mysql_fetch_assoc($result)) {
          $ulos .= "<option value='$xrow[tilino]'>$xrow[tilino] $xrow[nimi]</option>";
        }

        $ulos .= "<option value=''>".t("Ei valintaa")."</option>";
        $ulos .= "</select>";
      }
    }
    elseif (mysql_num_rows($result) == 1) {
      $ok = 0;
      $xrow = mysql_fetch_assoc($result);
    }
    else {
      $ok = 1;
    }
  }

  if (isset($tiliasiakasid) and $tiliasiakasid > 0) {
    $liitos = 'A';
    $liitostunnus = $tiliasiakasid;
  }

  if (isset($tilitoimittajaid) and $tilitoimittajaid > 0) {
    $liitos = 'T';
    $liitostunnus = $tilitoimittajaid;
  }

  if ($ok == 0 and strlen($tili) > 0) {

    $tili = $xrow['tilino'];

    // Jos muistiotosite ja tilille on liitettävä asiakas tai toimittaja
    if ($tositetila == "X" and $xrow["toimijaliitos"] != "" and (int) $liitostunnus == 0 and (!isset($avaavatase) or $avaavatase != 'joo')) {
      $virhe .= t("Tositteeseen on liitettävä asiakas tai toimittaja")."!<br />";
      $ok = 1;
      $asiakastoimittajavirhe = TRUE;
    }

    // Tsekataan pakolliset ksutannuspaikat, kohteet ja projektit
    if (!isset($avaavatase) or $avaavatase != 'joo') {
      $pakotsek = tiliointi_tarkistus($xrow['tiliointi_tarkistus'], $kustp_tark, $kohde_tark, $projekti_tark);

      if (!empty($pakotsek)) {
        $virhe .= $pakotsek;
        $ok = 1;
      }
    }

    if ($xrow['kustp'] != 0 and isset($kustp) and $kustp == 0 and (!isset($avaavatase) or $avaavatase != 'joo')) {
      $virhe .= t("Pakollinen kustannuspaikka puuttui")."!<br />";
      $ok = 1;
      $kustp = $xrow['kustp'];
    }

    if ($xrow['kohde'] != 0 and isset($kohde) and $kohde == 0 and (!isset($avaavatase) or $avaavatase != 'joo')) {
      $virhe .= t("Pakollinen kohde puuttui")."!<br />";
      $ok = 1;
      $kohde = $xrow['kohde'];
    }

    if ($xrow['projekti'] != 0 and isset($projekti) and $projekti == 0 and (!isset($avaavatase) or $avaavatase != 'joo')) {
      $virhe .= t("Pakollinen projekti puuttui")."!<br />";
      $ok = 1;
      $projekti = $xrow['projekti'];
    }

    if ($xrow['manuaali_esto'] != '' and (!isset($avaavatase) or $avaavatase != 'joo')) {
      $virhe .= t("Tilille on asetettu manuaalisen lisäyksen/muokkauksen esto")."!";
      $ok = 1;
    }
  }
}

if ($summa == 0 and $mistatullaan != 'ulask.php') {
  $virhe .= t("Summa puuttuu")."!<br />";
  $ok = 1;
}

if (strlen($tili) == 0) {
  $virhe .= t("Tili puuttuu")."!<br />";
  $ok = 1;
}
