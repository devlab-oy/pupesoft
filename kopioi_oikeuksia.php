<?php
require ("inc/parametrit.inc");

echo "<font class='head'>".t("Kopioi käyttöoikeuksia").":</font><hr>";

  if ($copyready != '') {
    echo "<font class='message'>".t("Kopioitiin oikeudet")." $fromkuka ($fromyhtio) --> $tokuka ($toyhtio)</font><br><br>";

    $query = "SELECT *
              FROM oikeu
              WHERE kuka = '$fromkuka'
              and yhtio  = '$fromyhtio'";
    $kukar = pupe_query($query);

    $query = "DELETE FROM oikeu
              WHERE kuka = '$tokuka'
              AND yhtio  = '$toyhtio'";
    $delre = pupe_query($query);

    while ($row = mysql_fetch_array($kukar)) {

      $query = "INSERT into oikeu SET
                kuka       = '{$tokuka}',
                sovellus   = '{$row['sovellus']}',
                nimi       = '{$row['nimi']}',
                alanimi    = '{$row['alanimi']}',
                paivitys   = '{$row['paivitys']}',
                nimitys    = '{$row['nimitys']}',
                jarjestys  = '{$row['jarjestys']}',
                jarjestys2 = '{$row['jarjestys2']}',
                hidden     = '{$row['hidden']}',
                yhtio      = '{$toyhtio}',
                laatija    = '{$kukarow['kuka']}',
                luontiaika = now(),
                muutospvm  = now(),
                muuttaja   = '{$kukarow['kuka']}'";
      $upres = pupe_query($query);
    }

    // päiviteään kuka-tauluun mitkä käyttäjät on aktiivisia ja mitkä poistettuja
    paivita_aktiiviset_kayttajat($tokuka, $toyhtio);

    $fromkuka  = '';
    $tokuka    = '';
    $fromyhtio  = '';
    $toyhtio  = '';
  }

  echo "<br><form method='post'>";
  echo "<input type='hidden' name='tila' value='copy'>";

  echo "<font class='message'>".t("Keneltä kopioidaan").":</font>";

  // tehdään käyttäjälistaukset

  $query = "SELECT distinct kuka.nimi, kuka.kuka
            FROM kuka
            JOIN yhtio USING (yhtio)
            WHERE kuka.extranet = ''
            AND ((yhtio.konserni = '$yhtiorow[konserni]' and yhtio.konserni != '') OR kuka.yhtio = '$kukarow[yhtio]')
            ORDER BY nimi";
  $kukar = pupe_query($query);

  echo "<table><tr><th align='left'>".t("Käyttäjä").":</th><td>
  <select name='fromkuka' onchange='submit()'>
  <option value=''>".t("Valitse käyttäjä")."</option>";

  while ($kurow=mysql_fetch_array($kukar)) {
    if ($fromkuka==$kurow[1]) $select='selected';
    else $select='';

    echo "<option $select value='$kurow[1]'>$kurow[0] ($kurow[1])</option>";
  }

  echo "</select></td></tr>";

  if ($fromkuka!='') {
    // tehdään yhtiolistaukset

    $query = "SELECT DISTINCT kuka.yhtio, yhtio.nimi
              FROM kuka, yhtio
              WHERE kuka.kuka   = '$fromkuka'
              AND kuka.extranet = ''
              AND yhtio.yhtio   = kuka.yhtio ";
    $yhres = pupe_query($query);

    if (mysql_num_rows($yhres) > 1){
      echo "<tr><th align='left'>".t("Yhtio").":</th><td><select name='fromyhtio'>";

      while ($yhrow = mysql_fetch_array ($yhres)) {
        echo "from $fromyhtio ja $kurow[0]<br> ";
        if ($fromyhtio==$yhrow[0]) $select='selected';
        else $select='';

        echo "<option $select value='$yhrow[yhtio]'>$yhrow[nimi]</option>";
      }

      echo "</select></td></tr>";
    }
    else {
      if (mysql_num_rows($yhres) == 1) {
        $yhrow = mysql_fetch_array ($yhres);
        echo "<input type='hidden' name='fromyhtio' value='$yhrow[yhtio]'>";
      }
      else {
        echo "Pahaa tapahtui!";
        exit;
      }
    }
  }

  echo "</table>";

  echo "<br><br><font class='message'>".t("Kenelle kopioidaan").":</font>";

  // tehdään käyttäjälistaukset

  $query = "SELECT distinct kuka.nimi, kuka.kuka
            FROM kuka
            JOIN yhtio USING (yhtio)
            WHERE kuka.extranet = ''
            AND ((yhtio.konserni = '$yhtiorow[konserni]' and yhtio.konserni != '') OR kuka.yhtio = '$kukarow[yhtio]')
            ORDER BY nimi";
  $kukar = pupe_query($query);

  echo "<table><tr><th align='left'>".t("Käyttäjä").":</th><td>
  <select name='tokuka' onchange='submit()'>
  <option value=''>".t("Valitse käyttäjä")."</option>";

  while ($kurow=mysql_fetch_array($kukar)) {
    if ($tokuka==$kurow[1]) $select='selected';
    else $select='';

    echo "<option $select value='$kurow[1]'>$kurow[0] ($kurow[1])</option>";
  }

  echo "</select></td></tr>";

  if ($tokuka!='') {
    // tehdään yhtiolistaukset

    $query = "SELECT distinct kuka.yhtio, yhtio.nimi
              FROM kuka, yhtio
              WHERE kuka.kuka   = '$tokuka'
              AND kuka.extranet = ''
              AND yhtio.yhtio   = kuka.yhtio ";
    $yhres = pupe_query($query);

    if (mysql_num_rows($yhres) > 1) {
      echo "<tr><th align='left'>".t("Yhtio").":</th><td><select name='toyhtio'>";

      while ($yhrow = mysql_fetch_array ($yhres)) {
        if ($toyhtio==$yhrow[0]) $select='selected';
        else $select='';

        echo "<option $select value='$yhrow[yhtio]'>$yhrow[nimi]</option>";
      }

      echo "</select></td></tr>";
    }
    else {
      if (mysql_num_rows($yhres) == 1) {
        $yhrow = mysql_fetch_array ($yhres);
        echo "<input type='hidden' name='toyhtio' value='$yhrow[yhtio]'>";
      }
      else {
        echo "Pahaa tapahtui!";
        exit;
      }
    }
  }

  echo "</table>";

  if (($tokuka!='') and ($fromkuka!='')) {
    echo "<br><br>";
    echo "<input type='submit' name='copyready' value='".t("Kopioi käyttöoikeudet")." $fromkuka --> $tokuka'>";
  }

  echo "</form>";

require("inc/footer.inc");
