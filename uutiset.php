<?php

if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
  $otsikko_apu = $_POST["otsikko"];
  $uutinen_apu = $_POST["uutinen"];

  require "inc/parametrit.inc";

  $otsikko = $otsikko_apu;
  $uutinen = $uutinen_apu;
}

if (!isset($toim))   $toim = "";
if (!isset($tee))   $tee = "";
if (!isset($limit))   $limit = "";

if ($toim == "") {
  if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) echo "<font class='head'>".t("Intra Uutiset")."</font><hr>";
  $tyyppi = "uutinen";
}
elseif ($toim == "EXTRANET") {
  echo "<font class='head'>".t("Extranet Uutiset")."</font><hr>";
  $tyyppi = "extranet_uutinen";
}
elseif ($toim == "AUTOMANUAL") {
  echo "<font class='head'>".t("Automanual Uutiset")."</font><hr>";
  $tyyppi = "extranet_uutinen";
}
elseif ($toim == "VIIKKOPALAVERI") {
  echo "<font class='head'>".t("Viikkopalaveri")."</font><hr>";
  $tyyppi = "viikkopalaveri";
}
elseif ($toim == "ASIAKASPALVELU") {
  echo "<font class='head'>".t("Asiakaspalvelu")."</font><hr>";
  $tyyppi = "asiakaspalvelu";
}
elseif ($toim == "RYJO") {
  echo "<font class='head'>".t("Ryjo")."</font><hr>";
  $tyyppi = "ryjo";
}
elseif ($toim == "VERKKOKAUPPA") {
  echo "<font class='head'>".t("Verkkokaupan Uutiset")."</font><hr>";
  $tyyppi = $toim;
}

if ($toim == "VIIKKOPALAVERI" or $toim == "ASIAKASPALVELU" or $toim == "RYJO") {
  $kulisa = "";
}
else {
  $kulisa = " and kuka='$kukarow[kuka]' ";
}

