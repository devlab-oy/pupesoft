<?php

if ($_REQUEST["tee"] == 'NAYTATILAUS') $nayta_pdf = 1; //Generoidaan .pdf-file

require '../inc/parametrit.inc';

if ($tee_pdf == 'tulosta_karhu') {
  require 'myyntires/paperikarhu.php';
  exit;
}

if ($tee_pdf == 'tulosta_tratta') {
  require 'myyntires/paperitratta.php';
  exit;
}

// ekotetaan javascripti‰ jotta saadaan pdf:‰t uuteen ikkunaan
js_openFormInNewWindow();

if ($toim == "TRATTA") {
  echo "<font class='head'>".t("Selaa trattoja")."</font><hr />";
  $tyyppi = "T";
}
else {
  echo "<font class='head'>".t("Selaa maksukehotuksia")."</font><hr />";
  $tyyppi = "";
}

if ($tee == 'uusi_ekirje') {
  $tee_pdf = "tulosta_karhu";

  require 'myyntires/paperikarhu.php';

  echo "<br><font class='ok'>eKirje l‰hetetty uudestaan asiakkaalle $asiakastiedot[nimi]!</font><br><br>";
}

if ($tee == "uusi_ekirjekierros") {
  $tee_pdf = "tulosta_karhu";

  $query = "SELECT GROUP_CONCAT(DISTINCT lasku.tunnus) karhuttavat,
            sum(lasku.summa-lasku.saldo_maksettu) karhuttava_summa
            FROM karhukierros
            JOIN karhu_lasku ON (karhukierros.tunnus = karhu_lasku.ktunnus)
            JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.mapvm = '0000-00-00')
            JOIN asiakas ON lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus
            WHERE karhukierros.yhtio = '$kukarow[yhtio]'
            and karhukierros.tyyppi  = '$tyyppi'
            and karhukierros.tunnus  = '$kierros'
            GROUP BY asiakas.ytunnus, asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.postitp
            HAVING karhuttava_summa > 0";
  $uek_res = pupe_query($query);

  $uek_lask = 0;

  echo "<br>";

  while ($uek_row = mysql_fetch_assoc($uek_res)) {

    unset($karhuviesti);

    $lasku_tunnus = $uek_row["karhuttavat"];

    try {
      // koitetaan l‰hett‰‰ eKirje sek‰ tulostaa
      require 'paperikarhu.php';

      echo "<font class='ok'>eKirje l‰hetetty uudestaan asiakkaalle $asiakastiedot[nimi]!</font><br>";
    }
    catch (Exception $e) {
      $ekarhu_success = false;
      echo "<font class='error'>Ei voitu l‰hett‰‰ karhua eKirjeen‰. Virhe: " . $e->getMessage() . "</font>";
    }

    $uek_lask++;
  }

  echo "<br><br><font class='ok'>L‰hetettiin: $uek_lask eKirjett‰!</font><br><br><br>";
}

// Jos ollaan painettu poista-nappia
if (isset($_POST['poista_tratta'])) {
  if ($poista_tratta_tunnus != '' and $ltunnus != '') {
    // k‰sitell‰‰n muuttujat
    $poista_tratta_tunnus = (int) $poista_tratta_tunnus;
    $ltunnus = (int) $ltunnus;

    $query = "SELECT *
              FROM karhu_lasku
              WHERE ktunnus = $poista_tratta_tunnus
              AND ltunnus   = $ltunnus";
    $res = pupe_query($query);

    while ($row = mysql_fetch_assoc($res)) {
      // "poistetaan" haluttu tratta n‰kyvist‰ kertomalla laskuntunnus -1:ll‰
      $ltun = $row['ltunnus'] * -1;

      $query = "UPDATE karhu_lasku SET
                ltunnus       = $ltun
                WHERE ktunnus = $poista_tratta_tunnus
                AND ltunnus   = $row[ltunnus]";
      $kres = pupe_query($query);

      echo "<font class='message'>", t("Tratta poistettu laskulta"), " $row[ltunnus] (", t("kierros"), " $poista_tratta_tunnus)</font><br/>";
    }
  }
}

echo "<form name='karhu_selaa' method='post'>
    <table>
    <tr>
      <th>".t("Ytunnus")."</th><td><input type='text' name='ytunnus'></td>
    </tr>
    <tr>
      <th>".t("Laskunro")."</th><td><input type='text' name='laskunro'></td>
      <td class='back'><input type='submit' name='tee_hae' value='".t("Hae")."'></td>
      <td class='back'><input type='submit' name='tee_kaikki' value='".t("N‰yt‰ kaikki avoimet")."'></td>
    </tr>
    </table>
    </form>";

