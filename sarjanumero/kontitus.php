<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

require 'generoi_edifact.inc';

if (!isset($aktiivi_group)) {
  $aktiivi_group = false;
}

$errors = array();

if (isset($submit)) {

  switch ($submit) {
  case 'konttiviite':
    if (empty($konttiviite)) {
      $errors[] = t("Syˆt‰ konttiviite");
      $view = 'konttiviite';
    }
    else {

      $query = "SELECT lasku.sisviesti1 AS ohje,
                laskun_lisatiedot.konttityyppi,
                laskun_lisatiedot.konttimaara,
                tilausrivi.toimitettu,
                tilausrivi.tunnus,
                tilausrivi.var,
                trlt.konttinumero,
                ss.hyllyalue,
                ss.hyllynro,
                lasku.asiakkaan_tilausnumero
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
                  ON ss.yhtio = lasku.yhtio
                  AND ss.myyntirivitunnus = tilausrivi.tunnus
                WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
                AND laskun_lisatiedot.konttiviite = '{$konttiviite}'";

      if ($muutos == 'muutos' ) {

        $result = pupe_query($query);

        while ($rulla = mysql_fetch_assoc($result)) {

          if ($rulla['toimitettu'] == '') {

            $uquery = "UPDATE tilausrivi SET
                      keratty = '',
                      kerattyaika = '0000-00-00 00:00:00'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus = '{$rulla['tunnus']}'";
            pupe_query($uquery);

            $uquery = "UPDATE tilausrivin_lisatiedot SET
                      konttinumero = ''
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tilausrivitunnus = '{$rulla['tunnus']}'";
            pupe_query($uquery);

          }
        }
      }

      $yliajo = false;

      $tuloutettu = true;
      $rullia_loytyy = true;
      $kontissa = false;
      $ei_kontissa = false;
      $kontitettu = false;

      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        $rullia_loytyy = false;
      }
      else{

        $rullia = mysql_num_rows($result);

        $rivitunnukset = '';

        while ($rulla = mysql_fetch_assoc($result)) {

          if ($rulla['var'] == 'P') {
            $tuloutettu = false;
          }

          if ($rulla['toimitettu'] != '') {
            $kontitettu = true;
          }

          if ($rulla['konttinumero'] != '') {
            $kontissa = true;
          }
          else{
            $ei_kontissa = true;
          }

          $tilaukset[$rulla['asiakkaan_tilausnumero']][] = $rulla;

          $kontitusohje = $rulla['ohje'];
          $tyyppi = $rulla['konttityyppi'];
          $konttimaara = $rulla['konttimaara'];

          $rivitunnukset .= $rulla['tunnus'] . ',';
        }
      }



      $rivitunnukset = rtrim($rivitunnukset, ',');

      if ($rullia_loytyy == false) {
        $errors[] = t("Ei lˆytynyt kontitettavia rullia.");
        $view = 'konttiviite';
      }
      elseif ($tuloutettu == false) {
        $errors[] = t("Kaikkia rullia ei ole tuloutettu.");
        $view = 'konttiviite';
      }
      elseif ($kontitettu == true) {
        $errors[] = t("Rullat on jo kontitettu ja kontti sinetˆity.");
        $view = 'konttiviite';
      }
      elseif ($kontissa == true and $ei_kontissa == false) {
        $errors[] = t("Kaikki viitteen alaiset rullat on jo kontitettu.");
        $yliajo = true;
        $view = 'konttiviite';
      }
      elseif ($kontissa == true and $ei_kontissa == true) {
        $errors[] = t("Osa viitteen alaisista rullista on jo kontitettu.");
        $yliajo = 'X';
        $view = 'konttiviite';
      }
      else{

        $info = array(
          'kontitusohje' => $kontitusohje,
          'tyyppi' => $tyyppi,
          'konttimaara' => $konttimaara
          );

        // kovakoodatut max-kilot...
        switch ($info['tyyppi']) {
        case 'C20':
        case 'C20OP':
          $info['maxkg'] = 22000;
          break;
        case 'C40':
        case 'C40OP':
        case 'C40HC':
          $info['maxkg'] = 27000;
          break;
        default:
          $info['maxkg'] = 22000;
        }

        $rullat_varastossa = array();

        foreach ($tilaukset as $tilaus => $rullat) {
          $_tilaus = array();
          foreach ($rullat as $key => $rulla) {
            $varasto = $rulla['hyllyalue'] . "-" . $rulla['hyllynro'];
            if (!isset($_tilaus[$varasto])) {
              $_tilaus[$varasto] = 1;
            }
            else {
              $_tilaus[$varasto]++;
            }
          }
          $rullat_varastossa[$tilaus] = $_tilaus;
        }

        $view = 'konttiviite_maxkg';
      }
    }
    break;
  case 'konttiviite_maxkg':

    if (empty($maxkg)) {
      $errors[] = t("Syˆt‰ kilom‰‰r‰");
      $view = 'konttiviite_maxkg';
    }
    else {

      $rullat_ja_kontit = rullat_ja_kontit($konttiviite, $maxkg);

      $kontittamattomat = $rullat_ja_kontit['kontittamattomat'];
      $kontitetut = $rullat_ja_kontit['kontitetut'];
      $kontit = $rullat_ja_kontit['kontit'];
      $konttimaara = count($kontit);

      if ($konttimaara > $bookattu_konttimaara) {
        $erotus = $konttimaara - $bookattu_konttimaara;
        $huomio  = t("Huom. N‰ytt‰‰ silt‰, ett‰ bookattu konttim‰‰r‰ ei riit‰ kaikille rullille:");
        $huomio .= " " . $erotus . " " . t("konttia lis‰tty.");
      }

      $aktiivinen_kontti = 1;

      if ($rullat_ja_kontit === false) {
        $errors[] = t("Tilausnumerolla ei lˆydy tilausta.");
        $view = 'tilausnumero';
      }
      elseif(count($kontittamattomat) == 0 and count($kontitetut) == 0) {
        $errors[] = t("Tilauksella ei ole kontitettavia rullia.");
        $view = 'tilausnumero';
      }
      else{
        $view = 'kontituslista';
      }
    }
    break;
  case 'jatka':
    $query = "SELECT trlt.konttinumero
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
              WHERE laskun_lisatiedot.yhtio = '{$kukarow['yhtio']}'
              AND laskun_lisatiedot.konttiviite = '{$konttiviite}'";
    $result = pupe_query($query);
    $konttiinfo = mysql_fetch_assoc($result);
    $konttiinfo = $konttiinfo['konttinumero'];
    $konttiinfo = explode("/", $konttiinfo);

    $maxkg = $konttiinfo[2];

    $rullat_ja_kontit = rullat_ja_kontit($konttiviite, $maxkg);

    $kontittamattomat = $rullat_ja_kontit['kontittamattomat'];
    $kontitetut = $rullat_ja_kontit['kontitetut'];
    $kontit = $rullat_ja_kontit['kontit'];
    $konttimaara = count($kontit);

    $aktiivinen_kontti = 1;

    if ($rullat_ja_kontit === false) {
      $errors[] = t("Tilausnumerolla ei lˆydy tilausta.");
      $view = 'tilausnumero';
    }
    elseif(count($kontittamattomat) == 0 and count($kontitetut) == 0) {
      $errors[] = t("Tilauksella ei ole kontitettavia rullia.");
      $view = 'tilausnumero';
    }
    else{
      $view = 'kontituslista';
    }

    break;

  case 'konttivalinta':
    if (!isset($aktiivinen_kontti)) {
      $aktiivinen_kontti = 1;
    }

    $rullat_ja_kontit = rullat_ja_kontit($konttiviite, $maxkg);
    $kontittamattomat = $rullat_ja_kontit['kontittamattomat'];
    $kontitetut = $rullat_ja_kontit['kontitetut'];
    $kontit = $rullat_ja_kontit['kontit'];
    $konttimaara = count($kontit);
    $view = 'kontituslista';
    break;
  case 'sarjanumero':
    $query = "SELECT myyntirivitunnus
              FROM sarjanumeroseuranta
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND sarjanumero = '{$sarjanumero}'";
    $result = pupe_query($query);
    $rivitunnus = mysql_result($result, 0);

    if ($rivitunnus) {

      $query = "UPDATE tilausrivi SET
                keratty = '{$kukarow['kuka']}',
                kerattyaika = NOW()
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$rivitunnus}'";
      pupe_query($query);

      $temp_konttinumero = $aktiivinen_kontti . "/" . $konttimaara . "/" . $maxkg;

      $query = "UPDATE tilausrivin_lisatiedot SET
                konttinumero = '{$temp_konttinumero}'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tilausrivitunnus = '{$rivitunnus}'";
      pupe_query($query);


    }
    else {

      $errors[] = t("Tuntematon sarjanumero.");

    }

    $rullat_ja_kontit = rullat_ja_kontit($konttiviite, $maxkg);

    $kontittamattomat = $rullat_ja_kontit['kontittamattomat'];
    $kontitetut = $rullat_ja_kontit['kontitetut'];

    foreach ($kontitetut as $kontitettu) {
      if ($kontitettu['sarjanumero'] == $sarjanumero) {
        $aktiivi_group = $kontitettu['group_class'];
      }
    }


    $kontit = $rullat_ja_kontit['kontit'];
    $view = 'kontituslista';
    break;
  case 'vahvista':
  default:
    $errors[] = 'error';
  }
}
else {
  $view = 'konttiviite';
}

