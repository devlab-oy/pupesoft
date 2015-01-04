<?php

require 'inc/edifact_functions.inc';

if (isset($_POST['task']) and $_POST['task'] == 'nayta_varastoraportti') {

  $varastot = unserialize(base64_decode($_POST['varastot']));

  $sessio = $_POST['session'];
  $logo_url = $_POST['logo_url'];
  $logo_info = pdf_logo($logo_url, $sessio);

  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];
  $pdf_data['varastot'] = $varastot;

  $pdf_tiedosto = varastoraportti_pdf($pdf_data);

  header("Content-type: application/pdf");
  echo file_get_contents($pdf_tiedosto);
  die;
}

require "inc/parametrit.inc";

if (!isset($errors)) $errors = array();

if (isset($task) and ($task == 'ylijaamasiirto')) {

  $query = "UPDATE tilausrivi
            SET otunnus = {$uusi_tilaus}
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$rivitunnus}'";
  pupe_query($query);

  $query = "UPDATE sarjanumeroseuranta
            SET lisatieto = 'Siirretty'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND myyntirivitunnus = '{$rivitunnus}'";
  pupe_query($query);


  $query = "SELECT tilausrivin_lisatiedot.asiakkaan_rivinumero,
              lasku.asiakkaan_tilausnumero,
              ss.leveys
              FROM lasku
              LEFT JOIN tilausrivi
                ON tilausrivi.yhtio = lasku.yhtio
                AND tilausrivi.otunnus = lasku.tunnus
              LEFT JOIN sarjanumeroseuranta AS ss
                ON ss.yhtio = tilausrivi.yhtio
                AND ss.myyntirivitunnus = tilausrivi.tunnus
              LEFT JOIN tilausrivin_lisatiedot
                ON tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
              WHERE lasku.yhtio = '{$kukarow['yhtio']}'
              AND lasku.tunnus = '{$uusi_tilaus}'
              AND tilausrivi.tunnus != '{$rivitunnus}'
              GROUP BY concat(lasku.asiakkaan_tilausnumero,ss.leveys)
              ORDER BY asiakkaan_rivinumero ASC";
  $result = pupe_query($query);

  $rivinumero = 0;
  $uusi_rivi = false;
  while ($rivi = mysql_fetch_assoc($result)) {

    if ($leveys == $rivi['leveys']) {
      $uusi_rivi = $rivi['asiakkaan_rivinumero'];
      $tilausnumero = $rivi['asiakkaan_tilausnumero'];
      break;
    }

    $rivinumero = $rivi['asiakkaan_rivinumero'];
  }

  if ($rivinumero > 0) {

    if (!$uusi_rivi) {
      $uusi_rivi = $rivinumero + 1;
    }

    $query = "UPDATE tilausrivin_lisatiedot SET
              asiakkaan_rivinumero = '{$uusi_rivi}',
              asiakkaan_tilausnumero = '{$info['asiakkaan_tilausnumero']}'
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tilausrivitunnus = '{$rivitunnus}'";
    pupe_query($query);
  }

  $task = 'ylijaamakasittely';
}

