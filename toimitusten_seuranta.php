<?php

require "inc/parametrit.inc";
require 'sarjanumero/generoi_edifact.inc';

if (isset($task) and $task == 'sinetoi') {
  if (!empty($sinettinumero) and !empty($konttinumero)) {

    $kontit = kontitustiedot($konttiviite, $temp_konttinumero);

    $kontin_kilot = $kontit[$temp_konttinumero]['paino'];

    $lista = $kontit[$temp_konttinumero]['lista'];

    $query = "UPDATE tilausrivi SET
              toimitettu = '{$kukarow['kuka']}',
              toimitettuaika = NOW()
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus IN ({$lista})";
    pupe_query($query);

    $query = "UPDATE tilausrivin_lisatiedot SET
              konttinumero      = '{$konttinumero}',
              sinettinumero     = '{$sinettinumero}',
              kontin_kilot      = '{$kontin_kilot}',
              kontin_taarapaino = '{$taara}',
              kontin_isokoodi   = '{$isokoodi}'
              WHERE yhtio       = '{$kukarow['yhtio']}'
              AND tilausrivitunnus IN ({$lista})";
    pupe_query($query);

    $parametrit = kontitus_parametrit($lista);

    if ($parametrit) {
      $parametrit['kontitus_info']['konttinumero'] = $konttinumero;
      $parametrit['kontitus_info']['sinettinumero'] = $sinettinumero;
      $sanoma = laadi_edifact_sanoma($parametrit);
    }

    if (laheta_sanoma($sanoma)) {
      $lahetys = 'OK';
    }
    else {
      $lahetys = 'EI';
    }
  }
  unset($task);
}


if (isset($task) and $task == 'laheta_satamavahvistus') {

  $parametrit = satamavahvistus_parametrit($konttiviite);

  if ($parametrit) {
    $sanoma = laadi_edifact_sanoma($parametrit);
  }
  else{
    echo 'virhe<br>';die;
  }

  if (laheta_sanoma($sanoma)) {
    echo "Sanoma lähetetty";
    echo "<hr>";
    echo $sanoma;
    echo "<hr>";
  }
  else{
    echo "Lähetys epäonnistui!";
  }

  unset($task);
}

