<?php

// Enabloidaan, ett� Apache flushaa kaiken mahdollisen ruudulle kokoajan.
ini_set('implicit_flush', 1);
ob_implicit_flush(1);

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

// Ei k�ytet� pakkausta
$compression = FALSE;

require "../inc/parametrit.inc";
require "tulosta_vuosisopimusasiakkaat.inc";
require_once "tulosta_vuosisopimusasiakkaat_excel.inc";
require "inc/ProgressBar.class.php";

if ($asiakas_tarkistus == 1) {
  $ajax_params = array(
    'ytunnus' => $ytunnus,
    'asiakasid' => $asiakasid,
    'alkuvv' => $alkuvv,
    'alkukk' => $alkukk,
    'alkupp' => $alkupp,
    'loppuvv' => $loppuvv,
    'loppukk' => $loppukk,
    'loppupp' => $loppupp,
    'raja' => $raja
  );

  $asiakkaat = hae_asiakkaat($ajax_params, '');

  echo json_encode(count($asiakkaat));

  exit;
}

echo "<font class='head'>".t('Vuosisopimusasiakkaat')."</font><hr>";

if ($ytunnus != "" and $asiakasid == "") {
  if ($muutparametrit == '') {
    $muutparametrit = "$komento#$raja#$emailok#$alkupp#$alkukk#$alkuvv#$loppupp#$loppukk#$loppuvv#";
  }
}

if (isset($muutparametrit) and $tee != '' and $asiakasid != '') {
  list($komento, $raja, $emailok, $alkupp, $alkukk, $alkuvv, $loppupp, $loppukk, $loppuvv) = explode('#', $muutparametrit);
}

if ($tee == "tulosta" and ($raja == "" or !is_numeric($raja))) {
  echo "<font class='error'>".t('RAJA PUUTTUU' , $kieli)."!!!</font><br><br>";
  $tee = "";
}

if ($tee == "tulosta" and (!checkdate($alkukk, $alkupp, $alkuvv) or !checkdate($loppukk, $loppupp, $loppuvv))) {
  echo "<font class='error'>".t('PVM RAJAT PUUTTUU, TAI NE ON VIRHEELLISET', $kieli)."!!!</font><br><br>";
  $tee = "";
}

if ($tee == "tulosta") {
  // haetaan aluksi sopivat asiakkaat
  // viimeisen 12 kuukauden myynti pit�� olla yli $rajan
  echo "<font class='message'>".t('Haetaan sopivia asiakkaita' , $kieli)." (".t('myynti', $kieli)." $alkupvm - $loppupvm ".t('yli' , $kieli)." $raja)... ";

  $params = array(
    'ytunnus' => $ytunnus,
    'asiakasid' => $asiakasid,
    'alkuvv' => $alkuvv,
    'alkukk' => $alkukk,
    'alkupp' => $alkupp,
    'loppuvv' => $loppuvv,
    'loppukk' => $loppukk,
    'loppupp' => $loppupp,
    'raja' => $raja
  );
  $asiakkaat = hae_asiakkaat($params, $laheta_sahkopostit);

  if (!empty($asiakkaat)) {
    echo t('l�ytyi') . ' '.count($asiakkaat) . '<br/><br/>';
  }
  else {
    echo "<font class='error'>".t("Asiakasta ei l�ytynyt") . "</font><br/><br/>";
    $tee = "";
  }
}

