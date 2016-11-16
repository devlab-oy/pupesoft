<?php

if (strpos($_SERVER['SCRIPT_NAME'], "kuittaamattomat.php") !== FALSE) {
  require "../inc/parametrit.inc";
}

echo "<font class='head'>".t("Muistutukset")."</font><hr>";

if ($tee == 'B') {
  if ($muuta != "") {
    $query = "SELECT yhteyshenkilo.tunnus yhenkilo, kalenteri.*
              FROM kalenteri
              LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.tyyppi = 'A'
              WHERE kalenteri.tunnus = '$kaletunnus'
              and kalenteri.yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);
    $prow = mysql_fetch_array($result);

    $viesti = $kukarow["nimi"]." ".t("kuittasi").": ".$muuta;

    $kysely = "INSERT INTO kalenteri
               SET asiakas    = '$prow[asiakas]',
               liitostunnus = '$prow[liitostunnus]',
               henkilo      = '$prow[yhenkilo]',
               kuka         = '$kukarow[kuka]',
               yhtio        = '$kukarow[yhtio]',
               tyyppi       = 'Kuittaus',
               tapa         = '$prow[tapa]',
               kentta01     = '$viesti',
               kuittaus     = '',
               pvmalku      = now(),
               pvmloppu     = now(),
               perheid      = '$kaletunnus'";
    $result = pupe_query($kysely);

    $query = "UPDATE kalenteri
              SET kuittaus   = ''
              WHERE tunnus = '$kaletunnus'
              and yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tee = "";
  }
  else {
    $tee = "A";
  }
}

if ($tee == 'A') {
  $query = "SELECT yhteyshenkilo.nimi yhteyshenkilo, kalenteri.*
            FROM kalenteri
            LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio and yhteyshenkilo.tyyppi = 'A'
            WHERE kalenteri.tunnus = '$kaletunnus'
            and kalenteri.yhtio    = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  echo "<table>";

  echo "<tr>";
  echo "<th>".t("Päivämäärä")."</th>";
  echo "<th>".t("Viesti")."</th>";
  echo "<th>".t("Tapa")."</th>";
  echo "<th>".t("Asiakas")."</th>";
  echo "<th>".t("Yhteyshenkilö")."</th>";
  echo "</tr>";

  $prow = mysql_fetch_array($result);

  if ($prow["liitostunnus"] > 0) {
    $query = "SELECT nimi
              FROM asiakas
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$prow[liitostunnus]'";
    $asresult = pupe_query($query);
    $asrow = mysql_fetch_array($asresult);

    $aslisa = "<a href='".$palvelin2."crm/asiakasmemo.php?ytunnus=$prow[asiakas]&asiakasid=$prow[liitostunnus]'>$asrow[nimi]</a>";
  }
  else {
    $aslisa = "";
  }

  echo "<tr>";
  echo "<td>".tv1dateconv($prow["pvmalku"])."</td>";
  echo "<td>$prow[kentta01]</td>";
  echo "<td>$prow[tapa]</td>";
  echo "<td>$prow[asiakas] $aslisa</td>";
  echo "<td>$prow[yhteyshenkilo]</td>";
  echo "</tr>";

  echo "</table><br><br>";
  echo "<table>
      <form action='".$palvelin2."crm/kuittaamattomat.php?tee=B&kuka=$kuka&kaletunnus=$kaletunnus' method='POST'>
      <tr><th align='left'>".t("Kuittaus").":</td></th></tr>
      <tr><td>
      <textarea cols='83' rows='4' name='muuta' wrap='hard'>$muuta</textarea>
      </td></tr>
      <tr><td class='back'><input type='submit' value='".t("Kuittaa")."'></td></tr>
      </table></form>";
}

