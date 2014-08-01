<?php

require 'inc/parametrit.inc';

echo "<font class='head'>".t("Suoraveloitusten kohdistus suorituksiin")."</font><hr>";

if ($tee == 'V') {

  // Lasku on valittu ja sitä tiliöidään (suoritetaan)
  $query = "SELECT *
            FROM tiliointi
            WHERE tunnus = '$stunnus'
            and yhtio    = '$kukarow[yhtio]'
            and tilino   = '$yhtiorow[selvittelytili]'";
  $result = pupe_query($query);
  $tiliointirow = mysql_fetch_assoc($result);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Suoritus katosi")."!</font><br>";
    exit;
  }

  $query = "SELECT *
            FROM lasku
            WHERE tunnus = '$tunnus'
            and yhtio    = '$kukarow[yhtio]'
            and tila     = 'Q'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Lasku katosi, tai sen on joku jo suorittanut")."!</font><br>";
    exit;
  }

  $laskurow = mysql_fetch_assoc($result);

  list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["ostovelat"]);

  // Oletustiliöinnit
  // Ostovelat
  $query = "INSERT INTO tiliointi SET
            yhtio    = '$kukarow[yhtio]',
            ltunnus  = '$laskurow[tunnus]',
            tilino   = '$yhtiorow[ostovelat]',
            kustp    = '{$kustp_ins}',
            kohde    = '{$kohde_ins}',
            projekti = '{$projekti_ins}',
            tapvm    = '$tiliointirow[tapvm]',
            summa    = '$laskurow[summa]',
            vero     = 0,
            lukko    = '',
            laatija  = '$kukarow[kuka]',
            laadittu = now()";
  $xresult = pupe_query($query);

  list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["selvittelytili"]);

  // Rahatili
  $query = "INSERT INTO tiliointi SET
            yhtio    = '$kukarow[yhtio]',
            ltunnus  = '$laskurow[tunnus]',
            tilino   = '$yhtiorow[selvittelytili]',
            kustp    = '{$kustp_ins}',
            kohde    = '{$kohde_ins}',
            projekti = '{$projekti_ins}',
            tapvm    = '$tiliointirow[tapvm]',
            summa    = -1 * $laskurow[summa],
            vero     = 0,
            lukko    = '',
            laatija  = '$kukarow[kuka]',
            laadittu = now()";
  $xresult = pupe_query($query);

  $query = "UPDATE lasku set
            tila         = 'Y',
            mapvm        = '$tiliointirow[tapvm]',
            maksu_kurssi = 1
            WHERE tunnus = '$tunnus'";
  $xresult = pupe_query($query);
  $tee = '';

}

//Näytetään kohdistamattomat
if ($tee == '') {

  echo "<table>";

  // katotaan jos meillä on jotain selvittelytilejä pankkitilien takana
  $query = "SELECT group_concat(concat('\'',oletus_selvittelytili,'\'')) oletus_selvittelytilit
            FROM yriti
            WHERE yhtio                = '$kukarow[yhtio]'
            AND oletus_selvittelytili != ''";
  $result = pupe_query($query);
  $trow = mysql_fetch_assoc($result);

  $selvittelytilit = "'$yhtiorow[selvittelytili]'";

  if ($trow["oletus_selvittelytilit"] != "") {
    $selvittelytilit .= ", $trow[oletus_selvittelytilit]";
  }

  $query = "SELECT nimi nimi, lasku.tapvm tapvm, ifnull(tiliointi.tapvm, 'Ei sopivaa suoritusta') suorituspvm, tiliointi.selite tilioteselite, lasku.summa, lasku.tunnus, tiliointi.tunnus stunnus
            FROM lasku use index (yhtio_tila_mapvm)
            LEFT JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio and tiliointi.tilino in ($selvittelytilit) and tiliointi.summa = lasku.summa and korjattu = '')
            WHERE lasku.yhtio        = '$kukarow[yhtio]'
            AND lasku.tila           = 'Q'
            AND lasku.mapvm          = '0000-00-00'
            AND lasku.suoraveloitus != ''";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<font class='message'>".t("Kohdistamattomat suoraveloituslaskut")."</font>";

    echo "<tr>";

    for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
      echo "<th>" . t(mysql_field_name($result, $i)) . "</th>";
    }

    echo "<th></th></tr>";

    while ($trow = mysql_fetch_assoc($result)) {
      echo "<tr>";

      for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
        echo "<td>".$trow[mysql_field_name($result, $i)]."</td>";
      }

      if ($trow['suorituspvm'] != 'Ei sopivaa suoritusta') {
        echo "<td><form name = 'valinta' method='post'>
          <input type = 'hidden' name = 'tee' value = 'V'>
          <input type = 'hidden' name = 'tunnus' value = '$trow[tunnus]'>
          <input type = 'hidden' name = 'stunnus' value = '$trow[stunnus]'>
          <input type = 'submit' value = '".t("suorita")."'></form></td>";
      }
      else {
        echo "<td><a href='$palvelin2", "muutosite.php?tee=E&tunnus=$trow[tunnus]'>".t("Tutki")."</a></td>";
      }
      echo "</tr>";
    }
    echo "</table>";
  }
  else {
    echo t("Ei kohdistamattomia suoraveloituksia");
  }
}

require 'inc/footer.inc';