echo "<div class='header'>";

echo "<div class='header_left'>";
echo "<a href='index.php' class='button header_button'>";
echo t("P‰‰valikko");
echo "</a>";
echo "</div>";

echo "<div class='header_center'>";
echo "<h1>";
echo t("KONTITUS");
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo t("Kirjaudu ulos");
echo "</a>";
echo "</div>";

echo "</div>";

echo "<div style='text-align:center;padding:0; margin:0 auto;'>";

echo "<div class='error center'>";

foreach ($errors as $error) {
  echo $error."<br>";
}
echo "</div>";

if ($view == 'konttiviite') {

  if (!$yliajo) {



    echo "<div class='subheader'>";

    echo "<div class='subheader_left'>";
    echo "<div class='tapfocus'></div>";
    echo "</div>";

    echo "<div class='subheader_center'>";

    echo "
    <form method='post' action=''>
        <label for='konttiviite'>", t("Konttiviite"), "</label><br>
        <input type='text' id='konttiviite' name='konttiviite' style='margin:10px;' />
        <br>
        <button name='submit' value='konttiviite' onclick='submit();' class='button'>", t("OK"), "</button>
    </form>";

    echo "</div>";

    echo "<div class='subheader_right'>";
    echo "<div class='tapfocus' style='float:right;'></div>";
    echo "</div>";

    echo "</div>";



  }

  if ($yliajo) {

    echo "
      <div style='display:inline-block; margin:6px;'>
      <form method='post' action=''>
        <input type='hidden' name='konttiviite' value='{$konttiviite}' />
        <input type='hidden' name='muutos' value='muutos' />
        <button name='submit' value='konttiviite' onclick='submit();' class='{$luokka}'>" . t("Muuta kontitusta") . "</button>
      </form>
      </div>";
  }

  if ($yliajo === "X") {

    echo "
      <div style='display:inline-block; margin:6px;'>
      <form method='post' action=''>
        <input type='hidden' name='konttiviite' value='{$konttiviite}' />
        <button name='submit' value='jatka' onclick='submit();' class='{$luokka}'>" . t("Jatka kontitusta") . "</button>
      </form>
      </div>";
  }


}