if ($tee == "tulosta") {
  flush();

  $edalkupvm  = date("Y-m-d", mktime(0, 0, 0, $alkukk,  $alkupp,  $alkuvv - 1));
  $edloppupvm = date("Y-m-d", mktime(0, 0, 0, $loppukk, $loppupp, $loppuvv - 1));

  $tryre = t_avainsana("OSASTO");
  $osastot = array();

  // Tehd��n temppidiiri jossa toimitaan, niin ei mene failit sekaisin jos on usea rappariajo samaan aikaan
  $tmpdir = "/tmp/VSR_".md5(uniqid(rand(), true))."/";
  mkdir($tmpdir);

  while ($tryro = mysql_fetch_array($tryre)) {
    $osastot[$tryro['selite']] = $tryro['selitetark'];
  }

  $tryre = t_avainsana("TRY");
  $tuoteryhmat = array();

  while ($tryro = mysql_fetch_array($tryre)) {
    $tuoteryhmat[$tryro['selite']] = $tryro['selitetark'];
  }

  $params = array(
    'alkuvv' => $alkuvv,
    'alkukk' => $alkukk,
    'alkupp' => $alkupp,
    'loppuvv' => $loppuvv,
    'loppukk' => $loppukk,
    'loppupp' => $loppupp,
    'edalkupvm' => $edalkupvm,
    'edloppupvm' => $edloppupvm,
    'tuoteryhmat' => $tuoteryhmat,
    'osastot' => $osastot,
    'ytunnus' => $ytunnus,
  );

  echo t('Haetaan myyntitiedot') . '<br/>';

  $data_array = array();

  $bar = new ProgressBar();
  $bar->initialize(count($asiakkaat)-1);

  foreach ($asiakkaat as $asiakas) {
    $bar->increase();
    $tilaukset = hae_tilaukset($params, $asiakas['tunnus']);

    $summa_array = array(
      'sumkpled' => 0,
      'sumkplva' => 0,
      'sumed' => 0,
      'sumva' => 0,
    );

    foreach ($tilaukset['osastoittain'] as $tilaus) {
      $summa_array['sumkpled'] += $tilaus['kpled'];
      $summa_array['sumkplva'] += $tilaus['kplva'];
      $summa_array['sumed'] += $tilaus['ed'];
      $summa_array['sumva'] += $tilaus['va'];
    }

    $summa_array2 = array(
      'sumkpled' => 0,
      'sumkplva' => 0,
      'sumed' => 0,
      'sumva' => 0,
    );

    foreach ($tilaukset['tuoteryhmittain'] as $tilaus) {
      $summa_array2['sumkpled'] += $tilaus['kpled'];
      $summa_array2['sumkplva'] += $tilaus['kplva'];
      $summa_array2['sumed'] += $tilaus['ed'];
      $summa_array2['sumva'] += $tilaus['va'];
    }

    $data_array[] = array(
      'asiakasrow' => $asiakas,
      'tilaukset_ilman_try' => $tilaukset['osastoittain'],
      'summat_ilman_try' => $summa_array,
      'tilaukset_try' => $tilaukset['tuoteryhmittain'],
      'summat_try' => $summa_array2,
    );
  }

  kasittele_tilaukset($data_array, $laheta_sahkopostit, $komento, $params, $generoi_excel, $kieli);

  echo "<br>".t('Kaikki valmista' , $kieli).".</font>";

  // Poistetaan temppidirri
  rmdir($tmpdir);

} // end tee == tulosta