if ($tee == "LISAAMUISTUTUS") {
  if ($viesti != "") {

    if ($kuittaus == '') {
      $kuittaus = 'K';
    }

    if (checkdate($mkka, $mppa, $mvva)) {
      $pvmalku  = "'$mvva-$mkka-$mppa $mhh:$mmm:00'";
    }
    else {
      $pvmalku  = "now()";
    }

    $kysely = "INSERT INTO kalenteri
               SET kuka = '$kuka',
               yhtio    = '$kukarow[yhtio]',
               tyyppi   = 'Muistutus',
               tapa     = '$tapa',
               kentta01 = '$viesti',
               kuittaus = '$kuittaus',
               pvmalku  = $pvmalku,
               pvmloppu = date_add($pvmalku, INTERVAL 30 MINUTE)";
    $result = pupe_query($kysely);
    $muist = mysql_insert_id($GLOBALS["masterlink"]);

    echo t("Lisätty muistutus päivälle:")."  <b>$pvmalku</b><br><br>";

    $query = "SELECT *
              FROM kuka
              WHERE yhtio = '$kukarow[yhtio]'
              and kuka    = '$kuka'";
    $result = pupe_query($query);
    $row = mysql_fetch_array($result);

    // Käytäjälle lähetetään tekstiviestimuistutus
    if ($row["puhno"] != '' and strlen($viesti) > 0 and $sms_palvelin != "" and $sms_user != "" and $sms_pass != "") {
      $ok = 1;

      $teksti = substr("Muistutus $yhtiorow[nimi]. $tapa. ".$viesti, 0, 160);
      $teksti = urlencode($teksti);

      $retval = file_get_contents("$sms_palvelin?user=$sms_user&pass=$sms_pass&numero=$row[puhno]&viesti=$teksti&not_before_date=$mvva-$mkka-$mppa&not_before=$mhh:$mmm:00&yhtio=$kukarow[yhtio]&kalenteritunnus=$muist");

      if (trim($retval) == "0") $ok = 0;

      if ($ok == 1) {
        echo "<font class='error'>VIRHE: Tekstiviestin lähetys epäonnistui! $retval</font><br><br>";
      }

      if ($ok == 0) {
        echo "<font class='message'>Tekstiviestimuistutus lehetetään!</font><br><br>";
      }
    }

    $kuka    = '';
    $tapa    = '';
    $viesti    = '';
    $kuittaus  = '';
    $tee     = '';
  }
  else {
    $tee     = 'MUISTUTUS';
  }
}

if ($tee == "MUISTUTUS") {
  echo "<table>";
  echo "  <form action='".$palvelin2."crm/kuittaamattomat.php' method='POST'>
      <input type='hidden' name='tee' value='LISAAMUISTUTUS'>
      <input type='hidden' name='from' value='$from'>";

  echo "<table width='620'>";

  echo "<tr><th colspan='3'>".t("Lisää muistutus")."</th>";
  echo "<tr><td colspan='3'><textarea cols='83' rows='3' name='viesti' wrap='hard'>$viesti</textarea></td></tr>";

  echo "  <tr>
    <th>".t("Yhteydenottaja: ")."</th>
    <td colspan='2'><select name='kuka'>
    <option value='$kukarow[kuka]'>".t("Itse")."</option>";

  $query = "SELECT distinct kuka.tunnus, kuka.nimi, kuka.kuka
            FROM kuka, oikeu
            WHERE kuka.yhtio = '$kukarow[yhtio]'
            and oikeu.yhtio  = kuka.yhtio
            and oikeu.kuka   = kuka.kuka
            and oikeu.nimi   = 'crm/kalenteri.php'
            and kuka.kuka    <> '$kukarow[kuka]'
            ORDER BY kuka.nimi";
  $result = pupe_query($query);

  while ($row = mysql_fetch_array($result)) {
    if ($row["kuka"] == $kuka) {
      $sel = "SELECTED";
    }
    else {
      $sel = "";
    }

    echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
  }
  echo "</select></td></tr>";

  if (!isset($mkka))
    $mkka = date("m");
  if (!isset($mvva))
    $mvva = date("Y");
  if (!isset($mppa))
    $mppa = date("d");
  if (!isset($mhh))
    $mhh = "08";
  if (!isset($mmm))
    $mmm = "00";

  echo "<tr><th>".t("Muistutuspäivämäärä (pp-kk-vvvv tt:mm)")."</th>
      <td colspan='2'><input type='text' name='mppa' value='$mppa' size='3'>-
      <input type='text' name='mkka' value='$mkka' size='3'>-
      <input type='text' name='mvva' value='$mvva' size='5'>
      &nbsp;&nbsp;
      <input type='text' name='mhh' value='$mhh' size='3'>:
      <input type='text' name='mmm' value='$mmm' size='3'></td></tr>";

  if ($kuittaus == "E") {
    $sel = "CHECKED";
  }
  else {
    $sel = "";
  }

  echo"  <tr>
      <th>".t("Ei kuittausta:")." </th><td colspan='2'><input type='checkbox' name='kuittaus' value='E' $sel>
      </td>
      </tr>";

  echo "<tr><th>".t("Tapa:")."</th>";

  $vresult = t_avainsana("KALETAPA");

  echo "<td colspan='2'><select name='tapa'>";

  while ($vrow=mysql_fetch_array($vresult)) {
    $sel="";

    if ($tapa == $vrow["selitetark"]) {
      $sel = "selected";
    }
    echo "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark]</option>";
  }

  echo "</select></td></tr>";

  echo "  <tr>
      <td colspan='3' align='right' class='back'>
      <input type='submit' value='".t("Tallenna")."'>
      </form>
      </td></tr>";
  echo "</table>";
}

