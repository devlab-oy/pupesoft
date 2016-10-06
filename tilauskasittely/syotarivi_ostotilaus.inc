<?php

if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
  $_laa_src = "{$palvelin2}pics/facelift/nuolet_alas.png";
  $_sup_src = "{$palvelin2}pics/facelift/nuolet_ylos.png";
  $_kuv_sty = "height:20px;";
}
else {
  $_laa_src = "{$palvelin2}pics/lullacons/arrow-single-down-green.png";
  $_sup_src = "{$palvelin2}pics/lullacons/arrow-single-up-green.png";
  $_kuv_sty = "height:15px;";
}

$hiddenlisa = "";
$hiddenimg = $_sup_src;

if (empty($_COOKIE["syota_osto_laajat"])) {
  $hiddenlisa = " style='display: none;'";
  $hiddenimg = $_laa_src;
}

echo "
  <script>
      $(document).ready(function(){
        $('#syota_osto_laajat').click(function(){
           if ($('.osto_laaja').is(':visible')) {
             $('.osto_laaja').hide();
             $('#syota_osto_laajat').attr('src', '$_laa_src');
             document.cookie = \"syota_osto_laajat=;7\";
           }
           else {
             $('.osto_laaja').show();
             $('#syota_osto_laajat').attr('src', '$_sup_src');
             document.cookie = \"syota_osto_laajat=show;7\";
           }
        });
      });
      </script>";

// Syötetään rivi
echo "  <tr>
    <form name='rivi' action = '$PHP_SELF#$rivitunnus' method='post' autocomplete='off'>
    <input type='hidden' name='toim' value = '$toim'>
    <input type='hidden' name='lopetus' value = '$lopetus'>
    <input type='hidden' name='tilausnumero' value = '$tilausnumero'>
    <input type='hidden' name='toim_nimitykset' value = '$toim_nimitykset'>
    <input type='hidden' name='toim_tuoteno' value = '$toim_tuoteno'>
    <input type='hidden' name='naytetaankolukitut' value = '$naytetaankolukitut'>
    <input type='hidden' name='toiminto' value = '$toiminto'>
    <input type='hidden' name='tee' value = 'TI'>
    <input type='hidden' name='rivitunnus' value = '$rivitunnus'>
    <input type='hidden' name='hinta_alkuperainen' value = '{$hinta_alkuperainen}'>
    <input type='hidden' name='perheid2' value = '$perheid2'>
    <input type='hidden' name='muuta' value = '$muuta'>
    <input type='hidden' name='otunnus' value = '$otunnus'>
    <input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
    <input type='hidden' name='rivinotunnus' value = '$rivinotunnus'>
    <input type='hidden' name='osto_sarjatunnus' value = '$osto_sarjatunnus'>
    <input type='hidden' name='rivinvar' value = '$var'>
    <input type='hidden' name='lasku' value = '$lasku'>
    <input type='hidden' name='toimittajaid' value = '$toimittajaid'>";

$variaaritorivi = FALSE;

$_ocolspan = 5 + $yhtiorow['oston_alekentat'];

$suoratoimitusekko = "";

if ($rivitunnus > 0) {
  //Tutkitaan löytyykö JT-rivi joka mäppäytyy tähän ostoriviin
  $query = "SELECT tilausrivi.tunnus, tilausrivi.otunnus, lasku.nimi, tilausrivin_lisatiedot.suoraan_laskutukseen
            FROM tilausrivin_lisatiedot
            JOIN tilausrivi ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and (tilausrivi.varattu+tilausrivi.jt) > 0 and tilausrivi.laskutettuaika = '0000-00-00')
            JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
            WHERE tilausrivin_lisatiedot.yhtio          = '$kukarow[yhtio]'
            and tilausrivin_lisatiedot.tilausrivilinkki = '$rivitunnus'";
  $varastoon_result = pupe_query($query);

  if ($varastoon_row = mysql_fetch_assoc($varastoon_result)) {
    echo "<input type='hidden' name='suoratoimitus_rivitun' value='$varastoon_row[tunnus]'>";
    echo "<input type='hidden' name='suoratoimitus_rivikpl' value='$kpl'>";

    if ($varastoon_row["suoraan_laskutukseen"] != "") {
      $suoratoimitusekko = "<tr><th colspan='$_ocolspan'>".t("HUOM").":</td></tr><tr><td colspan='$_ocolspan'>".t("Suoratoimitus asiakkaalle").": $varastoon_row[nimi], ".t("Tilaus").": $varastoon_row[otunnus]. Ostorivin muutokset tehdään myös myyntiriville!</td></tr>";
    }
    else {
      $suoratoimitusekko = "<tr><th colspan='$_ocolspan'>".t("HUOM").":</td></tr><tr><td colspan='$_ocolspan'>".t("Tilattu asiakkaalle").": $varastoon_row[nimi], ".t("Tilaus").": $varastoon_row[otunnus]. Ostorivin muutokset tehdään myös myyntiriville!</td></tr>";
    }
  }
}

if ($yhtiorow['variaatiomyynti'] == "K" and $kukarow["extranet"] == '' and (is_array($tuoteno_array) or $tuoteno != "" or $variaatio_tuoteno) and $ulos == "" and $rivitunnus == "") {
  $variaaritorivi_osto = TRUE;

  require "syota_variaatiorivi.inc";
}