if ($tee == '') {

  if (!isset($alkupp))  $alkupp  = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
  if (!isset($alkukk))  $alkukk  = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
  if (!isset($alkuvv))  $alkuvv  = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));

  if (!isset($loppupp)) $loppupp = date("d");
  if (!isset($loppukk)) $loppukk = date("m");
  if (!isset($loppuyy)) $loppuvv = date("Y");

  echo "<font class='message'>".t('Jos asiakkaalla ei ole s�hk�postia, raportit tulostetaan haluamaasi tulostimeen' , $kieli).".</font><br><br>";

  echo "<form name='vuosiasiakkaat_form' method='post'>";
  echo "<input type='hidden' name='tee' value='tulosta'>";
  echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
  echo "<input type ='hidden' name='muutparametrit' value='$muutparametrit'>";

  echo "<table>";
  echo "<tr><th>".t('Valitse tulostin', $kieli).":</th>";
  echo "<td><select name='komento'>";
  echo "<option value=''>".t('Ei kirjoitinta', $kieli)."</option>";

  $query = "SELECT *
            FROM kirjoittimet
            WHERE yhtio = '$kukarow[yhtio]'
            AND komento not in ('email','EDI')
            ORDER BY kirjoitin";
  $kires = pupe_query($query);

  while ($kirow = mysql_fetch_array($kires)) {
    echo "<option value='$kirow[komento]'>$kirow[kirjoitin]</option>";
  }

  echo "</select></td></tr>";

  echo "<tr><th>".t('Sy�t� ostoraja' , $kieli).":</th>";
  echo "<td><input type='text' name='raja' value='10000' size='10'> $yhtiorow[valkoodi] valitulla ajanjaksolla</td></tr>";
  echo "<tr><th>".t('Generoi excel tiedosto').":</th><td><input type='checkbox' name='generoi_excel' /></td></tr>";
  echo "<tr>";
  echo "<th>".t('L�het� s�hk�postit', $kieli).":</th>";
  echo "<td>
      <input type='radio' name='laheta_sahkopostit' value='ajajalle' checked='checked'>".t('Ohjelman ajajalle' , $kieli)."<br>
      <input type='radio' name='laheta_sahkopostit' value='asiakkaalle'>".t('Asiakkaalle', $kieli)."<br>
      <input type='radio' name='laheta_sahkopostit' value='asiakkaan_myyjalle'>".t('Asiakkaan vastuumyyj�lle', $kieli)."<br>
    </td>";
  echo "</tr>";
  echo "<tr><th>".t('Asiakasnumero', $kieli).":</th>";
  echo "<td><input type='text' name='ytunnus' size='10'> ".t('aja vain t�m� asiakas', $kieli)." (".t('tyhj�', $kieli)."=".t('kaikki', $kieli).")</td></tr>";
  echo "<tr><th>".t('Alku p�iv�m��r�', $kieli).":</th>";
  echo "<td>";
  echo "<input type='text' name='alkupp' value='$alkupp' size='3'>";
  echo "<input type='text' name='alkukk' value='$alkukk' size='3'>";
  echo "<input type='text' name='alkuvv' value='$alkuvv' size='5'> ".t("(pp-kk-vvvv)")."</td></tr>";
  echo "<tr><th>".t('Loppu p�iv�m��r�', $kieli).":</th>";
  echo "<td>";
  echo "<input type='text' name='loppupp' value='$loppupp' size='3'>";
  echo "<input type='text' name='loppukk' value='$loppukk' size='3'>";
  echo "<input type='text' name='loppuvv' value='$loppuvv' size='5'> ".t("(pp-kk-vvvv)")."</td></tr>";
  echo "</table>";

  echo "<br><input type='submit' value='".t('Tulosta', $kieli)."' onclick='if(tarkista()){document.vuosiasiakkaat_form.submit();} else{return false;}'></form>";

?>
<script>
      function tarkista() {
        var ok = true;

        if(!tarkista_tulostin()) {
          ok = false;
        }

        if (ok != false) {
          if (!tarkista_lahettaja()) {
          ok = false;
        }
        }
        return ok;
      }

      function tarkista_tulostin() {
        if ($('input[name=laheta_sahkopostit]:checked').val() == 'asiakkaalle' && $('select[name=komento]').val() == '') {
          alert('Asiakkaalle raportteja l�hetett�ess� pit�� olla valittuna printteri, johon s�hk�postittomat raportit tulostetaan.');
          return false;
        }
        else {
          return true;
        }
      }

      function tarkista_lahettaja() {
        if($('input[name=laheta_sahkopostit]:checked').val() == 'asiakkaalle') {
          var ok;
          $.ajax({
            type: 'POST',
            url: 'vuosisopimusasiakkaat.php?asiakas_tarkistus=1&no_head=yes',
            data: {
              'ytunnus': $('input[name=ytunnus]').val(),
              'asiakasid': '',
              'raja': $('input[name=raja]').val(),
              'alkuvv': $('input[name=alkuvv]').val(),
              'alkukk': $('input[name=alkukk]').val(),
              'alkupp': $('input[name=alkupp]').val(),
              'loppuvv': $('input[name=loppuvv]').val(),
              'loppukk': $('input[name=loppukk]').val(),
              'loppupp': $('input[name=loppupp]').val()
            },
            success: function(data) {
              ok = confirm('S�hk�posteja olisi l�hd�ss� '+data+' kappaletta. Oletko varma, ett� haluat l�hett��?');
            },
            async:false
          });

          if(ok) {
            return true;
          }
          else {
            return false;
          }
        }
        else {
          return true;
        }
      }