if ($tee == 'LISAA') {

  if ($kukarow['yhtio'] == 'artr' and $toim == 'EXTRANET' and $automanual_uutinen == '' and $extranet_uutinen == '') {
    echo "<font class='error'>".t("Uutisen näkyvyys on valittava! (Extranet tai Automanual)")."</font><br><br>";
    $rivi["kentta01"]  = $otsikko;
    $rivi["kentta02"]  = $uutinen;
    $rivi["kentta08"]  = $kentta08;
    $rivi["kentta09"]  = $kentta09;
    $rivi["konserni"]  = $konserni;
    $rivi["kokopaiva"] = $kokopaiva;
    $tee = "SYOTA";
  }
  elseif ($kukarow['yhtio'] == 'artr' and $toim == 'AUTOMANUAL' and $automanual_uutinen == '') {
    echo "<font class='error'>".t("Uutisen näkyvyys on valittava!")."</font><br><br>";
    $rivi["kentta01"]  = $otsikko;
    $rivi["kentta02"]  = $uutinen;
    $rivi["kentta09"]  = $kentta09;
    $rivi["konserni"]  = $konserni;
    $rivi["kokopaiva"] = $kokopaiva;
    $tee = "SYOTA";
  }
  elseif (strlen($otsikko) > 0 and strlen($uutinen) > 0 and count($lang) > 0) {

    $liitostunnus = 0;

    $retval = tarkasta_liite("userfile");

    if ($retval !== true) {
      echo $retval;
    }
    else {
      $uusi_filu = muuta_kuvan_koko(0, 130, "thumb", "tmp", "userfile");
      $kuva = tallenna_liite("userfile", "kalenteri", 0, $selite);

      if ($uusi_filu != "") {
        unlink($uusi_filu);
      }
    }

    $uutinen = nl2br(strip_tags($uutinen, '<a>'));
    $otsikko = nl2br(strip_tags($otsikko, '<a>'));
    $uutinen = mysql_real_escape_string($uutinen);

    // ollaanko valittu konsernitasoinen uutinen
    if ($konserni != '') $konserni = $yhtiorow['konserni'];

    $tapa = "";

    if ($automanual_uutinen != '' and $extranet_uutinen != '' and $toim == 'EXTRANET') {
      $tapa = "automanual_ext_uutinen";
    }
    elseif ($automanual_uutinen != '' and $extranet_uutinen == '' and ($toim == 'EXTRANET' or $toim == 'AUTOMANUAL')) {
      $tapa = "automanual_uutinen";
    }
    elseif ($automanual_uutinen == '' and $extranet_uutinen != '' and $toim == 'EXTRANET') {
      $tapa = "extranet_uutinen";
    }
    else {
      $tapa = $tyyppi;
    }

    for ($i=0; $i < count($lang); $i++) {

      if ($tunnus != 0) {
        $query = " UPDATE kalenteri SET ";
        $postquery = " WHERE tunnus = '$tunnus' ";
      }
      else {
        $query = "INSERT INTO kalenteri
                  SET
                  kuka       = '$kukarow[kuka]',
                  tyyppi     = '$tyyppi',
                  yhtio      = '$kukarow[yhtio]',
                  pvmalku    = now(),
                  luontiaika = now(),";
        $postquery = "";
      }

      $query .= "kentta01   = '$otsikko',
                 kentta02   = '$uutinen',";
      if ($kuva != '') {
        $query .= "kentta03   = '$kuva',";
      }

      if ($kentta08 == 'X') {
        $query .= "kentta08 = '$kentta08',";
      }

      $query .=  "kentta09   = '$kentta09',
                  konserni   = '$konserni',
                  kieli     = '$lang[$i]',
                  kokopaiva  = '$kokopaiva',
                  kuittaus  = '$lukittu',
                  tapa    = '$tapa'";
      $query .= $postquery;
      $result = pupe_query($query);
      $katunnus = mysql_insert_id($GLOBALS["masterlink"]);

      if ($liitostunnus != 0 && $kuva != '') {
        // päivitetään kuvalle vielä linkki toiseensuuntaa
        $query = "UPDATE liitetiedostot set liitostunnus='$katunnus' where tunnus='$liitostunnus'";
        $result = pupe_query($query);
      }

      if (!empty($mul_asiakasegmentti)) {

        // Poistetaan vanhat jos niitä on
        if ($tunnus > 0) {
          $uutistunnus = $tunnus;
        }
        else {
          $uutistunnus = $katunnus;
        }

        $query  = "DELETE from uutinen_asiakassegmentti
                   WHERE yhtio = '$kukarow[yhtio]'
                   AND uutistunnus = $uutistunnus";
        pupe_query($query);

        foreach($mul_asiakasegmentti as $muls) {
          $query  = "INSERT INTO uutinen_asiakassegmentti
                     SET yhtio = '$kukarow[yhtio]',
                     segmenttitunnus = $muls,
                     uutistunnus = $uutistunnus";
          pupe_query($query);
        }
      }
    }

    $tee = "";
  }
  else {
    echo "<font class='error'>".t("Sekä otsikko että uutinen on syötettävä!")."</font><br><br>";
    $rivi["kentta01"]  = $otsikko;
    $rivi["kentta02"]  = $uutinen;
    $rivi["kentta08"]  = $kentta08;
    $rivi["kentta09"]  = $kentta09;
    $rivi["konserni"]  = $konserni;
    $rivi["kokopaiva"] = $kokopaiva;
    $tee = "SYOTA";
  }
}