if ($variaaritorivi === FALSE) {
  // Annetaan mahdollisuus tehdä uusi tilausrivi tai näytetään virhe
  echo "<table>
      $suoratoimitusekko
      <tr>
      <th>".t("Tuoteno");

  echo "<div style='float:right;'><img id='syota_osto_laajat' style='{$_kuv_sty}' src='{$hiddenimg}'></div>";

  echo "</th>
      <th>".t("Määrä")."</th>
      <th>".t("Hinta")."</th>";

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    echo "<th>", t("Ale"), "{$alepostfix}</th>";
  }

  echo "<th>".t("Alv")."</th>
      <th>".t("Viestit")."</th>
      <td class='back'></td>
      </tr>";

  if ($ulos != '') {
    echo "<td>$ulos</td>";
  }
  else {
    if (in_array($yhtiorow["livetuotehaku_tilauksella"], array("J", "K")) and $tuoteno == "") {

      $nosubmitti = "";

      if (strpos($_SERVER['SCRIPT_NAME'], "keikka.php")  !== FALSE) {
        $nosubmitti = "EISUBMIT";
      }

      echo "<td valign='top'>".livesearch_kentta("rivi", "TUOTEHAKU", "tuoteno", 200, '', $nosubmitti)."</td>";
    }
    else {
      echo "<td><input type='text' size='18' name='tuoteno' value = '$tuoteno'></td>";
    }
  }

  echo "<td><input type='text' size='7' name='kpl' value = '$kpl'></td>
        <td><input type='text' size='7' name='hinta' value = '$hinta'></td>";

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['oston_alekentat']; $alepostfix++) {
    echo "<td><input type='text' size='5' name='ale{$alepostfix}' value = '", ${'ale'.$alepostfix}, "'></td>";
  }

  if ($alv=='') $alv=$laskurow['alv'];

  echo "<td>".alv_popup_oletus('alv', $alv)."</td><td>";

  if ($automatiikka == "ON" and $onjodropdowni != 'ON' and $laskurow['tila'] != 'O') {
    echo "  ".t("Valtse toiminto").": <select name='var'>";
    echo "<option value='LOJ#!¡!#$kpl'>".t("Lisää syötetyt ja merkkaa erotus JT:ksi")."</option>";
    echo "<option value=''>".t("Päivitä rivin määrä")."</option>";
    echo "</select>";
  }

  // Ostohinnan päivityskysely
  if ($paiv == 'X') {
    echo "<input type='radio' name='paiv' value='K'>".t("Päivitä")."<br>";
    echo "<input type='radio' name='paiv' value='E' CHECKED>".t("Älä Päivitä")."<br>";
  }

  echo "$varaosavirhe</td>";
  echo "<td class='back'>
        <input type='hidden' name='rivilaadittu' value='$rivilaadittu'>
        <input type='submit' class='lisaa_btn' name= 'kumpi' value = '".t("Lisää")."'>
        <input type='submit' class='poista_btn' name= 'tyhjenna' value = '".t("Tyhjennä")."'></td>";

  //näytetään poista nappi vain kun ollaan navigoitu ostotilaus riville saapumisen kohdista rivin kautta ja kun tilausrivi on esitäytetty
  if ($saapuminen_ostorivin_muokkaus == 1 and !empty($rivitunnus)) {
    echo "<td class='back'><input type='submit' class='poista_btn' name='poista' value='".t("Poista")."'/></td>";
  }

  echo "</tr>";

  if ($toim == "HAAMU" or $tuoteno == "99" or $yhtiorow["nimityksen_muutos_tilauksella"] == "Y") {
    echo "<tr class='osto_laaja' $hiddenlisa><th align='left' colspan='$_ocolspan'>".t("Nimitys")."</th></tr>
        <tr class='osto_laaja' $hiddenlisa><td align='left' colspan='$_ocolspan'><input type = 'text' name='tuotenimitys' value='$tuotenimitys' size = '70'></td></tr>";
  }

  echo "<tr class='osto_laaja' $hiddenlisa>
      <th align='left'>".t("Toimitusaika")."</th>";

  echo "<th colspan='".($_ocolspan-1)."' align='left'>".t("Kommentti")."</th>";

  echo "<td class='back'></td></tr>";

  if ($toimaika == "" or $toimaika == "0000-00-00") {
    $toimaika = $laskurow["toimaika"];
  }

  if ($toimaika == "" or $toimaika == "0000-00-00") {
    $toimkka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    $toimvva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    $toimppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
  }
  else {
    $toimkka = substr($toimaika, 5, 2);
    $toimvva = substr($toimaika, 0, 4);
    $toimppa = substr($toimaika, 8, 2);
  }

  echo "<tr class='osto_laaja' $hiddenlisa>
      <td><input type='text' size='3' name='toimppa' value='$toimppa'><input type='text' size='3' name='toimkka' value='$toimkka'><input type='text' size='5' name='toimvva' value='$toimvva'></td>
      <td colspan='".($_ocolspan-1)."'><textarea name='kommentti' cols='50'>$kommentti</textarea></td>";

  echo "</form></table>";
  echo "<br>";
}

// Kursorinohjausta
if ($formi == '') {
  $formi = 'rivi';
}

if ($kentta == '') {
  $kentta = 'tuoteno';
}