</script>
  <?php
}

function hae_asiakkaat($params, $laheta_sahkopostit) {
  global $kukarow;

  //valittu asiakas
  if ($params['ytunnus'] != '') {
    $query = "SELECT *
              FROM asiakas
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND asiakasnro = '{$params['ytunnus']}'";
    $result = pupe_query($query);

    if ($asiakas_row = mysql_fetch_assoc($result)) {
      $aswhere = "and lasku.liitostunnus = '{$asiakas_row['tunnus']}'";
    }
    else {
      $aswhere = "";

      return false;
    }
  }
  else {
    $aswhere = "";
  }

  if ($laheta_sahkopostit == 'asiakkaan_myyjalle') {
    $select = "kuka.eposti myyja_eposti,";
    $join = "  JOIN asiakas ON ( asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus AND asiakas.myyjanro != 0)
          JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.myyja = asiakas.myyjanro)";
    $group = ",myyja_eposti";
  }
  else {
    $select = "";
    $join = "JOIN asiakas ON ( asiakas.yhtio = lasku.yhtio AND asiakas.tunnus = lasku.liitostunnus )";
    $group = "";
  }

  $query = "SELECT asiakas.tunnus,
            asiakas.email asiakas_email,
            asiakas.ytunnus,
            asiakas.asiakasnro,
            asiakas.nimi,
            asiakas.nimitark,
            asiakas.osoite,
            asiakas.postino,
            asiakas.postitp,
            {$select}
            sum(arvo)     arvo
            FROM lasku USE INDEX (yhtio_tila_tapvm)
            {$join}
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tila    = 'L'
            and lasku.alatila = 'X'
            and lasku.tapvm   >= '{$params['alkuvv']}-{$params['alkukk']}-{$params['alkupp']}'
            and lasku.tapvm   <= '{$params['loppuvv']}-{$params['loppukk']}-{$params['loppupp']}'
            $aswhere
            GROUP BY asiakas.tunnus,
            asiakas_email,
            asiakas.ytunnus,
            asiakas.asiakasnro,
            asiakas.nimi,
            asiakas.nimitark,
            asiakas.osoite,
            asiakas.postino,
            asiakas.postitp
            {$group}
            HAVING sum(arvo) > {$params['raja']}";
  $result = pupe_query($query);

  $asiakkaat = array();
  while ($row = mysql_fetch_assoc($result)) {
    $asiakkaat[] = $row;
  }

  return $asiakkaat;
}

