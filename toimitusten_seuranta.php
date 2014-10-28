<?php

require "inc/parametrit.inc";
require 'mobiili/generoi_edifact.inc';

if (isset($task) and $task == 'hae_parametrit') {
  $parametrit = satamavahvistus_parametrit($konttiviite);

  if ($parametrit) {
    $sanoma = laadi_edifact_sanoma($parametrit);
  }
  else{
    echo 'virhe<br>';
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

  echo '<hr>';
}

echo "<font class='head'>".t("Toimitusten seuranta")."</font><br><br>";




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
          count(tilausrivi.tunnus) AS rullat,
          group_concat(if(tilausrivi.var = 'P', 'E', 'K')) AS varastossa,
          group_concat(if(tilausrivi.keratty = '', 'E', 'K')) AS kontissa,
          tilausrivi.toimitettu
          FROM lasku
          JOIN laskun_lisatiedot
            ON laskun_lisatiedot.yhtio = lasku.yhtio
            AND laskun_lisatiedot.otunnus = lasku.tunnus
          LEFT JOIN tilausrivi
            ON tilausrivi.yhtio = lasku.yhtio
            AND tilausrivi.otunnus = lasku.tunnus
            AND tilausrivi.tyyppi = 'L'
          WHERE lasku.yhtio = '{$kukarow['yhtio']}'
          AND lasku.tilaustyyppi = 'N'
          AND laskun_lisatiedot.konttiviite != ''
          GROUP BY lasku.asiakkaan_tilausnumero";

$result = pupe_query($query);


$tilaukset = array();

while ($rivi = mysql_fetch_assoc($result)) {
  $tilaukset[$rivi['asiakkaan_tilausnumero']] = $rivi;
}


echo "<table>";
echo "<tr>";
echo "<th>".t("Tilauskoodi")."</th>";
echo "<th>".t("Matkakoodi")."</th>";
echo "<th>".t("Lähtöpäivä")."</th>";
echo "<th>".t("Konttiviite")."</th>";
echo "<th>".t("Tapahtumat")."</th>";
echo "<th>".t("Rullien määrä")."</th>";
echo "<th class='back'></th>";
echo "</tr>";

foreach ($tilaukset as $key => $tilaus) {

  $kontti_sinetointivalmis = false;
  $kontti_valmis = false;

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

    // katsotaan onko  viety varastoon jotakin
    $varastossa = explode(",", $tilaus['varastossa']);
    $viematta = $viety = 0;

    foreach ($varastossa as $rivi) {
      if ($rivi == 'E') {
        $viematta++;
      }
      else {
        $viety++;
      }
    }

    if ($viematta == 0) {
      $tapahtumat .= "&bull; " .  t("Rullat viety varastoon") . "<br>";

      // onko myös ehditty jo kontittaa jotakin
      $kontissa = explode(",", $tilaus['kontissa']);
      $kontittamatta = $kontitettu = 0;

      foreach ($kontissa as $rivi) {
        if ($rivi == 'E') {
          $kontittamatta++;
        }
        else {
          $kontitettu++;
        }
      }

      if ($kontittamatta == 0) {
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
          $kontti_sinetointivalmis = true;
        }


      }
      elseif ($kontitettu > 0) {
        $tapahtumat .= "&bull; " .  t("Osa rullista kontitettu") . "<br>";
      }

      // onko kontti jo sinetöity
      if ($tilaus['toimitettu'] != '') {
        $tapahtumat .= "&bull; " .  t("Kontti sinetöity") . "<br>";
        $kontti_valmis = true;
      }

    }
    elseif ($viety > 0) {
      $tapahtumat .= "&bull; " .  t("Osa rullista viety varastoon") . "<br>";
    }

  }



  echo "<td valign='top'>";
  echo $tapahtumat;
  echo "</td>";


  echo "<td valign='top' align='center'>";
  echo $rullamaara;
  echo "</td>";

  echo "<td valign='top' class='back'>";

  if ($kontti_sinetointivalmis) {

    echo "
      <form method='post'>
      <input type='hidden' name='konttiviite' value='{$tilaus['konttiviite']}' />
      <input type='hidden' name='task' value='sinetoi' />
      <input type='submit' value='". t("Syötä kontti- ja sinettinumero") ."' />
      </form>";
  }


  if ($kontti_valmis) {

    echo "
      <form method='post'>
      <input type='hidden' name='konttiviite' value='{$tilaus['konttiviite']}' />
      <input type='hidden' name='task' value='hae_parametrit' />
      <input type='submit' value='". t("Lähetä satamavahvistus") ."' />
      </form>";

  }


  echo "</td>";

  echo "</tr>";

}

echo "</table>";

require "inc/footer.inc";