if (isset($task) and ($task == 'ylijaamakasittely')) {

  echo "<a href='varastotilanne.php'>´ " . t("Palaa varastontilanteeseen") . "</a><br><br>";
  echo "<font class='head'>".t("Ylij‰‰m‰rullien k‰sittely")."</font></a><hr><br>";

  // haetaan aktiiviset tilaukset joihin voi siirt‰‰ ylij‰‰mi‰
  $query = "SELECT lasku.asiakkaan_tilausnumero,
            lasku.tunnus,
            trlt.asiakkaan_tilausnumero as rtn,
            laskun_lisatiedot.konttiviite
            FROM lasku
            JOIN laskun_lisatiedot
              ON laskun_lisatiedot.yhtio = lasku.yhtio
              AND laskun_lisatiedot.otunnus = lasku.tunnus
            LEFT JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
              AND tilausrivi.tyyppi = 'L'
            LEFT JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = lasku.yhtio
              AND trlt.tilausrivitunnus = tilausrivi.tunnus
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tilaustyyppi = 'N'
            AND laskun_lisatiedot.satamavahvistus_pvm = '0000-00-00 00:00:00'
            GROUP BY lasku.asiakkaan_tilausnumero
            ORDER BY trlt.asiakkaan_tilausnumero";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "Ei avoimia tilauksia...";
  }
  else {

    $avoimet_tilaukset = array();

    while ($tilaus = mysql_fetch_assoc($result)) {
      $avoimet_tilaukset[] = $tilaus;
    }

  }

  $query = "SELECT concat(ss.hyllyalue,'-',ss.hyllynro) AS paikka,
            concat(lasku.asiakkaan_tilausnumero,':', tilausrivin_lisatiedot.asiakkaan_rivinumero) AS tilaus,
            lasku.asiakkaan_tilausnumero,
            ss.sarjanumero,
            ss.leveys,
            tilausrivi.tunnus
            FROM sarjanumeroseuranta AS ss
            JOIN tilausrivi
              ON tilausrivi.yhtio = ss.yhtio
              AND tilausrivi.tunnus = ss.myyntirivitunnus
            JOIN tilausrivin_lisatiedot
              ON tilausrivin_lisatiedot.yhtio = ss.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            JOIN lasku
              ON lasku.yhtio = ss.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
            JOIN laskun_lisatiedot
              ON laskun_lisatiedot.yhtio = ss.yhtio
              AND laskun_lisatiedot.otunnus = lasku.tunnus
            WHERE ss.yhtio = '{$kukarow['yhtio']}'
            AND ss.lisatieto = 'Ylijaama'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "Ei rullia varastossa...";
  }
  else {

    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Varastopaikka")."</th>";
    echo "<th>".t("UIB")."</th>";
    echo "<th>".t("Vanha tilaus:rivi")."</th>";
    echo "<th>".t("Uusi tilaus")."</th>";
    echo "<th class='back'></th>";
    echo "</tr>";

    while ($rulla = mysql_fetch_assoc($result)) {

      echo "<tr>";

      echo "<td valign='top' align='center'>";
      echo $rulla['paikka'];
      echo "</td>";

      echo "<td valign='top' align='center'>";
      echo $rulla['sarjanumero'];
      echo "</td>";

      echo "<td valign='top' align='center'>";
      echo $rulla['tilaus'];
      echo "</td>";

      echo "<td valign='top' align='center'>";
      echo "<form method='post'><select name='uusi_tilaus'>";

      if (count($avoimet_tilaukset) > 0) {
        echo "<option>". t("Valitse uusi tilaus") ."</option>";

        foreach ($avoimet_tilaukset as $avoin_tilaus) {

          if ($rulla['asiakkaan_tilausnumero'] != $avoin_tilaus['asiakkaan_tilausnumero']) {
            echo "<option value='{$avoin_tilaus['tunnus']}'>{$avoin_tilaus['asiakkaan_tilausnumero']}</option>";
          }
        }

        echo "</select>";
      }
      else {
        echo t("Ei avoimia tilauksia");
      }

      echo "</td>";
      echo "<td class='back' valign='top' align='center'>";

      if (count($avoimet_tilaukset) > 0) {
        echo "<input type='hidden' name='task' value='ylijaamasiirto' />";
        echo "<input type='hidden' name='rivitunnus' value='{$rulla['tunnus']}' />";
        echo "<input type='hidden' name='leveys' value='{$rulla['leveys']}' />";
        echo "<input type='submit' value='Siirr‰' />";
      }
      echo "</form></td>";
      echo "</tr>";
    }
    echo "</table>";
  }
}