if (!isset($task)) {

  $konttiviite_kasitelty = array();

  echo "<font class='head'>".t("Toimitusten seuranta")."</font><hr><br>";

  $query = "SELECT lasku.asiakkaan_tilausnumero,
            laskun_lisatiedot.matkakoodi,
            laskun_lisatiedot.konttiviite,
            laskun_lisatiedot.konttimaara,
            laskun_lisatiedot.konttityyppi,
            laskun_lisatiedot.rullamaara,
            lasku.toimaika,
            lasku.tila,
            lasku.alatila,
            lasku.tunnus,
            COUNT(tilausrivi.tunnus) AS rullat,
            SUM(IF(tilausrivi.var = 'P', 1, 0)) AS tulouttamatta,
            SUM(IF(tilausrivi.keratty = '', 1, 0)) AS kontittamatta,
            SUM(IF(tilausrivi.toimitettu = '', 1, 0)) AS toimittamatta,
            SUM(IF(trlt.kontin_mrn = '', 1, 0)) AS mrn_vastaanottamatta
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
            AND laskun_lisatiedot.konttiviite != ''
            GROUP BY lasku.asiakkaan_tilausnumero
            ORDER BY konttiviite";
  $result = pupe_query($query);

  $tilaukset = array();

  $viitteet = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    $viitteet[] = $rivi['konttiviite'];
    $tilaukset[$rivi['asiakkaan_tilausnumero']] = $rivi;
  }

  echo "<table>";
  echo "<tr>";
  echo "<th>".t("Tilauskoodi")."</th>";
  echo "<th>".t("Matkakoodi")."</th>";
  echo "<th>".t("Lähtöpäivä")."</th>";
  echo "<th>".t("Konttiviite")."</th>";
  echo "<th>".t("Rullien määrä")."</th>";
  echo "<th>".t("Tapahtumat")."</th>";
  echo "<th>".t("Kontit")."</th>";
  echo "<th class='back'></th>";
  echo "</tr>";


  foreach ($tilaukset as $key => $tilaus) {

    $viitelasku = array_count_values($viitteet);
    $tilauksia_viitteella = $viitelasku[$tilaus['konttiviite']];

    if (in_array($tilaus['konttiviite'], $kasitellyt_konttivitteet)) {
      $konttiviite_kasitelty = true;
    }
    else{
     $konttiviite_kasitelty = false;
    }

    $kontit_sinetointivalmiit = false;

    $query = "SELECT tunnus
              FROM liitetiedostot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND liitos = 'lasku'
              AND liitostunnus = '{$tilaus['tunnus']}'";
    $result = pupe_query($query);

    $bookkaukset = mysql_num_rows($result);

    $tapahtumat = "&bull; " . $bookkaukset ." kpl bookkaussanomia haettu<br>";

    echo "<tr>";

    echo "<td valign='top'>";
    echo $tilaus['asiakkaan_tilausnumero'];
    echo "</td>";

    echo "<td valign='top'>";
    echo $tilaus['matkakoodi'];
    echo "</td>";

    echo "<td valign='top'>";
    echo $tilaus['toimaika'];
    echo "</td>";


    echo "<td valign='top'>";
    echo $tilaus['konttiviite'];
    echo "</td>";

    if ($tilaus['rullat'] == 0) {
      $rullamaara = $tilaus['rullamaara'] . " (" . t("Ennakkoarvio") . ")";
    }
    else {

      $rullamaara = $tilaus['rullat'];

      $query = "SELECT tilausrivi.toimitettu, trlt.rahtikirja_id
                FROM tilausrivi
                JOIN tilausrivin_lisatiedot AS trlt
                  ON trlt.yhtio = tilausrivi.yhtio
                  AND trlt.tilausrivitunnus = tilausrivi.tunnus
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.tyyppi = 'O'
                AND trlt.asiakkaan_tilausnumero = '{$tilaus['asiakkaan_tilausnumero']}'";
      $result = pupe_query($query);

      $kuitattu = $kuittaamatta = 0;
      $rahtikirjat = array();

      // katsotaan onko rahtikirja(t) kuitattu ja kuinka monta rahtikirjaa
      while ($rulla = mysql_fetch_assoc($result)) {
        if ($rulla['toimitettu'] == '' ) {
          $kuittaamatta++;
        }
        else {
          $kuitattu++;
        }
        $rahtikirjat[] = $rulla['rahtikirja_id'];
      }

      $rahtikirjat = array_count_values($rahtikirjat);
      $rahtikirjat = count($rahtikirjat);
      $tapahtumat .= "&bull; " . $rahtikirjat ." kpl rahtikirjasanomia haettu<br>";

      if ($kuittaamatta == 0) {
        $tapahtumat .= "&bull; " .  t("Rahti kuitattu saapuneeksi") . "<br>";
      }
      elseif ($kuitattu > 0) {
        $tapahtumat .= "&bull; " .  t("Osa rahdista kuitattu saapuneeksi") . "<br>";
      }

      if ($tilaus['tulouttamatta'] == 0) {

        $tapahtumat .= "&bull; " .  t("Rullat viety varastoon") . "<br>";

        if ($tilaus['kontittamatta'] == 0) {
          $tapahtumat .= "&bull; " .  t("Rullat kontitettu") . "<br>";

          $query = "SELECT group_concat(otunnus)
                    FROM laskun_lisatiedot
                    WHERE yhtio = '{$yhtiorow['yhtio']}'
                    AND konttiviite = '{$tilaus['konttiviite']}'";
          $result = pupe_query($query);
          $konttiviitteen_alaiset_tilaukset = mysql_result($result, 0);

          $query = "SELECT count(tunnus) AS riveja
                    FROM tilausrivi
                    WHERE yhtio = '{$yhtiorow['yhtio']}'
                    AND otunnus IN ({$konttiviitteen_alaiset_tilaukset})
                    AND keratty = ''";
          $result = pupe_query($query);
          $konttiviitteesta_kontittamatta = mysql_result($result, 0);

          if ($konttiviitteesta_kontittamatta == 0) {
            $kontit_sinetointivalmiit = true;
          }

        }
        elseif ($tilaus['kontittamatta'] < $tilaus['rullamaara']) {
          $tapahtumat .= "&bull; " .  t("Osa rullista kontitettu") . "<br>";
        }

        if ($tilaus['toimittamatta'] == 0) {
          $tapahtumat .= "&bull; " .  t("Kontit sinetöity") . "<br>";
        }
        elseif ($tilaus['toimittamatta'] < $tilaus['rullamaara']) {
          $tapahtumat .= "&bull; " .  t("Osa konteista sinetöity") . "<br>";
        }

        $mrn_tullut = false;

        if ($tilaus['mrn_vastaanottamatta'] == 0) {
          $tapahtumat .= "&bull; " .  t("MRN-numerot vastaanotettu") . "<br>";
          $mrn_tullut = true;
        }
        elseif ($tilaus['mrn_vastaanottamatta']  < $tilaus['rullamaara']) {
         $tapahtumat .= "&bull; " .  t("Osa MRN-numeroista vastaanotettu") . "<br>";
        }

      }
      elseif ($tilaus['tulouttamatta'] < $tilaus['rullamaara']) {
        $tapahtumat .= "&bull; " .  t("Osa rullista viety varastoon") . "<br>";
      }
    }

    echo "<td valign='top' align='center'>";
    echo $rullamaara;
    echo "</td>";

    echo "<td valign='top'>";
    echo $tapahtumat;
    echo "</td>";

    if ($konttiviite_kasitelty) {
      //echo "<td valign='top' align='center'>";
      //echo t("Sama konttiviite kuin yllä.");
      //echo "</td>";
    }
    elseif (!$kontit_sinetointivalmiit) {
      echo "<td valign='top' rowspan='{$tilauksia_viitteella}' align='center'>";
      echo t("Ei vielä tietoa.");
      echo "</td>";
      $kasitellyt_konttivitteet[] = $tilaus['konttiviite'];
    }
    else {
      echo "<td valign='top' rowspan='{$tilauksia_viitteella}' align='right'>";

      $kontit = kontitustiedot($tilaus['konttiviite']);

      $kesken = 0;
      foreach ($kontit as $konttinumero => $kontti) {

        $temp_array = explode("/", $konttinumero);
        $_konttinumero = $temp_array[0];

        echo "<div style='margin:0 5px 8px 5px; padding:5px; border-bottom:1px solid grey;'>";
        echo "{$_konttinumero}. ({$kontti['kpl']} kpl, {$kontti['paino']} kg)&nbsp;&nbsp;";

        if ($kontti['sinettinumero'] == '') {
          echo "<form method='post'>";
          echo "<input type='hidden' name='task' value='anna_konttitiedot' />";
          echo "<input type='hidden' name='temp_konttinumero' value='{$konttinumero}' />";
          echo "<input type='hidden' name='paino' value='{$kontti['paino']}' />";
          echo "<input type='hidden' name='rullia' value='{$kontti['kpl']}' />";
          echo "<input type='hidden' name='sinetoitava_konttiviite' value='{$tilaus['konttiviite']}' />";
          echo "<input type='submit' value='". t("Sinetöi") ."' />";
          echo "</form>";
          $kesken++;
        }
        else {
          echo "<button type='button' disabled>" . t("Sinetöity") . "</button>";
        }

        if ($kontti['mrn'] != '') {
          echo "<div style='text-align:center; margin:4px 0'>MRN: ";
          echo "<input type='text'  value='{$kontti['mrn']}' readonly>";
          echo "</div>";

        }

        echo "</div>";
      }

      if ($kesken == 0 and $mrn_tullut) {

        echo "
          <div style='text-align:center;margin:10px 0;'>
          <form method='post'>
          <input type='hidden' name='konttiviite' value='{$tilaus['konttiviite']}' />
          <input type='hidden' name='matkakoodi' value='{$tilaus['matkakoodi']}' />
          <input type='hidden' name='lahtopvm_arvio' value='{$tilaus['toimaika']}' />
          <input type='hidden' name='task' value='tee_satamavahvistus' />
          <input type='submit' value='". t("Tee satamavahvistus") ."' />
          </form>
          </div>";
      }


      echo "</td>";
      $kasitellyt_konttivitteet[] = $tilaus['konttiviite'];
    }

    echo "</tr>";
  }

  echo "</table>";
}