function hae_tilaukset($params, $asiakas_tunnus) {
  global $kukarow;

  $select = "tuote.osasto, tuote.try,";
  $group = "osasto, try";
  $order = "osasto, try";

  $query = "SELECT {$select}
            sum(if (tapvm >= '{$params['alkuvv']}-{$params['alkukk']}-{$params['alkupp']}'    and tapvm <= '{$params['loppuvv']}-{$params['loppukk']}-{$params['loppupp']}', tilausrivi.rivihinta, 0)) va,
            sum(if (tapvm >= '{$params['edalkupvm']}'                      and tapvm <= '{$params['edloppupvm']}', tilausrivi.rivihinta, 0)) ed,
            sum(if (tapvm >= '{$params['alkuvv']}-{$params['alkukk']}-{$params['alkupp']}'    and tapvm <= '{$params['loppuvv']}-{$params['loppukk']}-{$params['loppupp']}', tilausrivi.kpl, 0)) kplva,
            sum(if (tapvm >= '{$params['edalkupvm']}'                      and tapvm <= '{$params['edloppupvm']}', tilausrivi.kpl, 0)) kpled
            FROM lasku USE INDEX (yhtio_tila_liitostunnus_tapvm)
            JOIN tilausrivi USE INDEX (yhtio_otunnus) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'L' and tilausrivi.try >= 0)
            JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
            WHERE lasku.yhtio      = '{$kukarow['yhtio']}'
            AND lasku.liitostunnus = '{$asiakas_tunnus}'
            AND lasku.tapvm        >= '{$params['edalkupvm']}'
            AND lasku.tila         = 'L'
            AND lasku.alatila      = 'X'
            GROUP BY {$group}
            HAVING va != 0 OR ed != 0 OR kplva != 0 OR kpled != 0
            ORDER BY {$order}";
  $result = pupe_query($query);

  $tilaukset_tuoteryhmittain = array();
  $tilaukset_osastoittain = array();

  while ($row = mysql_fetch_assoc($result)) {

    if ($row['osasto'] < 10000) {
      $tilaukset_osastoittain[$row['osasto']]['va'] += $row['va'];
      $tilaukset_osastoittain[$row['osasto']]['ed'] += $row['ed'];
      $tilaukset_osastoittain[$row['osasto']]['kplva'] += $row['kplva'];
      $tilaukset_osastoittain[$row['osasto']]['kpled'] += $row['kpled'];
      $tilaukset_osastoittain[$row['osasto']]['osasto'] = $row['osasto'];
      $tilaukset_osastoittain[$row['osasto']]['osasto_nimitys'] = $params['osastot'][$row['osasto']];

      $row['tuoteryhma_nimitys'] = $params['tuoteryhmat'][$row['try']];
      $row['osasto_nimitys'] = $params['osastot'][$row['osasto']];
      $tilaukset_tuoteryhmittain[] = $row;
    }
  }

  return array(
    'osastoittain'     => $tilaukset_osastoittain,
    'tuoteryhmittain'   => $tilaukset_tuoteryhmittain
  );
}

function kasittele_tilaukset($data_array, $laheta_sahkopostit, $komento, $params, $generoi_excel, $kieli) {
  global $kukarow;

  if ($generoi_excel == 'on') {
    $tiedostot = generoi_excel_tiedostot($data_array, $params, $kieli);
  }
  else {
    $tiedostot = generoi_pdf_tiedostot($data_array, $params, $kieli);
  }

  switch ($laheta_sahkopostit) {
  case 'ajajalle':
    $email = $kukarow['eposti'];

    if (empty($params['ytunnus'])) {
      luo_zip_ja_laheta($tiedostot, $email);
    }
    else {
      //jos ytunnus on annettu tied�mme, ett� generoidaan vain yksi raportti
      laheta_email($email, $tiedostot);
    }
    break;

  case 'asiakkaalle':
    foreach ($data_array as $data) {
      $email = $data['asiakasrow']['asiakas_email'];
      $tiedosto = $data['tiedosto'];
      if ($email != '') {
        laheta_email($email, array($tiedosto));
      }
      else {
        tulosta_raportit(array($tiedosto), $komento);
      }

      unlink($tiedosto);
    }
    break;

  case 'asiakkaan_myyjalle':
    //k�yd��n data_array l�pi jotta saamme raportin sille kuuluvan myyj�n alle
    $myyjien_tiedostot = array();
    foreach ($data_array as $data) {
      $myyjien_tiedostot[$data['asiakasrow']['myyja_eposti']][] = $data['tiedosto'];
    }
    foreach ($myyjien_tiedostot as $myyja_eposti => $tiedosto_array) {
      $email = $myyja_eposti;
      if ($params['ytunnus'] == '') {
        luo_zip_ja_laheta($tiedosto_array, $email);
      }
      else {
        //jos ytunnus on annettu tied�mme, ett� generoidaan vain yksi raportti
        laheta_email($email, $tiedosto_array);
      }
    }
    break;

  default:
    $email = $kukarow['eposti'];

    if (empty($params['ytunnus'])) {
      luo_zip_ja_laheta($tiedostot, $email);
    }
    else {
      laheta_email($email, $tiedostot);
    }
    break;
  }
}