if ($tee == "") {

  if (strpos($_SERVER['SCRIPT_NAME'], "kuittaamattomat.php") !== FALSE) {
    echo "<a href='".$palvelin2."crm/kuittaamattomat.php?tee=MUISTUTUS&ytunnus=$ytunnus&yhtunnus=$yhtunnus'>".t("Lisää muistutus")."</a><br><br>";
  }

  echo "<table>";

  if (strpos($_SERVER['SCRIPT_NAME'], "kuittaamattomat.php") !== FALSE) {
    echo "<tr>";
    echo "<td>Näytä henkilön </td>";

    echo "  <form action='".$palvelin2."crm/kuittaamattomat.php' method='POST'>

        <td><select name='kuka' onchange='submit()'>
        <option value='$kukarow[kuka]'>$kukarow[nimi]</option>";

    $query = "SELECT distinct kuka.tunnus, kuka.nimi, kuka.kuka
              FROM kuka, oikeu
              WHERE kuka.yhtio = '$kukarow[yhtio]'
              and oikeu.yhtio  = kuka.yhtio
              and oikeu.kuka   = kuka.kuka
              and oikeu.nimi   = 'crm/kalenteri.php'
              and kuka.tunnus  <> '$kukarow[tunnus]'
              ORDER BY kuka.nimi";
    $result = pupe_query($query);

    while ($row = mysql_fetch_array($result)) {
      $sel = '';

      if ($row["kuka"] == $kuka) {
        $sel = 'SELECTED';
      }

      echo "<option value='$row[kuka]' $sel>$row[nimi]</option>";
    }

    echo "</select></td><td> ".t("kuitattavat muistutukset").".</td></form>";

    echo "</tr></table><br>";
  }

  if ($kuka == '') {
    $kuka = $kukarow["kuka"];
  }

  //* listataan muistutukset *///
  $query = "SELECT yhteyshenkilo.nimi yhteyshenkilo, kuka1.nimi nimi1, kuka2.nimi nimi2,
            lasku.tunnus laskutunnus, lasku.tila laskutila, lasku.alatila laskualatila, kuka3.nimi laskumyyja, lasku.muutospvm laskumpvm,
            kalenteri.*,
            date_format(pvmalku, '%Y%m%d%H%i%s') voimassa
            FROM kalenteri
            LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio and yhteyshenkilo.tyyppi = 'A'
            LEFT JOIN kuka as kuka1 ON (kuka1.yhtio=kalenteri.yhtio and kuka1.kuka=kalenteri.kuka)
            LEFT JOIN kuka as kuka2 ON (kuka2.yhtio=kalenteri.yhtio and kuka2.kuka=kalenteri.myyntipaallikko)
            LEFT JOIN lasku ON kalenteri.yhtio=lasku.yhtio and kalenteri.otunnus=lasku.tunnus
            LEFT JOIN kuka as kuka3 ON (kuka3.yhtio = lasku.yhtio and kuka3.tunnus = lasku.myyja)
            where (kalenteri.kuka = '$kuka' or kalenteri.myyntipaallikko = '$kuka')
            and kalenteri.tyyppi in ('Muistutus','Lead')
            and kuittaus         = 'K'
            and kalenteri.yhtio  = '$kukarow[yhtio]'
            and left(kalenteri.tyyppi,7) != 'DELETED'
            ORDER BY kalenteri.pvmalku desc";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    echo "<table>";

    echo "<tr>";
    echo "<th valign='top'>".t("Asiaa hoitaa")."</th>";
    echo "<th valign='top'>".t("Päivämäärä")."<br>".t("Muistutus")."</th>";
    echo "<th valign='top'>".t("Viesti")."</th>";
    echo "<th valign='top'>".t("Tyyppi")."<br>".t("Tapa")."</th>";
    echo "<th valign='top'>".t("Asiakas")."</th>";
    echo "<th valign='top'>".t("Yhteyshenkilö")."</th>";
    echo "</tr>";

    while ($prow = mysql_fetch_array($result)) {

      unset($asrow);

      if ($prow["liitostunnus"] > 0) {
        $query = "SELECT nimi
                  FROM asiakas
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$prow[liitostunnus]'";
        $asresult = pupe_query($query);
        $asrow = mysql_fetch_array($asresult);

        $aslisa = "<a href='".$palvelin2."crm/asiakasmemo.php?ytunnus=$prow[asiakas]&asiakasid=$prow[liitostunnus]'>$asrow[nimi]</a>";
      }
      else {
        $aslisa = "";
      }

      echo "<tr>";
      echo "<td valign='top'>$prow[nimi1]</td>";
      echo "<td valign='top'>".tv1dateconv($prow["luontiaika"], "P")."<br>";


      if (date("YmdHis") > $prow["voimassa"]) {
        echo "<font class='red'>".tv1dateconv($prow["pvmalku"], "P")."</font>";
      }
      else {
        echo "<font class='green'>".tv1dateconv($prow["pvmalku"], "P")."</font>";
      }
      echo "</td>";

      echo "<td valign='top'>$prow[kentta01]";

      if ($prow["laskutunnus"] > 0) {
        $laskutyyppi = $prow["laskutila"];
        $alatila   = $prow["laskualatila"];

        //tehdään selväkielinen tila/alatila
        require "inc/laskutyyppi.inc";

        echo "<br><br>".t("$laskutyyppi")." ".t("$alatila").":  <a href='../raportit/asiakkaantilaukset.php?toim=MYYNTI&tee=NAYTATILAUS&tunnus=$prow[laskutunnus]'>$prow[laskutunnus]</a> / ".tv1dateconv($prow["laskumpvm"])." ($prow[laskumyyja])";
      }

      if ($prow["laskutunnus"] == 0 and $prow["tyyppi"] == "Lead") {
        echo "<br><br><a href='".$palvelin2."tilauskasittely/tilaus_myynti.php?toim=TARJOUS&asiakasid=$prow[liitostunnus]'>".t("Tee tarjous")."</a>";
      }

      echo "</td>";
      echo "<td valign='top'>$prow[tyyppi]<br>$prow[tapa]</td>";
      echo "<td valign='top'>$prow[asiakas] $aslisa</td>";
      echo "<td valign='top'>$prow[yhteyshenkilo]</td>";

      if ($prow["kuka"] == $kukarow["kuka"]) {
        echo "<td class='back' valign='top'><form action='".$palvelin2."crm/kuittaamattomat.php?&tee=A&kaletunnus=$prow[tunnus]' method='post'>
            <input type='submit' value='".t("Kuittaa")."'></form></td>";
      }

      echo "</tr>";
    }

    echo "</table>";
  }
  else {
    echo "<font class='message'>".t("Yhtään kuitattavaa muistutusta ei löydy")."!</font>";
  }
}

if (strpos($_SERVER['SCRIPT_NAME'], "kuittaamattomat.php") !== FALSE) {
  require "../inc/footer.inc";
}