if ($tee == "SYOTA") {

  $rivi["pvmalku"] = date('Y-m-d');

  if ($tunnus > 0) {
    $query  = "SELECT *
               from kalenteri
               where tyyppi='$tyyppi' and tunnus='$tunnus' and yhtio='$kukarow[yhtio]' $kulisa";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $rivi = mysql_fetch_array($result);
    }
    else {
      echo "<br><br>".t("VIRHE: Et voi muokata uutista!")."<br>";
      exit;
    }
  }

  echo "<form enctype='multipart/form-data' name='sendfile' method='post'>
    <input type='hidden' name='tee' value='LISAA'>
    <input type='hidden' name='toim' value='$toim'>
    <input type='hidden' name='tunnus' value='$rivi[tunnus]'>
    <table width='400'>
    <tr>
      <th>".t("Otsikko")."</th>
      <td><input type='text' size='40' name='otsikko' value='$rivi[kentta01]'></td>
    </tr>
    <tr>
      <th>".t("Uutinen")."</th>
      <td><textarea wrap='none' name='uutinen' cols='100' rows='15'>$rivi[kentta02]</textarea></td>
    </tr>";

  if ($tunnus > 0) {

    if ($rivi["kentta03"] != '') {
      echo "
        <tr>
          <th>".t("Nykyinen kuva")."</th>
          <td><img src=view.php?id=$rivi[kentta03]' width='130'></td>
        </tr>";

      echo "<input type='hidden' name='kuva' value='$rivi[kentta03]'>";
    }

    echo "
      <tr>
        <th>".t("Syötä uusi kuva")."</th>
        <td><input type='file' name='userfile'></td>
      </tr>";
  }
  else {
    echo "
      <tr>
        <th>".t("Kuva")."</th>
        <td><input type='file' name='userfile'></td>
      </tr>";
  }

  echo "<tr>
      <th>".t("Toimittaja")."</th>
      <td>$kukarow[nimi]</td>
     </tr>
     <tr>
      <th>".t("Päivämäärä")."</th>
      <td>".tv1dateconv($rivi['pvmalku'], "PITKA")."</td>
     </tr>";

  echo "<tr><th>".t("Kieli").":&nbsp;</th><td>";

  if (!isset($lang)) $lang = array();

  foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
    $sel = "";

    if ($tunnus == 0) {
      if ($rivi["kieli"] == $sanakirja_kieli or ($rivi["kieli"] == "" and $sanakirja_kieli == $yhtiorow["kieli"]) and count($lang) == 0) $sel = "CHECKED";
      if (in_array($sanakirja_kieli, $lang)) $sel = "CHECKED";

      echo "<input type='radio' name='lang[]' value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."<br>";
    }
    elseif ($tunnus > 0) {
      if ($rivi["kieli"] == $sanakirja_kieli) $sel = "CHECKED";

      echo "<input type='radio' name='lang[]' value='$sanakirja_kieli' $sel>".t($sanakirja_kieli_nimi)."<br>";
    }
  }
  echo "</td>";

  if ($toim == "VERKKOKAUPPA") {
    echo "<tr><th>".t("Osasto")."</th><td>";

    echo "<select name='kentta09'>";

    $result = t_avainsana("VERKKOKAULINKKI");

    if (mysql_num_rows($result) > 0) {
      while ($orow = mysql_fetch_array($result)) {
        if ($rivi["kentta09"] == $orow["selite"]) $sel = "SELECTED";
        else $sel = "";
        echo "<option value='$orow[selite]' $sel>$orow[selitetark]</option>";
      }
    }

    $result = t_avainsana("OSASTO", "", " and avainsana.jarjestys < 10000 ");

    if (mysql_num_rows($result) > 0) {
      while ($orow = mysql_fetch_array($result)) {
        if ($rivi["kentta09"] == $orow["selite"]) $sel = "SELECTED";
        else $sel = "";
        echo "<option value='$orow[selite]' $sel>$orow[selite] - $orow[selitetark]</option>";
      }

    }

    echo "</select>";
    echo "</td></tr>";
  }

  if ($rivi['kokopaiva'] != "") $check = "CHECKED";
  else $check = "";

  echo "<tr><th>".t("Prioriteetti")."</th><td><input type='checkbox' name='kokopaiva' $check> ".t("Näytetäänkö uutinen aina päällimmäisenä")."</td></tr>";

  if ($yhtiorow['konserni'] != '') {

    if ($rivi['konserni'] != "") $check = "CHECKED";
    else $check = "";

    echo "<tr>
      <th>".t("Konserni")."</th>
      <td><input type='checkbox' name='konserni' $check> ".t("Näytetäänkö uutinen konsernin kaikilla yrityksillä")."</td>
    </tr>";
  }
  if (($toim == "VIIKKOPALAVERI" or $toim == "ASIAKASPALVELU" or $toim == "RYJO") and ($rivi["kuka"] == $kukarow["kuka"])) {
    if ($rivi['kuittaus'] != "") $check = "CHECKED";
    else $check = "";

    echo "<tr>
        <th>".t("Lukko")."</th>
        <td><input type='checkbox' name='lukittu' value='L' $check>".t("Lukitse palaveri. Lukittua palaveria ei voi muokata eikä poistaa.")."</td>
      </tr>";
  }

  if (($kukarow['yhtio'] == 'artr' and ($toim == 'EXTRANET' or $toim == 'AUTOMANUAL')) or ($toim == 'EXTRANET')) {
    if ($rivi['tapa'] == "automanual_ext_uutinen" and $rivi['tyyppi'] == "extranet_uutinen") {
      $check1 = $check2 = "CHECKED";
    }
    elseif ($rivi['tapa'] == "automanual_uutinen" and $rivi['tyyppi'] == "extranet_uutinen") {
      $check1 = "CHECKED";
      $check2 = "";
    }
    elseif ($rivi['tapa'] == "extranet_uutinen" and $rivi['tyyppi'] == "extranet_uutinen") {
      $check1 = "";
      $check2 = "CHECKED";
    }
    else {
      if ($toim == 'AUTOMANUAL') {
        $check1 = "CHECKED";
      }
      else {
        $check1 = "";
      }
      if ($toim == "EXTRANET") {
        $check2 = "CHECKED";
      }
      else {
        $check2 = "";
      }
    }

    if ($kukarow['yhtio'] == 'artr' and ($toim == 'EXTRANET' or $toim == 'AUTOMANUAL')) {
      echo "<tr>
        <th>".t("Automanual")."</th>
        <td><input type='checkbox' name='automanual_uutinen' $check1> ".t("Näytetäänkö uutinen Automanualissa")."</td>
      </tr>";
    }

    if ($toim == 'EXTRANET') {
      echo "<tr>
        <th>".t("Extranet")."</th>
        <td><input type='checkbox' name='extranet_uutinen' $check2> ".t("Näytetäänkö uutinen Extranetissä")."</td>
      </tr>";

      $check3 = "";
      if ($rivi['kentta08'] == 'X') {
        $check3 = "CHECKED";
      }

      echo "<tr>
        <th>".t("Extranet")."</th>
        <td><input type='checkbox' name='kentta08' value='X' $check3> ".t("Ei näytetä asiakkaan asiakkaille")."</td>
      </tr>";

      $segtunnarit = array();

      if ($tunnus > 0) {
        $preq = "SELECT segmenttitunnus
                 FROM uutinen_asiakassegmentti
                 WHERE yhtio = '$kukarow[yhtio]'
                 AND uutistunnus = $tunnus";
        $preres = pupe_query($preq);

        while ($prerow = mysql_fetch_array($preres)) {
          $segtunnarit[$prerow["segmenttitunnus"]] = TRUE;
        }
      }
      elseif (!empty($mul_asiakasegmentti)) {
        foreach($mul_asiakasegmentti as $muls) {
          $segtunnarit[$muls] = TRUE;
        }
      }

      $preq = "SELECT CONCAT(REPEAT('&raquo;', COUNT(parent.tunnus) - 1), ' ', ifnull(node.koodi, ''), ' ', node.nimi) AS name, node.koodi koodi, node.tunnus
               FROM dynaaminen_puu AS node
               JOIN dynaaminen_puu AS parent ON (parent.yhtio = node.yhtio AND parent.laji = node.laji AND parent.lft <= node.lft AND parent.rgt >= node.lft)
               WHERE node.yhtio = '$kukarow[yhtio]'
               AND node.lft     > 0
               AND node.laji    = 'asiakas'
               GROUP BY node.tunnus
               ORDER BY node.lft";
      $preres = pupe_query($preq);

      echo "<tr><th>".t("Extranet-uutisen segementtirajaus")."</th>";
      echo "<td><select name='mul_asiakasegmentti[]' multiple='TRUE' size='6'";
      echo "<option value=''>".t("Ei asiakassegmenttiä")."</option>";

      while ($prerow = mysql_fetch_array($preres)) {
        $sel = '';
        if (!empty($segtunnarit[$prerow["tunnus"]])) {
          $sel = "selected";
        }
        echo "<option value='$prerow[tunnus]' $sel>$prerow[name]</option>";
      }

      echo "</select></td></tr>\n";
    }
  }

  echo "
    </table>

    <br><input type='submit' value='".t("Syötä")."'>

    </form>";
}