function generoi_pdf_tiedostot(&$data_array, $params, $kieli) {
  global $pdf, $asiakasrow, $yhtiorow, $sivu, $norm, $pieni, $pvm, $alkuvv, $alkukk, $alkupp, $loppuvv, $loppukk, $loppupp, $kala, $sivu, $lask, $sumkpled, $sumkplva, $sumed, $sumva, $asiakas_numero;

  $alkuvv = $params['alkuvv'];
  $alkukk = $params['alkukk'];
  $alkupp = $params['alkupp'];
  $loppuvv = $params['loppuvv'];
  $loppukk = $params['loppukk'];
  $loppupp = $params['loppupp'];

  $pdf_tiedostot = array();
  $i = 0;
  echo '<br/>'. t('Tehd��n pdf tiedostot') . '<br/>';
  $bar2 = new ProgressBar();
  $bar2->initialize(count($data_array)-1);
  foreach ($data_array as &$data) {


    $bar2->increase();
    $pdf = new pdffile();
    $pdf->set_default('margin-top',   0);
    $pdf->set_default('margin-bottom',   0);
    $pdf->set_default('margin-left',   0);
    $pdf->set_default('margin-right',   0);

    // defaultteja layouttiin
    $kala = 575;
    $lask = 1;
    $sivu = 1;

    $asiakasrow = $data['asiakasrow'];
    // kirjotetaan header
    $firstpage = alku("osasto");

    $firstpage = rivi_kaikki($firstpage, 'osasto', $data['tilaukset_ilman_try'], $params);

    $sumkpled = $data['summat_ilman_try']['sumkpled'];
    $sumkplva = $data['summat_ilman_try']['sumkplva'];
    $sumed = $data['summat_ilman_try']['sumed'];
    $sumva = $data['summat_ilman_try']['sumva'];
    // kirjotetaan footer
    loppu($firstpage, "dontsend");

    // defaultteja layouttiin
    $kala = 575;
    $lask = 1;
    $sivu = 1;

    // uus pdf header
    $firstpage = alku();

    $firstpage = rivi_kaikki($firstpage , '' , $data['tilaukset_try']);

    $sumkpled = $data['summat_try']['sumkpled'];
    $sumkplva = $data['summat_try']['sumkplva'];
    $sumed = $data['summat_try']['sumed'];
    $sumva = $data['summat_try']['sumva'];
    $asiakas_numero = $data['asiakasrow']['asiakasnro'];
    // kirjotetaan footer ja palautetaan luodun tiedoston polku
    $pdf_tiedostot[] = loppu($firstpage);

    $data['tiedosto'] = $pdf_tiedostot[$i];

    $i++;
  }

  return $pdf_tiedostot;
}

function generoi_excel_tiedostot(&$data_array, $params, $kieli) {
  global $yhtiorow;

  echo '<br/>'. t('Tehd��n excel tiedostot') . '<br/>';
  $bar2 = new ProgressBar();
  $bar2->initialize(count($data_array)-1);
  $excel_tiedostot = array();
  $i = 0;
  foreach ($data_array as &$data) {
    $bar2->increase();
    $temp_data = array(
      'osastoittain' => $data['tilaukset_ilman_try'],
      'tuoteryhmittain' => $data['tilaukset_try']
    );
    $excel = new vuosisopimus_asiakkaat_excel();
    $excel->set_kieli($kieli);
    $excel->set_asiakas($data['asiakasrow']);
    $excel->set_yhtiorow($yhtiorow);
    $alkumiinusyks = $params['alkuvv']-1;
    $loppumiinusyks = $params['loppuvv']-1;
    $excel->set_rajaus_paivat(array(
        'alkupaiva' => $params['alkupp'] . '.' . $params['alkukk'] . '.' . $params['alkuvv'],
        'loppupaiva' => $params['loppupp'] . '.' . $params['loppukk'] . '.' .$params['loppuvv'],
        'edalkupaiva' => $params['alkupp'] . '.' . $params['alkukk'] . '.' . $alkumiinusyks,
        'edloppupaiva' => $params['loppupp'] . '.' . $params['loppukk'] . '.' .$loppumiinusyks,
      ));
    $excel->set_tilausrivit($temp_data);
    $excel->set_summat_osastoittain($data['summat_ilman_try']);
    $excel->set_summat_tuoteryhmittain($data['summat_try']);

    $excel_tiedostot[] = $excel->generoi();
    $data['tiedosto'] = $excel_tiedostot[$i];

    unset($excel);
    $i++;
  }

  return $excel_tiedostot;
}

