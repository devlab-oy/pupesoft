<?php


require "../inc/parametrit.inc";
require "../inc/edifact_functions.inc";


if ($task == 'matkakoodipaivitys') {

  $query = "SELECT tunnus, asiakkaan_tilausnumero, asiakkaan_rivinumero FROM tilausrivin_lisatiedot WHERE matkakoodi = ''";
  $result = pupe_query($query);

  while ($rivi = mysql_fetch_assoc($result)) {

    $numero_rivi = $rivi['asiakkaan_tilausnumero'] . ':' . $rivi['asiakkaan_rivinumero'];

    $query = "SELECT data FROM liitetiedostot WHERE selite = '{$numero_rivi}' AND kayttotarkoitus = 'bookkaussanoma'";
    $res = pupe_query($query);

    $data = mysql_result($res, 0);
    $datarivit = explode("'", $data);

    foreach ($datarivit as $datarivi) {

      if (substr($datarivi, 0, 8) == 'BGM+335+') {
        $osat = explode("+", $datarivi);
        $matkakoodi = $osat[2];
        break;
      }
    }

    if (!empty($matkakoodi)) {
      $update = "UPDATE tilausrivin_lisatiedot SET matkakoodi = '{$matkakoodi}' WHERE tunnus = '{$rivi['tunnus']}'";
      pupe_query($update);
      echo $matkakoodi . 'päivitetty riville ' . $rivi['tunnus'] . '<hr>';
    }
  }
}


if ($task == 'tullinimikepalautus') {

  $query = "SELECT
            rahtikirja_id,
            asiakkaan_tilausnumero,
            asiakkaan_rivinumero,
            tilausrivin_lisatiedot.tilausrivitunnus AS otr,
            ss.myyntirivitunnus AS mtr
            FROM tilausrivin_lisatiedot
            LEFT JOIN sarjanumeroseuranta AS ss
              ON ss.ostorivitunnus = tilausrivin_lisatiedot.tilausrivitunnus
            WHERE tullinimike = 0
            AND rahtikirja_id != ''
            LIMIT 500";
  $result = pupe_query($query);

  while ($rivi = mysql_fetch_assoc($result)) {

    unset($tullinimike);

    $query = "SELECT data FROM liitetiedostot WHERE kayttotarkoitus = 'rahtikirjasanoma' AND selite = '{$rivi['rahtikirja_id']}'";
    $res = pupe_query($query);
    $data = mysql_result($res, 0);
    $datarivit = explode("'", $data);


    foreach ($datarivit as $datarivi) {

      if (substr($datarivi, 0, 5) == "PIA+1") {
        $osat = explode("+", $datarivi);
        $tullinimike_info = $osat[2];
        $tullinimike_info_osat = explode(":", $tullinimike_info);
        $tullinimike = $tullinimike_info_osat[0];
      }

      if ($datarivi == "RFF+CU:" . $rivi['asiakkaan_tilausnumero'] . ":" . $rivi['asiakkaan_rivinumero']) {

        if (isset($tullinimike)) {
          $update = "UPDATE tilausrivin_lisatiedot SET tullinimike = '{$tullinimike}' WHERE tilausrivitunnus IN ({$rivi['otr']},{$rivi['mtr']})";
          pupe_query($update);
          echo $update;
          echo '<br>';
          break;
        }
      }
    }
  }
}

/*

if ($task == 'input') {

  $sanoma = str_replace("#@#", "'", $sanoma);

  if (strpos($sanoma, "DESADV") == true) {
    kasittele_rahtikirjasanoma($sanoma);
  }
  elseif (strpos($sanoma, "IFTSTA") == true) {
    kasittele_iftsta($sanoma);
  }
  elseif (strpos($sanoma, "IFTMBF") == true) {
    kasittele_bookkaussanoma($sanoma);
  }

}



if ($task == 'nollaa') {

  $taulut = array(
      "tilausrivi",
      "tilausrivin_lisatiedot",
      "lasku",
      "laskun_lisatiedot",
      "sarjanumeroseuranta",
      "liitetiedostot");

  foreach ($taulut as $taulu) {
    $query = "TRUNCATE TABLE {$taulu}";
    pupe_query($query);
  }

}
*/

  echo "
  <font class='head'>".t("Testaus")."</font>


  <br><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='input' />

    <textarea name='sanoma'></textarea>

    <input type='submit' value='".t("Lue sanoma")."'>
  </form>

  <br><br>

  <form action='' method='post'>
    <input type='hidden' name='task' value='nollaa' />
    <input type='submit' value='".t("Nollaa tilanne")."'>
  </form>

  <form action='' method='post'>
    <input type='hidden' name='task' value='tullinimikepalautus' />
    <input type='submit' value='".t("tullinimikkeet")."'>
  </form>

  <form action='' method='post'>
    <input type='hidden' name='task' value='matkakoodipaivitys' />
    <input type='submit' value='".t("matkakoodit")."'>
  </form>

  ";



require "inc/footer.inc";