if ($view == 'konttiviite_maxkg') {




  echo "<div class='subheader'>";

  echo "<div class='subheader_left'>";
  echo "<div class='tapfocus'></div>";
  echo "</div>";

  echo "<div class='subheader_center'>";


  echo "<div style='text-align:center;padding:10px; margin:0 auto;'>";
  echo "<table border='0'>";

  echo "<tr>";
  echo "<td style='text-align:right; width:50%'>" . t("Konttiviite") . ": </td>";
  echo "<td style='text-align:left; width:50%'>{$konttiviite}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td style='text-align:right;'>" . t("Konttityyppi") . ": </td>";
  echo "<td style='text-align:left;'>{$info['tyyppi']}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td style='text-align:right;'>" . t("Bookattu m‰‰r‰") . ": </td>";
  echo "<td style='text-align:left;'>{$info['konttimaara']}</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td style='text-align:right;'>" . t("Max-kapasiteetti") . ": </td>";
  echo "<td style='text-align:left;'>{$info['maxkg']}</td>";
  echo "</tr>";

  if ($info['kontitusohje'] != '') {

    echo "<tr>";
    echo "<td colspan='2' style='padding:8px 0'>";

    echo "<div class='ohjediv'>";
    echo "Bookkaussanoman kontitusohje:<br><br>";
    echo $info['kontitusohje'];
    echo "</div>";

    echo "</td>";
    echo "</tr>";

  }

  echo "<tr>";
  echo "<td colspan='2'  style='padding:8px 0'>Rullien sijainnit:</td>";
  echo "</tr>";

  foreach ($rullat_varastossa as $tilaus => $varastot) {

    echo "<tr>";
    echo "<td style='text-align:center; width:100%; padding:10px  0 0 0' colspan='2'><b>{$tilaus}</b></td>";
    echo "</tr>";

    foreach ($varastot as $hylly => $maara) {
      echo "<tr>";
      echo "<td style='text-align:right; width:50%'>{$hylly}: </td>";
      echo "<td style='text-align:left; width:50%'> {$maara} kpl.</td>";
      echo "</tr>";
    }
  }



  echo "<tr>";
  echo "<td align='right' style='padding-top:10px'>Yhteens‰: </td>";
  echo "<td align='left'  style='padding-top:10px'> " . $rullia . " kpl.</td>";
  echo "</tr>";

  echo "</table>";
  echo "</div>";

  echo "
  <form method='post' action=''>
    <div style='text-align:center;padding:10px;'>
      <label for='maxkg'>", t("Konttien maksimi kilom‰‰r‰"), "</label><br>
      <input type='hidden' name='konttiviite' value='{$konttiviite}' />
      <input type='hidden' name='bookattu_konttimaara' value='{$info['konttimaara']}' />
      <input type='text' id='maxkg' name='maxkg' style='margin:10px;' value='{$info['maxkg']}' />
      <br>
      <button name='submit' value='konttiviite_maxkg' onclick='submit();' class='button'>", t("Jatka"), "</button>
    </div>
  </form>";


  echo "</div>";

  echo "<div class='subheader_right'>";
  echo "<div class='tapfocus' style='float:right;'></div>";
  echo "</div>";

  echo "</div>";



}

