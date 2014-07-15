<?php

$ulos = "";
$jatko = 1; // oletetaan normaali käsittely
$tyyppi = 1; // oletetaan rivin näkyvyys

if (mysql_field_name($result, $i) == "tunnus" or mysql_field_name($result, $i) == "yhtio") {
  $tyyppi = 0;
}

if (mysql_field_type($result, $i) == "blob") {
  $ulos = "<td><textarea cols='50' rows='5' name='$nimi'>$trow[$i]</textarea></td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "tuoteno") {
  $lopetus_temp = base64_encode(urlencode("{$palvelin2}asiakkaan_laite_hallinta.php////tee=hae_asiakas//asiakas_tunnus={$valittu_asiakas}"));
  $lopetus = "yllapito.php////toim=laite//uusi=1//valittu_paikka={$valittu_paikka}//lopetus_temp={$lopetus_temp}";
  $ulos = "<td id='tuoteno_td'>";
  $ulos .= livesearch_kentta('uusi_laite_form', 'TUOTEHAKU', $nimi, '226', $trow[$i], 'NOSUBMIT');
  $ulos .= "<a href='{$palvelin2}yllapito.php?valittu_asiakas={$valittu_asiakas}&uusi=1&toim=tuote!!!SAMMUTINHUOLTO!!!true&pikaperustus=1&lopetus={$lopetus}&js_open_yp=&limit=&nayta_poistetut=&nayta_eraantyneet=&laji=&'><button type='button'>" . t('Tuote pikaperustus') . "</button></a>";
  $ulos .= "</td>";
  $jatko = 0;
}

if (mysql_field_name($result, $i) == "paikka") {
  $join = "";
  if (!empty($asiakas_tunnus)) {
    $join = " JOIN kohde
              ON ( kohde.yhtio = paikka.yhtio
                AND kohde.tunnus = paikka.kohde )
              JOIN asiakas
              ON ( asiakas.yhtio = kohde.yhtio
                AND asiakas.tunnus = kohde.asiakas
                AND asiakas.tunnus = '{$asiakas_tunnus}')";
  }

  $query = "SELECT paikka.tunnus,
            concat_ws(' ', paikka.nimi, paikka.osoite, avainsana.selitetark) as dropdown_text
            FROM paikka
            JOIN avainsana
            ON ( avainsana.yhtio = paikka.yhtio
              AND avainsana.selite = paikka.olosuhde
              AND avainsana.laji = 'OLOSUHDE' )
            {$join}
            WHERE paikka.yhtio = '{$kukarow['yhtio']}'";
  $paikka_result = pupe_query($query);
  $paikat = array();
  while ($paikka = mysql_fetch_assoc($paikka_result)) {
    if (empty($valittu_paikka)) {
      if ($trow[$i] == $paikka['tunnus']) {
        $paikka['selected'] = "SELECTED";
      }
      else {
        $paikka['selected'] = "";
      }
    }
    else {
      //jos valittu_paikka setattu ollaan tulossa asikkaan_laite_hallinnasta
      if ($valittu_paikka == $paikka['tunnus']) {
        $paikka['selected'] = "SELECTED";
      }
      else {
        $paikka['selected'] = "";
      }
    }
    $paikat[] = $paikka;
  }

  $ulos = "<td>";
  $ulos .= "<select name='{$nimi}'>";
  foreach ($paikat as $paikka) {
    $ulos .= "<option {$paikka['selected']} value='{$paikka['tunnus']}'>{$paikka['dropdown_text']}</option>";
  }
  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
}

//TODO sarjanro. laitteeseen isketty sarjanumero. valmistenumero toisinsanoen. tämä pitää syöttää käsin
if (mysql_field_name($result, $i) == "sarjanro") {
  if (!empty($kopioi_rivi)) {
    $trow['tila'] = "";
    $trow[$i] = "";
  }
}

//TODO oma_numero. kohteen omistajan antana numero. esim tussilla vedetty kylkeen. tämä pitää syöttää käsin
if (mysql_field_name($result, $i) == "oma_numero") {

}

//Jos omistaja kentässä on jotain niin kyseiselle laitteelle ei generoida työmääräyksiä. Laitteet, jotka eivät kuulu huollon piiriin halutaan kuitenkin lisätä asiakkaan laitepuuhun, jotta huoltomies ei ihmettele ylimääräisiä laitteita huoltokierroksellaan.
if (mysql_field_name($result, $i) == "omistaja") {
  $ulos = "<tr>";
  $ulos .= "<th align='left'>";
  $ulos .= t('Muu omistaja');
  $ulos .= "</th>";
  $ulos .= "<td>";
  $ulos .= "<input type='text' class='omistaja' name='t[{$i}]' value='{$trow[$i]}' size='35' maxlength='60' />";
  $ulos .= "</td>";
  $ulos .= "</tr>";

  $jatko = 0;
  $tyyppi = 0;
}

if (mysql_field_name($result, $i) == "valm_pvm") {
  $tyyppi = 1.7;
}

if (mysql_field_name($result, $i) == "tila") {
  $query = "SELECT *
            FROM avainsana
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND laji = 'LAITE_TILA'
            ORDER BY jarjestys ASC";
  $tuote_tila_result = pupe_query($query);
  $tuote_tilat = array();
  while ($tuote_tila = mysql_fetch_assoc($tuote_tila_result)) {
    $tuote_tilat[] = $tuote_tila;
  }

  $ulos = "<td>";
  $ulos .= "<select name='{$nimi}'>";
  foreach ($tuote_tilat as $tila) {
    $sel = "";
    if ($tila['selite'] == $trow['tila']) {
      $sel = "SELECTED";
    }
    $ulos .= "<option {$sel} value='{$tila['selite']}'>{$tila['selitetark']}</option>";
  }
  $ulos .= "</select>";
  $ulos .= "</td>";

  $jatko = 0;
}

if (mysql_field_name($result, $i) == "koodi") {
  $tyyppi = 0;
}

if (!isset($redirect_echoed)) {
  $redirect_echoed = 1;
  if (!empty($valittu_paikka)) {
    //redirektiin tarvitaan valittu_asiakas joten haetaan paikan kohde, jolla on asiakas asiakas
    $query = "SELECT asiakas.tunnus
              FROM asiakas
              JOIN kohde
              ON ( kohde.yhtio = asiakas.yhtio
                AND kohde.asiakas = asiakas.tunnus)
              JOIN paikka
              ON ( paikka.yhtio = kohde.yhtio
                AND paikka.kohde = kohde.tunnus
                AND paikka.tunnus = '{$valittu_paikka}')
              WHERE asiakas.yhtio = '{$kukarow['yhtio']}'";
    $asiakas_result = pupe_query($query);
    $asiakas_row = mysql_fetch_assoc($asiakas_result);
    $valittu_asiakas = $asiakas_row['tunnus'];
    echo "<input type='hidden' name='redirect_to' value='asiakkaan_laite_hallinta.php' />";
    echo "<input type='hidden' name='valittu_asiakas' value='{$valittu_asiakas}' />";
  }
  else {
    echo "<input type='hidden' name='redirect_to_laite_hallinta' value='0' />";
  }
}