if (!isset($task)) {

  $query = "SELECT ss.*,
            tilausrivin_lisatiedot.asiakkaan_rivinumero AS myyntirivinumero,
            tilausrivin_lisatiedot.asiakkaan_tilausnumero AS myyntitilausnumero,
            ostotilausrivin_lisatiedot.asiakkaan_rivinumero AS ostorivinumero,
            ostotilausrivin_lisatiedot.asiakkaan_tilausnumero AS ostotilausnumero,
            ostotilausrivin_lisatiedot.kuljetuksen_rekno,
            IF(ss.lisatieto IS NULL, 'Normaali', ss.lisatieto) AS status
            FROM sarjanumeroseuranta AS ss
            LEFT JOIN tilausrivi
              ON tilausrivi.yhtio = ss.yhtio
              AND tilausrivi.tunnus = ss.myyntirivitunnus
            LEFT JOIN tilausrivin_lisatiedot
              ON tilausrivin_lisatiedot.yhtio = ss.yhtio
              AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
            LEFT JOIN tilausrivi AS ostotilausrivi
              ON ostotilausrivi.yhtio = ss.yhtio
              AND ostotilausrivi.tunnus = ss.ostorivitunnus
            LEFT JOIN tilausrivin_lisatiedot AS ostotilausrivin_lisatiedot
              ON ostotilausrivin_lisatiedot.yhtio = ss.yhtio
              AND ostotilausrivin_lisatiedot.tilausrivitunnus = ostotilausrivi.tunnus
            LEFT JOIN lasku
              ON lasku.yhtio = ss.yhtio
              AND lasku.tunnus = tilausrivi.otunnus
            LEFT JOIN laskun_lisatiedot
              ON laskun_lisatiedot.yhtio = ss.yhtio
              AND laskun_lisatiedot.otunnus = lasku.tunnus
            WHERE ss.yhtio = '{$kukarow['yhtio']}'
            AND (ss.lisatieto != 'Toimitettu' OR ss.lisatieto IS NULL)
            AND ss.varasto IS NOT NULL
            ORDER BY ss.hyllyalue, CAST(ss.hyllynro AS SIGNED)";
  $result = pupe_query($query);

  echo "<font class='head'>".t("Varastotilanne")."</font><hr><br>";

  if (mysql_num_rows($result) == 0) {
    echo "Ei rullia varastossa...";
  }
  else {

    $varastot = array();
    $painot = array();

    while ($rulla = mysql_fetch_assoc($result)) {

      $varastopaikka = $rulla['hyllyalue'] . "-" . $rulla['hyllynro'];

      $varastot[$varastopaikka][] = $rulla;
      $painot[$varastopaikka] = $painot[$varastopaikka] + $rulla['massa'];
      $statukset[$varastopaikka][] = $rulla['status'];

    }

    foreach ($statukset as $vp => $status) {
      $statukset[$vp] = array_count_values($status);
    }

    echo "<table>";
    echo "<tr>";
    echo "<th>".t("Varastopaikka")."</th>";
    echo "<th>".t("Rullien m‰‰r‰")."</th>";
    echo "<th>".t("Tilausnumerot ja -rivit")."</th>";
    echo "<th>".t("Statukset")."</th>";
    echo "<th>".t("Yhteispaino")."</th>";
    echo "</tr>";

    foreach ($varastot as $vp => $rullat) {

      echo "<tr>";

      echo "<td valign='top' align='center'>";
      echo $vp;
      echo "</td>";

      echo "<td valign='top' align='center'>";
      echo count($rullat);
      echo " kpl</td>";

      echo "<td valign='top' align='center'>";

      $tilausnumerot = array();
      foreach ($rullat as $rulla) {

        if ($rulla['myyntirivinumero'] == NULL) {
          $kombo = $rulla['ostotilausnumero'] . ":" . $rulla['ostorivinumero'];
        }
        else {
          $kombo = $rulla['myyntitilausnumero'] . ":" . $rulla['myyntirivinumero'];
        }

        if (!in_array($kombo, $tilausnumerot)) {
          $tilausnumerot[] = $kombo;
          echo $kombo, '<br>';
        }
      }

      echo "</td>";

      echo "<td valign='top' align='center'>";
      foreach ($statukset[$vp] as $status => $kpl) {
        echo $status, ' ', $kpl, ' kpl<br>';
      }
      echo "</td>";

      echo "<td valign='top' align='center'>";
      echo $painot[$vp];
      echo " kg</td>";

      echo "</tr>";
    }
    echo "</table>";

    echo '<br>';

    js_openFormInNewWindow();

    $varastot = serialize($varastot);
    $varastot = base64_encode($varastot);

    $session = mysql_real_escape_string($_COOKIE["pupesoft_session"]);
    $logo_url = $palvelin2."view.php?id=".$yhtiorow["logo"];

    $varastot = 'xxx';

    echo "
    <form method='post' id='nayta_varastoraportti' action='varastotilanne.php'>
    <input type='hidden' name='varastot' value='{$varastot}' />
    <input type='hidden' name='task' value='nayta_varastoraportti' />
    <input type='hidden' name='session' value='{$session}' />
    <input type='hidden' name='logo_url' value='{$logo_url}' />
    <input type='hidden' name='tee' value='XXX' />
    </form>
    <button onClick=\"js_openFormInNewWindow('nayta_varastoraportti', 'Varastoraportti'); return false;\" />";

    echo t("Luo pdf");
    echo "</button></div>";

    echo "
      <br><br>
      <form>
      <input type='hidden' name='task' value='ylijaamakasittely' />
      <input type='submit' value='K‰sittele ylij‰‰m‰t' />
      </form>";
  }
}



require "inc/footer.inc";
