<?php
//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (isset($argv[1]) and trim($argv[1]) != '') {

  // otetaan tietokanta connect
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  // hmm.. jännää
  $kukarow['yhtio'] = $argv[1];

  $query    = "SELECT * from yhtio where yhtio='$kukarow[yhtio]'";
  $yhtiores = pupe_query($query);

  if (mysql_num_rows($yhtiores) == 1) {
    $yhtiorow = mysql_fetch_array($yhtiores);

    // haetaan yhtiön parametrit
    $query = "SELECT *
              FROM yhtion_parametrit
              WHERE yhtio='$yhtiorow[yhtio]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $yhtion_parametritrow = mysql_fetch_array($result);
      // lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
      foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
        $yhtiorow[$parametrit_nimi] = $parametrit_arvo;
      }
    }
  }
  else {
    die ("Yhtiö $kukarow[yhtio] ei löydy!");
  }

  $query = "(SELECT tilausrivi.otunnus, lasku.laatija, concat('vastuuostaja#', kuka.eposti) eposti, lasku.nimi, lasku.ytunnus, COUNT(*) kpl
             FROM tilausrivi
             JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'O' and lasku.alatila != '' AND lasku.h1time < SUBDATE(CURDATE(), INTERVAL 5 DAY))
             JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ostajanro > 0)
             JOIN kuka  ON (kuka.yhtio = tuote.yhtio AND kuka.myyja = tuote.ostajanro AND kuka.eposti != '')
             WHERE lasku.yhtio          = '$kukarow[yhtio]'
             AND tilausrivi.toimitettu  = ''
             AND tilausrivi.tyyppi      = 'O'
             AND tilausrivi.kpl         = 0
             and tilausrivi.varattu    != 0
             AND tilausrivi.jaksotettu  = 0
             GROUP BY 1,2,3,4,5)
             UNION
             (SELECT tilausrivi.otunnus, lasku.laatija, concat('ostaja#', kuka.eposti) eposti, lasku.nimi, lasku.ytunnus, COUNT(*) kpl
             FROM tilausrivi
             JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'O' and lasku.alatila != '' AND lasku.h1time < SUBDATE(CURDATE(), INTERVAL 5 DAY))
             JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
             JOIN kuka ON (kuka.yhtio = lasku.yhtio and kuka.kuka = lasku.laatija AND kuka.eposti != '')
             WHERE lasku.yhtio          = '$kukarow[yhtio]'
             AND tilausrivi.toimitettu  = ''
             AND tilausrivi.tyyppi      = 'O'
             AND tilausrivi.kpl         = 0
             and tilausrivi.varattu    != 0
             AND tilausrivi.jaksotettu  = 0
             GROUP BY 1,2,3,4,5)
             ORDER BY eposti, otunnus";
  $result = pupe_query($query);

  $veposti = "";
  $meili   = "";

  do {
    $trow = mysql_fetch_assoc($result);

    if ($trow['eposti'] != $veposti and $veposti != "") {

      list($postityyppi, $l_veposti) = explode("#", $veposti);

      if ($postityyppi == 'vastuuostaja') {
        $meili = t("Vastuuostajalle ilmoitus vahvistamatta olevista ostotilauksien riveistä").":\n\n" . $meili;
      }
      else {
        $meili = t("Sinulla on vahvistamatta seuraavien ostotilauksien rivit").":\n\n" . $meili;
      }

      $tulos = mail($l_veposti, mb_encode_mimeheader(t("Muistutus vahvistamattomista ostotilausriveistä"), "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
      $meili = "";
    }

    $meili .= t("Ostotilaus").": " . $trow['otunnus'] . "\n";
    $meili .= t("Toimittaja").": " . $trow['nimi'] . "\n";
    $meili .= t("Vahvistamattomia rivejä").": " . $trow['kpl'] . "\n\n";

    $veposti = $trow['eposti'];

  } while ($trow);
}