function luo_zip_ja_laheta($tiedostot, $email_address) {
  global $yhtiorow, $kieli;

  $maaranpaa = $GLOBALS['tmpdir'].'Ostoseuranta_raportit.zip';

  $ylikirjoita = true;//ihan varmuuden vuoks

  if (luo_zip($tiedostot, $maaranpaa, $ylikirjoita)) {
    //l�hetet��n email
    laheta_email($email_address, array($maaranpaa));

    //poistetaan zippi
    unlink($maaranpaa);
  }
  else {
    echo t("Zipin luominen ep�onnistui", $kieli);
  }

  //poistetaan pdf tiedostot
  foreach ($tiedostot as $tiedosto) {
    unlink($tiedosto);
  }
}

function laheta_email($email_address, array $liitetiedostot_path = array()) {
  global $yhtiorow, $kieli;

  $params = array(
    "to"     => $email_address,
    "subject"   => $yhtiorow['nimi'] . " - " . t('Vuosisopimusraportti' , $kieli) . ' ' . date("d.m.Y"),
    "ctype"     => "html",
    "body"     => t('Liitteen� l�ytyy vuosisopimusraportit' , $kieli),
    "attachements" => array()
  );

  foreach ($liitetiedostot_path as $liitetiedosto_path) {
    $tiedosto_nimi = explode('/', $liitetiedosto_path);
    $hakemiston_syvyys = count($tiedosto_nimi);
    $tiedosto_nimi = $tiedosto_nimi[$hakemiston_syvyys - 1];

    $liitetiedosto =  array(
      'filename' => $liitetiedosto_path,
      'newfilename' => t($tiedosto_nimi, $kieli),
    );

    $params['attachements'][] = $liitetiedosto;
  }

  pupesoft_sahkoposti($params);
}

function tulosta_raportit($tiedostot, $komento) {
  global $kieli;

  echo t("Tulostetaan asiakkaan ostoseuranta tulostimeen {$komento}", $kieli);

  foreach ($tiedostot as $tiedosto) {
    $line = exec($komento." ".$tiedosto);

    //poistetaan tulostettu tiedosto
    unlink($tiedosto);
  }
}

function luo_zip($tiedostot = array(), $maaranpaa = '', $ylikirjoita = true) {
  //if the zip file already exists and overwrite is false, return false
  if (file_exists($maaranpaa) && !$ylikirjoita) {
    return false;
  }
  //vars
  $validit_tiedostot = array();
  //if files were passed in...
  if (is_array($tiedostot)) {
    //cycle through each file
    foreach ($tiedostot as $tiedosto) {
      //make sure the file exists
      if (file_exists($tiedosto)) {
        $validit_tiedostot[] = $tiedosto;
      }
    }
  }
  //if we have good files...
  if (count($validit_tiedostot)) {
    //create the archive
    $zip = new ZipArchive();
    if ($zip->open($maaranpaa, $ylikirjoita ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
      return false;
    }
    //add the files
    foreach ($validit_tiedostot as $tiedosto) {
      //haetaan tiedoston nimi, jotta ei tule zipattua koko hakemisto rakennetta
      $tiedosto_temp = explode('/', $tiedosto);
      $hakemiston_syvyys = count($tiedosto_temp);
      $tiedoston_nimi = $tiedosto_temp[$hakemiston_syvyys - 1];
      $zip->addFile($tiedosto, $tiedoston_nimi);
    }
    //debug
    //echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
    //close the zip -- done!
    $zip->close();

    //check to make sure the file exists
    return file_exists($maaranpaa);
  }
  else {
    return false;
  }
}

require "inc/footer.inc";