if (isset($task) and $task == 'anna_konttitiedot') {

  echo "<a href='toimitusten_seuranta.php'>« " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
  echo "<font class='head'>".t("Kontin sinetöinti")."</font></a><hr><br>";

  echo "
  <form method='post'>
  <input type='hidden' name='task' value='sinetoi' />
  <input type='hidden' name='konttiviite' value='{$sinetoitava_konttiviite}' />
  <input type='hidden' name='temp_konttinumero' value='{$temp_konttinumero}' />
  <table>
  <tr><th>" . t("Konttinumero") ."</th><td><input type='text' name='konttinumero' /></td></tr>
  <tr><th>" . t("Sinettinumero") ."</th><td><input type='text' name='sinettinumero' /></td></tr>
  <tr><th>" . t("Taarapaino") ." (kg)</th><td><input type='text' name='taara' /></td></tr>
  <tr><th>" . t("ISO-koodi") ."</th><td><input type='text' name='isokoodi' /></td></tr>
  <tr><th>" . t("Rullien määrä") ."</th><td>{$rullia} kpl</td></tr>
  <tr><th>" . t("Paino") ."</th><td>{$paino}</td></tr>
  <tr><th></th><td align='right'><input type='submit' value='". t("Sinetöi") ."' /></td></tr>
  </table>
  </form>";

}

