<?php

/**
 *  Extranetin saldokysely
 *  Lähteenä URL
 *
 *
 */


if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

// argv = yhtio
if (trim($argv[1]) != '') {

  // otetaan tietokanta connect
  require "connect.inc";
  require "functions.inc";

  // alustetaan muuttujat
  $kukarow['yhtio'] = mysql_real_escape_string($argv[1]);
  $kukarow['kuka'] = "admin";

  // urli mistä saldot haetaan
  $url = "http://orumnet.orum.fi/cgi-bin/wspd_cgi.sh/WService=weborum/tools/ws/whqty/vannesaldo.r?key=St544dgDamIf3a";
  $now = date("Y-m-d H:i:s");

  $file = file_get_contents($url);

  foreach (explode(PHP_EOL, $file) as $tuote) {
    list($tuoteno, $saldo) = explode("\t", $tuote);

    $tuoteno = mysql_real_escape_string($tuoteno);
    $saldo = (float) $saldo;

    // otetaan vain yksi tuotepaikka ja päivitetään sille saldo
    // muut tuotteen tuotepaikat nollataan lopussa
    $query = "SELECT hyllyalue, hyllynro, hyllytaso, hyllyvali
              FROM tuotepaikat
              WHERE yhtio    = '{$kukarow['yhtio']}'
              AND tuoteno    = '$tuoteno'
              AND hyllyalue != ''
              AND hyllynro  != ''
              AND hyllytaso != ''
              AND hyllyvali != ''";
    $tuotepaikka_result = mysql_query($query) or die("\nTuotepaikan haku epaonnistui:\n$query\n");
    $tuotepaikka_row = mysql_fetch_assoc($tuotepaikka_result);

    // päivitetään tuotepaikkojen saldot
    $query = "UPDATE tuotepaikat SET
              saldo         = '$saldo',
              muuttaja      = '{$kukarow['kuka']}',
              muutospvm     = '$now'
              WHERE yhtio   = '{$kukarow['yhtio']}'
              AND tuoteno   = '$tuoteno'
              AND hyllyalue = '$tuotepaikka_row[hyllyalue]'
              AND hyllynro  = '$tuotepaikka_row[hyllynro]'
              AND hyllytaso = '$tuotepaikka_row[hyllytaso]'
              AND hyllyvali = '$tuotepaikka_row[hyllyvali]'";
    $result = mysql_query($query) or die("\nTuotepaikkojen saldojen paivitys epaonnistui:\n$query\n");

    // haetaan kaikki tulostusjonossa olevat tilaukset
    $query = "SELECT tunnus
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tila    = 'N'
              AND alatila = 'A'";
    $result = mysql_query($query) or die("\nLaskujen haku epaonnistui:\n$query\n");

    // päivitetään laskun tila L:ksi ja alatila X:ksi (= laskutettu), että niitä ei voida enään muokata
    // päivitetään varattu kpl-kenttään ja nollataan varattu-kenttä
    while ($row = mysql_fetch_assoc($result)) {
      $query = "UPDATE lasku SET
                tila        = 'L',
                alatila     = 'X'
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$row['tunnus']}'";
      $res = mysql_query($query) or die("\nTila L alatila X paivitys epaonnistui:\n$query\n");

      $query = "UPDATE tilausrivi SET
                kpl         = varattu,
                varattu     = 0
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$row['tunnus']}'";
      $res = mysql_query($query) or die("\nKpl = varattu paivitys epaonnistui:\n$query\n");
    }

    // haetaan kaikki asiakkaat ja päivitetään niiden email-osoitteet myynti@orum.fi
    $query = "SELECT tunnus
              FROM asiakas
              WHERE yhtio  = '{$kukarow['yhtio']}'
              AND email   != 'vannemyynti@orum.fi'";
    $result = mysql_query($query) or die("\nAsiakkaiden haku epaonnistui:\n$query\n");

    // päivitetään asiakkaiden sähköpostiosoitteet
    while ($row = mysql_fetch_assoc($result)) {
      $query = "UPDATE asiakas SET
                email           = 'vannemyynti@orum.fi',
                tilausvahvistus = '1SP'
                WHERE yhtio     = '{$kukarow['yhtio']}'
                AND tunnus      = '{$row['tunnus']}'";
      $res = mysql_query($query) or die("\nAsiakkaan $row[tunnus] emailin paivitys epaonnistui:\n$query\n");
    }
  }

  // Tyhjennetään tuotepaikkojen saldot
  $query = "UPDATE tuotepaikat SET
            saldo          = 0,
            muuttaja       = '{$kukarow['kuka']}',
            muutospvm      = '$now'
            WHERE yhtio    = '{$kukarow['yhtio']}'
            AND muutospvm != '$now'";
  $result = mysql_query($query) or die("\nTuotepaikkojen saldojen tyhjennys epaonnistui:\n$query\n");
}
