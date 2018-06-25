<?php


if (!function_exists("tulosta_lavakeraystarrat_tec")) {
  function tulosta_lavakeraystarrat_tec($riresult, $rivinumerot, $komento, $from="") {
    global $kukarow;

    mysql_data_seek($riresult, 0);

    $rivit = array();
    $tilaukset = array();
    $asiakkat = array();
    $tuotteet = array();
    $lava_referenssiluku = 0;
    $lavanumero = 1;
    $ed_asiakas = "";

    while ($tilrow = mysql_fetch_assoc($riresult)) {

      if ($lava_referenssiluku >= lavakerayskapasiteetti) {
        $lavanumero++;
        $lava_referenssiluku=0;
      }

      // Lavakäsittely
      $lava_referenssiluku += ($tilrow["tilkpl"] * $tilrow['lavakoko']);

      $tilrow['lavanumero'] = $lavanumero;
      $tilrow['lava_referenssiluku'] = $lava_referenssiluku;

      $rivit[] = $tilrow;
    }

    // Rivit käänteiseen järjestykseen
    krsort($rivit);

    $tarrat = "
*** ETIKETIN KOKO ***
{D0500,0900,0450|}
{AX;+000,+000,+00|}
{AY;+05,0|}
{C|}
";

    foreach ($rivit as $tilrow) {

      if ($from == "KOPIOTULOSTUS" and empty($rivinumerot[$tilrow['tunnus']])) {
        continue;
      }

      if (!isset($tilaukset[$tilrow['otunnus']])) {
        $query  = "SELECT *
                   FROM lasku
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus = '{$tilrow['otunnus']}'";
        $lasres = pupe_query($query);
        $lasrow = mysql_fetch_assoc($lasres);

        $tilaukset[$tilrow['otunnus']] = $lasrow;
      }

      $lasrow = $tilaukset[$tilrow['otunnus']];

      if (!isset($tilaukset[$lasrow['liitostunnus']])) {
        $query  = "SELECT *
                   FROM asiakas
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tunnus = '{$lasrow['liitostunnus']}'";
        $asres = pupe_query($query);
        $asrow = mysql_fetch_assoc($asres);

        $asiakkat[$lasrow['liitostunnus']] = $asrow;
      }

      $asrow = $asiakkat[$lasrow['liitostunnus']];

      if (!isset($tuotteet[$tilrow['tuoteno']])) {
        $query  = "SELECT *
                   FROM tuote
                   WHERE yhtio = '{$kukarow['yhtio']}'
                   AND tuoteno = '{$tilrow['tuoteno']}'";
        $tuores = pupe_query($query);
        $tuorow = mysql_fetch_assoc($tuores);

        $tuotteet[$tilrow['tuoteno']] = $tuorow;
      }

      $tuorow = $tuotteet[$tilrow['tuoteno']];

      $pvm = date("d.m.Y");

      $viivakoodi1  = ">6";
      $viivakoodi1 .= substr($lasrow['asiakkaan_tilausnumero'], 0, 1);
      $viivakoodi1 .= ">5";
      $viivakoodi1 .= substr($lasrow['asiakkaan_tilausnumero'], 1);

      // tässä tehdään aineisto jota TEC tulostin tajuaa
      $tarra = "
*** TARRA ALKAA ***
{PC000;0020,0050,10,10,H,00,B|}
{RC000;10622900 {$lasrow['yhtio_nimi']}|}
{PC001;0890,0050,08,08,H,00,B,P3|}
{RC001;$pvm|}
{PC002;0020,0190,30,30,K,00,B|}
{RC002;{$asrow['toim_nimitark']}|}
{PC003;0300,0190,08,08,I,00,B|}
{RC003;{$asrow['toim_nimi']}|}
{PC004;0890,0080,08,08,H,00,B,P3|}
{RC004;{$lasrow['toimitustapa']}|}
{XB00;0020,0250,A,3,04,0,0150,+0000000000,000,1,00|}
{RB00;{$viivakoodi1}|}
{PC005;0400,0270,10,10,J,00,B|}
{RC005;{$tilrow['tuoteno']}|}
{PC006;0400,0310,10,10,I,00,B,P5050004002|}
{RC006;{$tilrow['nimitys']}|}
{PC007;0400,0405,10,10,J,00,B|}
{RC007;{$tilrow['hyllyalue']} - {$tilrow['hyllynro']} - {$tilrow['hyllyvali']} - {$tilrow['hyllytaso']}|}
{PC008;0400,0455,10,10,J,00,B|}
{RC008;{$rivinumerot[$tilrow['tunnus']]} / {$tilrow['lavanumero']}|}
*** TULOSTUS  ***
{XS;I,0001,0002C3200|}
";

      $_mt400_me1 = ($tuorow['mallitarkenne'] == '400' and $tuorow['myynti_era'] == '1');

      if (!empty($ed_lavanumero) and $tilrow['lavanumero'] != $ed_lavanumero and $from == "") {
        $tarrat .= "
*** TARRA ALKAA ***
{C|}
{XR;0000,0000,0900,0450,A|}
{PC000;0020,0050,10,10,H,00,B|}
{RC000;10622900 {$lasrow['yhtio_nimi']}|}
{PC001;0020,0200,30,30,K,00,B|}
{RC001;LAVA: $ed_lavanumero|}
{PC002;0020,0330,15,15,J,00,B|}
{RC002;{$ed_toimtapa}|}
*** TULOSTUS  ***
{XS;I,0001,0002C3200|}";

        // tulostetaan muutama tyhjä ennen kuin
        // pyydetään vaihtamaan lavaa
        $tarrat .= "
*** TARRA ALKAA ***
{C|}
{XR;0000,0000,0900,0450,A|}
{PC000;0300,0190,10,10,I,00,B|}
*** TULOSTUS  ***
{XS;I,0001,0002C3200|}";

        $tarrat .= "
*** TARRA ALKAA ***
{C|}
{XR;0000,0000,0900,0450,A|}
{PC000;0300,0190,10,10,I,00,B|}
*** TULOSTUS  ***
{XS;I,0001,0002C3200|}
{XJ;VAIHDA LAVA|}
";
      }
      elseif ($_mt400_me1 and !empty($ed_asiakas) and $lasrow['liitostunnus'] != $ed_asiakas and $from == "") {

        // jos mallitarkenne 400 ja myyntierä 1 ja jos asiakas vaihtuu,
        // niin tulostetaan tyhjä tarra
        $tarrat .= "
*** TARRA ALKAA ***
{C|}
{XR;0000,0000,0900,0450,A|}
{PC000;0300,0190,10,10,I,00,B|}
{RC000;ASIAKAS VAIHTUU|}
*** TULOSTUS  ***
{XS;I,0001,0002C3200|}";
      }

      $tulkpl = 1;

      // Tulostetaan yksi tarra per myyntiera
      if ($tuorow['myynti_era'] > 0 and $tilrow['tilkpl'] > 0) {
        $tulkpl = ceil($tilrow['tilkpl'] / $tuorow['myynti_era']);
      }

      for ($i=1; $i<=$tulkpl; $i++) {
        $tarrat .= $tarra;
      }

      $ed_lavanumero = $tilrow['lavanumero'];
      $ed_toimtapa = $lasrow['toimitustapa'];
      $ed_asiakas = $lasrow['liitostunnus'];
    }

    // Ekan lavan tiedot
    if ($from == "") {
      $tarrat .= "
*** TARRA ALKAA ***
{C|}
{XR;0000,0000,0900,0450,A|}
{PC000;0020,0050,10,10,H,00,B|}
{RC000;10622900 {$lasrow['yhtio_nimi']}|}
{PC001;0020,0200,30,30,K,00,B|}
{RC001;LAVA: $ed_lavanumero|}
{PC002;0020,0330,15,15,J,00,B|}
{RC002;{$ed_toimtapa}|}
*** TULOSTUS  ***
{XS;I,0001,0002C3200|}
";

      // tulostetaan muutama tyhjä ennen kuin
      // pyydetään vaihtamaan lavaa
      $tarrat .= "
*** TARRA ALKAA ***
{C|}
{XR;0000,0000,0900,0450,A|}
{PC000;0300,0190,10,10,I,00,B|}
*** TULOSTUS  ***
{XS;I,0001,0002C3200|}";

      $tarrat .= "
*** TARRA ALKAA ***
{C|}
{XR;0000,0000,0900,0450,A|}
{PC000;0300,0190,10,10,I,00,B|}
*** TULOSTUS  ***
{XS;I,0001,0002C3200|}
";
    }

    //konvertoidaan ääkköset printterin ymmärtämään muotoon
    $from = array ('ä', 'å', 'ö', 'Ä', 'Å', 'Ö', '`');
    $to   = array (chr(132), chr(134), chr(148), chr(142), chr(143), chr(153)); // DOS charset
    $tarrat = str_replace($from, $to, $tarrat); // Tehdään käännös

    $tarrat .= chr(12);

    // Kirjoitetaan temppifileen
    $filenimi = "/tmp/lavatarrat_".md5(uniqid(mt_rand(), true)).".txt";

    file_put_contents($filenimi, $tarrat);

    $line = exec("$komento $filenimi");
    system("rm -f $filenimi");

    echo t("Lavakeräystarrat tulostuu")."...<br>";
    #echo "<pre>$tarrat</pre><br><br>";
  }
}