if (isset($task) and $task == 'tee_satamavahvistus') {

  $lahtopvm_arvio = date("d.m.Y", strtotime($lahtopvm_arvio));

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
        $('#lahtopvm').datepicker();
      });
      </script>
  ";

  echo "<a href='toimitusten_seuranta.php'>« " . t("Palaa toimitusten seurantaan") . "</a><br><br>";
  echo "<font class='head'>".t("Satamavahvistus")."</font><hr><br>";
  echo "
    <form method='post'>
    <input type='hidden' name='konttiviite' value='{$konttiviite}' />
    <table>
    <tr><th>" . t("Matkakoodi") ."</th><td>{$matkakoodi}</td></tr>
    <tr><th>" . t("Konttiviite") ."</th><td>{$konttiviite}</td></tr>
    <tr><th>" . t("Lähtöpäivä") ."</th><td><input type='text' id='lahtopvm' name='lahtopvm' value='{$lahtopvm_arvio}' /></td></tr>
    <tr><th>" . t("Lähtöaika") ."</th><td>";

  echo "<select name='lahtotunti'>";
  echo "<option>Tunti</option>";
  $h = 0;
  while ($h <= 23) {
    $_h = str_pad($h,2,"0",STR_PAD_LEFT);
    echo "<option value='{$_h}'>{$_h}</option>";
    $h++;
  }
  echo "</select>";

  echo " : ";

  echo "<select name='lahtominuutti'>";
  echo "<option>Minuutti</option>";
  $m = 0;
  while ($m <= 59) {
    $_m = str_pad($m,2,"0",STR_PAD_LEFT);
    echo "<option value='{$_m}'>{$_m}</option>";
    $m++;
  }
  echo "</select>";

  echo "
  </td></tr>
  <tr><th></th><td align='right'><input type='submit' value='". t("Lähetä satamavahvistus") ."' /></td></tr>
  </table>
  <input type='hidden' name='task' value='laheta_satamavahvistus' />
  </form>";

}

require "inc/footer.inc";

function kontitustiedot($konttiviite, $konttinumero = false) {
  global $kukarow;

  if ($konttinumero) {
    $rajaus = "AND trlt.konttinumero = '{$konttinumero}'";
  }
  else{
    $rajaus = '';
  }

  $query = "SELECT tilausrivi.tunnus,
            ss.massa,
            trlt.konttinumero,
            trlt.sinettinumero,
            trlt.kontin_mrn
            FROM laskun_lisatiedot
            JOIN lasku
              ON lasku.yhtio = laskun_lisatiedot.yhtio
              AND lasku.tunnus = laskun_lisatiedot.otunnus
            JOIN tilausrivi
              ON tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus = lasku.tunnus
            JOIN tilausrivin_lisatiedot AS trlt
              ON trlt.yhtio = tilausrivi.yhtio
              AND trlt.tilausrivitunnus = tilausrivi.tunnus
            JOIN sarjanumeroseuranta AS ss
              ON ss.yhtio = tilausrivi.yhtio
              AND ss.myyntirivitunnus = tilausrivi.tunnus
            WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
            {$rajaus}
            AND laskun_lisatiedot.konttiviite = '{$konttiviite}'";
  $result = pupe_query($query);

  $kontit = array();

  while ($rulla = mysql_fetch_assoc($result)) {

    $kontit[$rulla['konttinumero']][] = $rulla;

  }

  foreach ($kontit as $konttinumero => $kontti) {

    $konttipaino = 0;
    $konttilista = '';

    foreach ($kontti as $rulla) {
      $konttipaino = $konttipaino + $rulla['massa'];
      $konttilista .= $rulla['tunnus'] . ',';
      $sinettinumero = $rulla['sinettinumero'];
      $mrn = $rulla['kontin_mrn'];
    }

    $konttilista = rtrim($konttilista, ',');

    $kontit[$konttinumero]['lista'] = $konttilista;
    $kontit[$konttinumero]['paino'] = $konttipaino;
    $kontit[$konttinumero]['kpl'] = count($kontti);
    $kontit[$konttinumero]['sinettinumero'] = $sinettinumero;
    $kontit[$konttinumero]['mrn'] = $mrn;


  }

  return $kontit;
}
