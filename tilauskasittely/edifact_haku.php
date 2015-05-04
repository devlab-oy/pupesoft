<?php


require "../inc/parametrit.inc";
require "../inc/edifact_functions.inc";
/*


$query = "SELECT tunnus, asiakkaan_tilausnumero, asiakkaan_rivinumero FROM tilausrivin_lisatiedot";
$result = pupe_query($query);

while ($rivi = mysql_fetch_assoc($result)) {

  $numero_rivi = $rivi['asiakkaan_tilausnumero'] . ':' . $rivi['asiakkaan_rivinumero'];

  $query = "SELECT data FROM liitetiedostot WHERE selite = '{$numero_rivi}'";
  $res = pupe_query($query);

  $data = mysql_result($res, 0);

  $datarivit = explode("'", $data);

  foreach ($datarivit as $datarivi) {

    if (substr($datarivi, 0, 8) == 'BGM+335+') {
      $osat = explode("+", $datarivi);
      $matkakoodi = $osat[2];
    }
  }

  if (!empty($matkakoodi)) {
    $update = "UPDATE tilausrivin_lisatiedot SET matkakoodi = '{$matkakoodi}' WHERE tunnus = '{$rivi['tunnus']}'";
    pupe_query($update);
    echo $matkakoodi . 'päivitetty riville ' . $rivi['tunnus'] . '<hr>';
  }

}

*/





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

/*

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


  ";



require "inc/footer.inc";