if ($toim == "") {
  echo "<br><br><font class='head'>".t("Selaa maksukehotuskierroksia / uudelleenl‰het‰ kierros asiakkaille")."</font><hr />";
  echo "<form name='karhu_selaa' method='post'>
    <input type='submit' name='tee_kiekat' value='".t("N‰yt‰ maksukehotuskierrokset")."'>
    </form><br>";
}

if ((isset($tee_hae) and $tee_hae != "") or (isset($tee_kaikki) and $tee_kaikki != "") or (isset($tee_kierros) and $tee_kierros != "")) {

  if (!empty($_POST['laskunro'])) {
    $where  = sprintf("lasku.laskunro = %d", (int) $_POST['laskunro']);
    $malisa = " ";
    $limit  = "GROUP BY karhu_lasku.ktunnus ORDER BY tunnus desc LIMIT 1";
    echo "<br><br><font class='info'>".t("Haetut maksukehotukset").":</font>";
  }
  elseif (!empty($_POST['kierros'])) {
    $where  = sprintf("karhukierros.tunnus = %d", (int) $_POST['kierros']);
    $malisa = " ";
    $limit  = "GROUP BY karhu_lasku.ktunnus ORDER BY tunnus desc LIMIT 1";
    echo "<br><br><font class='info'>".t("Maksukehotuskierroksen maksukehotukset").":</font>";
  }
  elseif (!empty($_POST['ytunnus'])) {
    $where  = sprintf("lasku.ytunnus = '%s'", (int) $_POST['ytunnus']);
    $malisa = " ";
    $limit  = "ORDER BY tunnus desc LIMIT 1";
    echo "<br><br><font class='info'>".t("Haetut maksukehotukset").":</font>";
  }
  else {
    $where  = "lasku.mapvm = '0000-00-00'";
    $malisa = " and lasku.mapvm = '0000-00-00' ";
    $limit  = "";
    echo "<br><br><font class='info'>".t("Kaikki avoimet maksukehotukset").":</font>";
  }

  // haetaan uusin karhukierros/karhukerta
  $query = "SELECT ifnull(group_concat(distinct karhu_lasku.ktunnus), 0) as tunnus, ifnull(group_concat(distinct liitostunnus), 0) as liitostunnus
            FROM karhu_lasku
            JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.yhtio = '$kukarow[yhtio]')
            JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = lasku.yhtio and karhukierros.tyyppi = '$tyyppi')
            WHERE $where
            $limit";
  $res = pupe_query($query);

  if (mysql_num_rows($res) > 0) {

    $ktunnus = mysql_fetch_assoc($res);

    echo "<br>
      <table>
        <tr>
          <th>".t('Kierros')."</th>
          <th>".t('Ytunnus')."<br>".t('Asiakas')."</th>
          <th>".t('Laskunro')."</th>
          <th>".t('Summa')."</th>
          <th>".t('Maksettu')."</th>
          <th>".t('Laskun er‰p‰iv‰')."</th>";

    if ($toim == "TRATTA") {
      echo "<th>".t('Tratta pvm')."<br>".t('Er‰p‰iv‰')."</th>";
      echo "<th>".t('Trattakertoja')."</th>";
    }
    else {
      echo "<th>".t('Maksukehotuspvm')."<br>".t('Er‰p‰iv‰')."</th>";
      echo "<th>".t('Maksukehotuskertoja')."</th>";
    }

    echo "<td class='back'></td>";

    if ($toim == "TRATTA") {
      echo "<th></th>";
    }

    echo "</tr>";

    $query = "SELECT lasku.laskunro, lasku.summa, lasku.saldo_maksettu, lasku.liitostunnus, karhu_lasku.ktunnus,
              if(lasku.nimi != lasku.toim_nimi and lasku.toim_nimi != '', concat_ws('<br>', lasku.nimi, lasku.toim_nimi), lasku.nimi) nimi,
              karhukierros.pvm, lasku.erpcm, lasku.mapvm, lasku.ytunnus, karhu_lasku.ltunnus
              FROM karhu_lasku
              JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.liitostunnus in ($ktunnus[liitostunnus]))
              JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = '$kukarow[yhtio]' and karhukierros.tyyppi = '$tyyppi')
              WHERE karhu_lasku.ktunnus in ($ktunnus[tunnus])
              $malisa
              ORDER BY ytunnus, pvm, laskunro";
    $res = pupe_query($query);

    $laskuri = 0;

    while ($row = mysql_fetch_assoc($res)) {

      $laskuri++;

      $query = "SELECT count(distinct ktunnus) as kertoja
                FROM karhu_lasku
                JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus AND karhukierros.tyyppi = '$tyyppi')
                WHERE ltunnus = $row[ltunnus]";
      $ka_res = mysql_query($query);
      $karhuttu = mysql_fetch_assoc($ka_res);

      $query = "SELECT group_concat(karhu_lasku.ltunnus) laskutunnukset
                FROM karhu_lasku
                JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.liitostunnus = $row[liitostunnus])
                JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = '$kukarow[yhtio]' and karhukierros.tyyppi = '$tyyppi')
                WHERE karhu_lasku.ktunnus = '$row[ktunnus]'";
      $la_res = pupe_query($query);
      $tunnukset = mysql_fetch_assoc($la_res);

      if ($toim == "TRATTA") {
        $yhtiorow['karhuerapvm'] = 7; // t‰m‰ on hardcoodattu tratan tulostukseen
      }

      if ($yhtiorow['karhuerapvm'] > 0) {
        $paiva = substr($row["pvm"], 8, 2);
        $kuu   = substr($row["pvm"], 5, 2);
        $year  = substr($row["pvm"], 0, 4);

        $erapaiva = tv1dateconv(date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva+$yhtiorow['karhuerapvm'], $year)));
      }
      else {
        $erapaiva = t("HETI");
      }

      echo "<tr>
          <td valign='top'>".tv1dateconv($row["pvm"])."</td>
          <td valign='top'>$row[ytunnus]<br>$row[nimi]</td>
          <td valign='top'><a href = '".$palvelin2."tilauskasittely/tulostakopio.php?toim=LASKU&tee=ETSILASKU&laskunro=$row[laskunro]'>$row[laskunro]</a></td>
          <td valign='top' style='text-align: right;'>$row[summa]</td>";


      if ($row["mapvm"] != "0000-00-00") {
        echo "  <td valign='top'>".tv1dateconv($row["mapvm"])."</td>";
      }
      else {
        echo "  <td valign='top' style='text-align: right;'>$row[saldo_maksettu]</td>";
      }

      echo "  <td valign='top'>".tv1dateconv($row['erpcm'])."</td>
          <td valign='top'>".tv1dateconv($row['pvm'])."<br>$erapaiva</td>
          <td valign='top' style='text-align: right;'>$karhuttu[kertoja]</td>";

      if ($toim == "TRATTA") {
        echo "<td class='back'>
            <form id='tulostakopioform_$laskuri' name='tulostakopioform_$laskuri' method='post'>
            <input type='hidden' name='karhutunnus' value='$row[ktunnus]'>
            <input type='hidden' name='lasku_tunnus[]' value='$tunnukset[laskutunnukset]'>
            <input type='hidden' name='tee' value='NAYTATILAUS'>
            <input type='hidden' name='tee_pdf' value='tulosta_tratta'>
            <input type='submit' value='".t("N‰yt‰ pdf")."' onClick=\"js_openFormInNewWindow('tulostakopioform_$laskuri', ''); return false;\">
            </form></td>";
      }
      else {
        echo "<td class='back'>
            <form id='tulostakopioform_$laskuri' name='tulostakopioform_$laskuri' method='post'>
            <input type='hidden' name='karhutunnus' value='$row[ktunnus]'>
            <input type='hidden' name='lasku_tunnus[]' value='$tunnukset[laskutunnukset]'>
            <input type='hidden' name='tee' value='NAYTATILAUS'>
            <input type='hidden' name='tee_pdf' value='tulosta_karhu'>
            <input type='submit' value='".t("N‰yt‰ pdf")."' onClick=\"js_openFormInNewWindow('tulostakopioform_$laskuri', ''); return false;\">
            </form></td>";

        if (isset($ekirje_config) and is_array($ekirje_config)) {
          echo "<td class='back'>
              <form method='post'>
              <input type='hidden' name='toim'       value = '$toim'>
              <input type='hidden' name='tee_hae'     value = '$tee_hae'>
              <input type='hidden' name='tee_kaikki'     value = '$tee_kaikki'>
              <input type='hidden' name='tee_kierros'   value = '$tee_kierros'>
              <input type='hidden' name='karhutunnus'   value = '$row[ktunnus]'>
              <input type='hidden' name='lasku_tunnus[]'   value = '$tunnukset[laskutunnukset]'>
              <input type='hidden' name='ytunnus'     value = '$ytunnus'>
              <input type='hidden' name='laskunro'     value = '$laskunro'>
              <input type='hidden' name='tee'         value = 'uusi_ekirje'>
              <input type='hidden' name='ekirje_laheta'   value = 'JOO'>
              <input type='submit' value='".t("L‰het‰ eKirje uudestaan")."'>
              </form></td>";
        }
      }

      if ($toim == "TRATTA") {
        echo "<td><form method='post'>";
        echo "<input type='submit' name='poista_tratta' id='poista_tratta' value='", t("Poista"), "'>";
        echo "<input type='hidden' name='poista_tratta_tunnus' id='poista_tratta_tunnus' value='$row[ktunnus]'>";
        echo "<input type='hidden' name='ltunnus' id='ltunnus' value='$row[ltunnus]'>";
        echo "</form></td>";
      }

      echo "</tr>";
    }

    echo "</table>";

  }
  else {
    echo "<br><font class='message'>Yht‰‰n laskua ei lˆytynyt!</font>";
  }
}
elseif (isset($tee_kiekat)) {
  $query = "SELECT karhukierros.pvm, karhukierros.tunnus kierros,
            count(DISTINCT concat(asiakas.ytunnus, asiakas.nimi, asiakas.nimitark, asiakas.osoite, asiakas.postino, asiakas.postitp)) kpl,
            sum(if(lasku.mapvm='0000-00-00', 1, 0)) avoimet,
            GROUP_CONCAT(distinct lasku.tunnus) karhuttavat
            FROM karhukierros
            JOIN karhu_lasku ON (karhukierros.tunnus = karhu_lasku.ktunnus)
            JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus)
            JOIN asiakas ON lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus
            WHERE karhukierros.yhtio = '$kukarow[yhtio]'
            and karhukierros.tyyppi  = '$tyyppi'
            and datediff(now(), karhukierros.pvm) <= 180
            GROUP BY karhukierros.pvm
            ORDER BY karhukierros.pvm DESC";
  $res = pupe_query($query);

  echo "<br><br><font class='info'>".t("Maksukehotuskierrokset").":</font>";
  echo "<table><tr>
    <th>".t('Kierros')."</th>
    <th>".t('Maksukehotusten m‰‰r‰')."</th>
    <th>".t('Maksamattomia laskuja nyt')."</th></tr>";

  while ($row = mysql_fetch_assoc($res)) {
    echo "<tr>
        <td valign='top'>".tv1dateconv($row["pvm"])."</td>
        <td valign='top' align='right'>$row[kpl]</td>
        <td valign='top' align='right'>$row[avoimet]</td>
        <td valign='top' class='back'>
          <form method='post'>
          <input type='hidden' name='toim'     value = '$toim'>
          <input type='hidden' name='tee_hae'   value = '$tee_hae'>
          <input type='hidden' name='tee_kaikki'   value = '$tee_kaikki'>
          <input type='hidden' name='tee_kierros' value = 'JES'>
          <input type='hidden' name='kierros'   value = '$row[kierros]'>
          <input type='submit' value='".t("N‰yt‰ kierroksen maksukehotukset")."'>
          </form>
        </td>";

    if (isset($ekirje_config) and is_array($ekirje_config) and $row["avoimet"] > 0) {
      echo "<td valign='top' class='back'>
          <form method='post'>
          <input type='hidden' name='toim'       value = '$toim'>
          <input type='hidden' name='tee_hae'     value = '$tee_hae'>
          <input type='hidden' name='tee_kaikki'     value = '$tee_kaikki'>
          <input type='hidden' name='tee'         value = 'uusi_ekirjekierros'>
          <input type='hidden' name='tee_kiekat'    value = 'JOO'>
          <input type='hidden' name='kierros'       value = '$row[kierros]'>
          <input type='hidden' name='ekirje_laheta'   value = 'JOO'>
          <input type='submit' value='".t("Uudelleenl‰het‰ eKirjeet")."'>
          </form>
        </td>";
    }

    echo "</tr>";

  }

  echo "</table>";

  if (isset($ekirje_config) and is_array($ekirje_config)) {
    echo "<br><font class='error'>".t("HUOM: eKirje l‰hetet‰‰n uudestaan vain jos asiakkaalla on viel‰ maksamattomia laskuja")."!</font>";
  }
}

require "inc/footer.inc";
