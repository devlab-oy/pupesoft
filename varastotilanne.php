<?php

if (isset($_POST['task']) and ($_POST['task'] == 'saldoraportti_pdf')) {
  $no_head = "yes";
}

require "inc/parametrit.inc";
require 'inc/edifact_functions.inc';

if (isset($task) and $task == 'saldoraportti_pdf') {

  $pdf_data = unserialize(base64_decode($varastotiedot));

  $logo_info = pdf_logo();
  $pdf_data['logodata'] = $logo_info['logodata'];
  $pdf_data['scale'] = $logo_info['scale'];

  $pdf_tiedosto = varastoraportti_pdf($pdf_data);

  header("Content-type: application/pdf");
  echo file_get_contents($pdf_tiedosto);
  die;
}

if (!isset($errors)) $errors = array();


if (!isset($task) or $task == 'luo_saldoraportti') {

  echo "<font class='head'>".t("Varastotilanne")."</font></a><hr><br>";

  $nyt = date("d.m.Y");

  echo "
    <script>
      $(function($){
         $.datepicker.regional['fi'] = {
                     closeText: 'Sulje',
                     prevText: '&laquo;Edellinen',
                     nextText: 'Seuraava&raquo;',
                     currentText: 'T&auml;n&auml;&auml;n',
             monthNames: ['Tammikuu','Helmikuu','Maaliskuu','Huhtikuu','Toukokuu','Kes&auml;kuu',
              'Hein&auml;kuu','Elokuu','Syyskuu','Lokakuu','Marraskuu','Joulukuu'],
              monthNamesShort: ['Tammi','Helmi','Maalis','Huhti','Touko','Kes&auml;',
              'Hein&auml;','Elo','Syys','Loka','Marras','Joulu'],
                      dayNamesShort: ['Su','Ma','Ti','Ke','To','Pe','Su'],
                      dayNames: ['Sunnuntai','Maanantai','Tiistai','Keskiviikko','Torstai','Perjantai','Lauantai'],
                      dayNamesMin: ['Su','Ma','Ti','Ke','To','Pe','La'],
                      weekHeader: 'Vk',
              dateFormat: 'dd.mm.yy',
                      firstDay: 1,
                      isRTL: false,
                      showMonthAfterYear: false,
                      yearSuffix: ''};
          $.datepicker.setDefaults($.datepicker.regional['fi']);
      });

      $(function() {
        $('#pvm').datepicker();
      });
      </script>
  ";

  echo "
    <form method='post'>
    <table>
    <tr><th>" . t("P‰iv‰") ."</th><td><input type='text' id='pvm' name='pvm' value='{$nyt}' /></td></tr>
    <tr><th>" . t("Kellonaika") ."</th><td>";

  $t = time();
  $minuutti = date("i",$t);
  $tunti = date("H",$t);

  echo "<select name='tunti'>";
  $h = 0;
  while ($h <= 23) {
    $_h = str_pad($h,2,"0",STR_PAD_LEFT);

    if ($_h == $tunti) {
      $sel = 'selected';
    }
    else {
     $sel = '';
    }

    echo "<option value='{$_h}' {$sel}>{$_h}</option>";
    $h++;
  }
  echo "</select>";

  echo " : ";

  echo "<select name='minuutti'>";
  $m = 0;
  while ($m <= 59) {
    $_m = str_pad($m,2,"0",STR_PAD_LEFT);

    if ($_m == $minuutti) {
      $sel = 'selected';
    }
    else {
     $sel = '';
    }

    echo "<option value='{$_m}' {$sel}>{$_m}</option>";
    $m++;
  }
  echo "</select>";

  echo "
  </td></tr>
  <tr><th></th><td align='right'><input type='submit' value='". t("Hae tiedot") ."' /></td></tr>
  </table>
  <input type='hidden' name='task' value='luo_saldoraportti' />
  </form>";

  echo "<br>
    <form>
    <input type='hidden' name='task' value='ylijaamakasittely' />
    <input type='submit' value='K‰sittele ylij‰‰m‰t' />
    </form>";

  if ($task == 'luo_saldoraportti') {

    $ajat = explode(".", $pvm);

    $paiva = $ajat[0];
    $kuu = $ajat[1];
    $vuosi = $ajat[2];

    $hetki = $vuosi.'-'.$kuu.'-'.$paiva.' '.$tunti.':'.$minuutti.':00';

    $varastotiedot = varastotilanne($hetki);
    extract($varastotiedot);

    $varastotiedot = serialize($varastotiedot);
    $varastotiedot = base64_encode($varastotiedot);

    js_openFormInNewWindow();

    echo "
      <form id='saldoraportti_pdf' method='post'>
      <input type='hidden' name='task' value='saldoraportti_pdf' />
      <input type='hidden' name='tee' value='XXX' />
      <input type='hidden' name='varastotiedot' value='{$varastotiedot}' />
      </form>";

    echo "<button onClick=\"js_openFormInNewWindow('saldoraportti_pdf','Saldoraportti'); return false;\" />";
    echo t("Lataa pdf");
    echo "</button><br />";


    $echo = "<br>Rullien kokonaism‰‰r‰: " . $totalmaara . ' kpl';
    $echo .= '<br>';
    $echo .= "Rullien kokonaispaino: " . $totalpaino . ' kg';
    $echo .= '<br><br>';

    $echo .= "<table>";

    foreach ($paikat as $paikka => $tilaukset) {

      $rivimaara = count($tilaukset) + 2;

      $echo .= "<tr><th valign='top' rowspan={$rivimaara}''>";
      $echo .= "<h2 style='font-weight:bold'>".$paikka.'</h2>';
      $echo .= "</th><th>";
      $echo .= "</th></tr>";

      $tilauspaino = 0;
      $tilausrullia = 0;
      foreach ($tilaukset as $tilaus => $rullat) {
        $echo .= "<tr><td>";


        $echo .= "<div style='display:inline-block; width:130px; padding:0 10px'>" . $tilaus . "</div>";

        $paino = 0;
        $rullia = 0;
        foreach ($rullat as $rulla) {
          $paino += $rulla['massa'];
          $rullia++;
          $tilauspaino += $rulla['massa'];
          $tilausrullia++;
        }

        $echo .= "<div style='display:inline-block; width:80px; text-align:right; padding-right:10px;'>" . $paino . " kg</div>";
        $echo .= "<div style='display:inline-block; text-align:left;'>" . $rullia . " kpl</div>";



        $echo .= "</td></tr>";
      }
      $echo .= "<tr><td>";

      $echo .= "<div style='display:inline-block; width:130px; padding:0 10px; font-weight:bold;'>" . t("Yhteens‰:") . "</div>";
      $echo .= "<div style='display:inline-block; width:80px; text-align:right; padding-right:10px;  font-weight:bold;'>" . $tilauspaino . " kg</div>";
      $echo .= "<div style='display:inline-block; text-align:left;  font-weight:bold;'>" . $tilausrullia . " kpl</div>";

      $echo .= "</td></tr>";

    }
    $echo .= "</table>";


    echo $echo;
  }
}

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

require "inc/footer.inc";