if ($tee == "POISTA") {
  $query  = "UPDATE kalenteri
             SET tyyppi = concat('DELETED ',tyyppi)
             WHERE tyyppi='$tyyppi' and tunnus='$tunnus' and kuka='$kukarow[kuka]' and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  $tee = "";
}

if ($tee == '') {

  if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
    echo "<form method='post'>
      <input type='hidden' name='toim' value='$toim'>";
    echo "<input type='hidden' name='tee' value='SYOTA'>";
    echo "<input type='submit' value='".t("Lisää uusi uutinen")."'>";
    echo "</form><br><br>";
  }

  if ($limit=="all") $limit = "";
  elseif ($limit=="50") $limit = "limit 50";
  elseif ($limit=="10") $limit = "limit 10";
  else $limit = "limit 5";

  if ($yhtiorow['konserni'] != "") {
    $ehto = "(kalenteri.yhtio='$kukarow[yhtio]' or kalenteri.konserni='$yhtiorow[konserni]')";
  }
  else {
    $ehto = "kalenteri.yhtio='$kukarow[yhtio]'";
  }

  if ($kukarow['kieli'] != "" and $kukarow['kieli'] == $yhtiorow['kieli']) {
    $lisa = " and (kalenteri.kieli = '$kukarow[kieli]' or kalenteri.kieli = '') ";
  }
  elseif ($kukarow['kieli'] != "") {
    $lisa = " and kalenteri.kieli = '$kukarow[kieli]' ";
  }
  else {
    $lisa = "";
  }

  $querylisa_tapa = "";

  if ($toim == 'AUTOMANUAL') {
    $querylisa_tapa = " and tapa in ('automanual_uutinen', 'automanual_ext_uutinen') ";
  }
  else {
    $querylisa_tapa = "  and tapa != 'automanual_uutinen' ";
  }

  $query = "SELECT *, kalenteri.tunnus tun, kalenteri.kuka toimittaja
            from kalenteri
            left join kuka on kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
            where tyyppi='$tyyppi' $lisa and $ehto
            $querylisa_tapa
            order by kokopaiva desc, pvmalku desc, kalenteri.tunnus desc
            $limit";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<table style='width: 100%; min-width: 600px;'>";

    while ($uutinen = mysql_fetch_array($result)) {

      /*
      toimittaja = kuka
      paivays    = pvmalku
      otsikko    = kentta01
      uutinen    = kentta02
      kuvaurl    = kentta03
      */

      $kuva = "";

      if ($uutinen["kentta03"] != "") {
        $kuva = "<img src='view.php?id=$uutinen[kentta03]' width='130'>";
      }

      if ((int) $yhtiorow["logo"] > 0 and $kuva == '') {
        $liite = hae_liite($yhtiorow["logo"], "Yllapito", "array");

        $kuva = "<img src='view.php?id=$liite[tunnus]' width='130'>";
      }
      elseif (@fopen($yhtiorow["logo"], "r") and $kuva == '') {
        $kuva = "<img src='$yhtiorow[logo]' width='130'>";
      }
      elseif (file_exists($yhtiorow["logo"]) and $kuva == '') {
        $kuva = "<img src='$yhtiorow[logo]' width='130'>";
      }

      if ($kuva == '') {
        if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
          $kuva = "<img src='{$palvelin2}pics/facelift/pupe.gif' width='130'>";
        }
        else {
          $kuva = "<img src='{$pupesoft_scheme}api.devlab.fi/pupesoft.gif' width='130'>";
        }
      }

      if ($uutinen['nimi'] == "") {
        $uutinen['nimi'] = $uutinen['toimittaja'];
      }

      if ($toim == "EXTRANET") {
        // ##tuoteno##
        $search = "/#{2}(.*?)#{2}/s";
        preg_match_all($search, $uutinen["kentta02"], $matches, PREG_SET_ORDER);

        if (count($matches) > 0) {
          $search = array();
          $replace = array();

          foreach ($matches as $m) {

            //  Haetaan tuotenumero
            $query = "SELECT tuoteno, nimitys
                      FROM tuote
                      WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$m[1]'";
            $tres = pupe_query($query);

            //  Tämä me korvataan aina!
            $search[] = "/$m[0]/";

            if (mysql_num_rows($tres) > 1) {
              $replace[]  = "";
            }
            else {
              $trow = mysql_fetch_array($tres);

              $replace[]  = "<a href = '$PHP_SELF?toim=$toim'>$trow[tuoteno]</a> $trow[nimitys]";
            }
          }

          $uutinen["kentta02"] = preg_replace($search, $replace, $uutinen["kentta02"]);
        }
      }

      echo "  <tr><td colspan='2' class='back'><font class='head'>$uutinen[kentta01]</font><hr></td></tr>
          <tr>
          <td valign='top' align='center' width='140'><br>$kuva<br><br></td>
          <td valign='top'>$uutinen[kentta02]</font></td>
          </tr>";

      echo "<tr><th colspan='2'>";
      echo t("Toimittaja").": $uutinen[nimi]<br>".t("Päivämäärä").": ".tv1dateconv($uutinen['pvmalku'], "PITKA");

      if ($toim == "VERKKOKAUPPA") {
        echo "<br>Osasto: $uutinen[kentta09]";
      }

      if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
        if (($toim == "VIIKKOPALAVERI" or $toim == "ASIAKASPALVELU" or $toim == "RYJO") and ($uutinen["kuittaus"] == "")) {
          echo "<br><br><form method='post'>
            <input type='hidden' name='toim' value='$toim'>";
          echo "<input type='hidden' name='tee' value='SYOTA'>";
          echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
          echo "<input type='submit' value='".t("Muokkaa")."'>";
          echo "</form> ";

          if ($uutinen["kuka"] == $kukarow["kuka"] and $uutinen["yhtio"] == $kukarow["yhtio"]) {
            echo " <form method='post'><input type='hidden' name='toim' value='$toim'>";
            echo "<input type='hidden' name='tee' value='POISTA'>";
            echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
            echo "<input type='submit' value='".t("Poista")."'>";
            echo "</form>";
          }
        }
        elseif ($toim != "VIIKKOPALAVERI" and $toim != "ASIAKASPALVELU" and $toim != "RYJO" and $uutinen["kuka"] == $kukarow["kuka"] and $uutinen["yhtio"] == $kukarow["yhtio"]) {
          echo "<br><br><form method='post'>
            <input type='hidden' name='toim' value='$toim'>";
          echo "<input type='hidden' name='tee' value='SYOTA'>";
          echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
          echo "<input type='submit' value='".t("Muokkaa")."'>";
          echo "</form> ";
          echo " <form method='post'>
            <input type='hidden' name='toim' value='$toim'>";
          echo "<input type='hidden' name='tee' value='POISTA'>";
          echo "<input type='hidden' name='tunnus' value='$uutinen[tun]'>";
          echo "<input type='submit' value='".t("Poista")."'>";
          echo "</form>";
        }
      }
      echo "</th></tr>";
      echo"<tr><td colspan='2' class='back'><br></td></tr>";

    }
    echo "</table>";

    echo "<a href='$PHP_SELF?limit=10&toim=$toim'>".t("Näytä viimeiset 10 uutista")."</a><br>";
    echo "<a href='$PHP_SELF?limit=50&toim=$toim'>".t("Näytä viimeiset 50 uutista")."</a><br>";
    echo "<a href='$PHP_SELF?limit=all&toim=$toim'>".t("Näytä kaikki uutiset")."</a><br>";
  }
}

if (strpos($_SERVER['SCRIPT_NAME'], "uutiset.php")  !== FALSE) {
  require "inc/footer.inc";
}