if ($view == 'kontituslista') {

  if (isset($huomio)) {
    echo $huomio;
  }



  echo "<div class='subheader'>";

  echo "<div class='subheader_left'>";
  echo "<div class='tapfocus'></div>";
  echo "</div>";

  echo "<div class='subheader_center'>";


  echo "
  <form method='post' action=''>
      <label for='sarjanumero'>", t("Sarjanumero"), "</label><br>
      <input type='text' id='sarjanumero' name='sarjanumero' style='margin:10px;' />
      <input type='hidden' name='konttiviite' value='{$konttiviite}' />
      <input type='hidden' name='maxkg' value='{$maxkg}' />
      <input type='hidden' name='konttimaara' value='{$konttimaara}' />
      <input type='hidden' name='aktiivinen_kontti' value='{$aktiivinen_kontti}' />
      <input type='hidden' name='aktiivi_group' class='aktiivi_group' value='{$aktiivi_group}' />
      <br>
      <button name='submit' value='sarjanumero' onclick='submit();' class='button'>", t("OK"), "</button>
  </form>";

  echo "</div>";

  echo "<div class='subheader_right'>";
  echo "<div class='tapfocus' style='float:right;'></div>";
  echo "</div>";

  echo "</div>";










  $tarvittava_maara = count($kontit);

  echo "<div style='text-align:center; padding:10px; width:700px; margin:0 auto; overflow:auto;'>";

  $konttien_painot = array();
  $konttien_kpl = array();

  foreach ($kontitetut as $rulla) {

    $kontitusinfo = explode("/", $rulla['konttinumero']);
    $konttinumero = $kontitusinfo[0];

    $konttien_painot[$konttinumero] = $konttien_painot[$konttinumero] + $rulla['paino'];
    $konttien_kpl[$konttinumero] = $konttien_kpl[$konttinumero] + 1;

  }

  foreach ($kontit as $key => $kontti) {

    if ($key == $aktiivinen_kontti) {
      $luokka = "button aktiivi";
    }
    else {
      $luokka = "button";
    }

    echo "<div style='display:inline-block; margin:6px;'>";
    echo "<form method='post' action=''>";
    echo "<input type='hidden' name='aktiivinen_kontti' value='{$key}' />";
    echo "<input type='hidden' name='konttiviite' value='{$konttiviite}' />";
    echo "<input type='hidden' name='aktiivi_group' class='aktiivi_group' value='{$aktiivi_group}' />";
    echo "<input type='hidden' name='maxkg' value='{$maxkg}' />";
    echo "<button name='submit' value='konttivalinta' onclick='submit();' class='{$luokka}'>";
    echo t("Kontti") ."-". $key ;

    if ($konttien_painot[$key] > 0 and $konttien_kpl[$key] > 0) {
      echo " (" . $konttien_painot[$key] . " kg, " . $konttien_kpl[$key] . " kpl)";
    }

    echo "</button></form></div>";

  }

  if (count($kontittamattomat) > 0) {

    echo "<div style='padding:20px;'>" . t("Kontittamattomat rullat") . ":</div>";


    echo "<div class='listadiv otsikkodiv lista_header'>";
    echo "<div class='peruslista_left'>";
    echo "Sijainti";
    echo "</div>";

    echo "<div class='peruslista_center'>";
    echo "Tilaus #";
    echo "</div>";

    echo "<div class='peruslista_right'>";
    echo "Paino (kg)";
    echo "</div>";

  }
  else{

    echo "<div>";
    echo t("Kaikki rullat kontitettu!");
    echo "</div>";

  }

  echo "</div>";

  foreach ($kontittamattomat as $rulla) {

    $group_class = $rulla['group_class'];

    if ($group_class == $aktiivi_group or (count($otsikoidut) < 1 and !$aktiivi_group) or ($oletus_aktiivi == $group_class and !$aktiivi_group)) {
      $display = 'block';
      $otsikko_tila ='avoin_otsikko';
      $nuoli = '';
      $oletus_aktiivi = $group_class;
    }
    else{
     $display = 'none';
     $otsikko_tila ='';
     $nuoli = '&#x25BC;';
    }

    if (!in_array($group_class, $otsikoidut)) {

      echo "<div class='listadiv otsikkodiv {$group_class}-otsikko {$otsikko_tila}'>";


      echo "<div class='otsikko_left'>";
      echo "<span class='nuoli {$group_class}-nuoli'>{$nuoli}</span>";
      echo "</div>";

      echo "<div class='otsikko_center'>";
      echo $rulla['asiakkaan_tilausnumero'];
      echo " - " . $rulla['paikka'];
      echo " - " . $rullat_ja_kontit['ryhma_laskuri'][$group_class] . " kpl";
      echo "</div>";

      echo "<div class='otsikko_right'>";
      echo "<span class='nuoli {$group_class}-nuoli'>{$nuoli}</span>";
      echo "</div>";


      echo "</div>";

      $otsikoidut[] = $group_class;

    }

    echo "<div class='listadiv perus {$group_class}' style='display:{$display};'>";

    echo "<div class='peruslista_left'>";
    echo $rulla['paikka'];
    echo "</div>";

    echo "<div class='peruslista_center'>";
    echo $rulla['asiakkaan_tilausnumero'];
    echo "</div>";

    echo "<div class='peruslista_right'>";
    echo (INT) $rulla['paino'];
    echo "</div>";


    echo "</div>";
  }

/*

  foreach ($kontitetut as $rulla) {

    $kontitusinfo = explode("/", $rulla['konttinumero']);
    $konttinumero = $kontitusinfo[0];

    echo "<div class='listadiv viety'>";
    echo "Tilaus: " . $rulla['asiakkaan_tilausnumero'];
    echo ", Paino: " . (INT) $rulla['paino'];
    echo ", Kontti: " . $konttinumero;
    echo "</div>";
  }

*/

  echo "</div>";
}

echo "</div>";



echo "<script type='text/javascript'>";

foreach ($otsikoidut as $luokka) {

echo "

  $('.{$luokka}-otsikko')..bind('touchstart',function(){

    if ( !$(this).hasClass('avoin_otsikko')) {

      $('.otsikkodiv').removeClass('avoin_otsikko');
      $(this).addClass('avoin_otsikko');
      $('.perus').slideUp(200);
      $('.{$luokka}').slideDown(200);
      $('.aktiivi_group').val('{$luokka}');
      $('.nuoli').html('&#x25BC;');
      $('.{$luokka}-nuoli').html('');

    }

  });

";


}


echo "

  $('.tapfocus').bind('touchstart',function(){
    $('input').focus();
    $('input').setSelectionRange(0, 9999);
  });

</script>";


require 'inc/footer.inc';



