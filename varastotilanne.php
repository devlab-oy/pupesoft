<?php

require 'inc/edifact_functions.inc';

if (isset($_POST['task']) and $_POST['task'] == 'nayta_varastoraportti') {

  $varastot = unserialize(base64_decode($_POST['varastot']));

  //$varastot['B-4'] = array_pad($varastot['B-4'], 120, $varastot['B-4'][0]);

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

$query = "SELECT ss.*,
          tilausrivin_lisatiedot.asiakkaan_rivinumero,
          ostotilausrivin_lisatiedot.kuljetuksen_rekno,
          IF(ss.lisatieto IS NULL, 'Normaali', ss.lisatieto) AS status,
          lasku.asiakkaan_tilausnumero
          FROM sarjanumeroseuranta AS ss
          JOIN tilausrivi
            ON tilausrivi.yhtio = ss.yhtio
            AND tilausrivi.tunnus = ss.myyntirivitunnus
          JOIN tilausrivin_lisatiedot
            ON tilausrivin_lisatiedot.yhtio = ss.yhtio
            AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
          JOIN tilausrivi AS ostotilausrivi
            ON ostotilausrivi.yhtio = ss.yhtio
            AND ostotilausrivi.tunnus = ss.ostorivitunnus
          JOIN tilausrivin_lisatiedot AS ostotilausrivin_lisatiedot
            ON ostotilausrivin_lisatiedot.yhtio = ss.yhtio
            AND ostotilausrivin_lisatiedot.tilausrivitunnus = ostotilausrivi.tunnus
          JOIN lasku
            ON lasku.yhtio = ss.yhtio
            AND lasku.tunnus = tilausrivi.otunnus
          JOIN laskun_lisatiedot
            ON laskun_lisatiedot.yhtio = ss.yhtio
            AND laskun_lisatiedot.otunnus = lasku.tunnus
          WHERE ss.yhtio = '{$kukarow['yhtio']}'
          AND (ss.lisatieto != 'Toimitettu' OR ss.lisatieto IS NULL)";
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
      $kombo = $rulla['asiakkaan_tilausnumero'] . ":" . $rulla['asiakkaan_rivinumero'];
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

  echo "
  <form method='post' id='nayta_varastoraportti'>
  <input type='hidden' name='varastot' value='{$varastot}' />
  <input type='hidden' name='task' value='nayta_varastoraportti' />
  <input type='hidden' name='session' value='{$session}' />
  <input type='hidden' name='logo_url' value='{$logo_url}' />
  <input type='hidden' name='tee' value='XXX' />
  </form>
  <button onClick=\"js_openFormInNewWindow('nayta_varastoraportti', 'Varastoraportti'); return false;\" />";

  echo t("Luo pdf");
  echo "</button></div>";


}

require "inc/footer.inc";
