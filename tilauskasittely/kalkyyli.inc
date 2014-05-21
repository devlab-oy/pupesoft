<?php

$lock_params = array(
    "locktime"    => 600,
  "lockfile"    => "$kukarow[yhtio]-keikka.lock",
  "filecontent" => "$otunnus;{$kukarow['kuka']};".date("Y-m-d H:i:s")
);

pupesoft_flock($lock_params);

$query  = "SELECT *
           FROM lasku
           WHERE tunnus = '$otunnus'
           AND yhtio    = '$kukarow[yhtio]'
           AND tila     = 'K'
           AND alatila  = ''";
$result = pupe_query($query);

if (mysql_num_rows($result) == 1) {
  $laskurow = mysql_fetch_array($result);
}
else {
  echo "<font style='error'>".t("VIRHE: Saapumista ei enää löydy")."!</font>";
  exit;
}

list(,,,$kohok,,,,$lisok,,$sarjanrook,,,,,,$varok) = tsekit($laskurow, 0, 0);

if (($vietiinko_koko_suuntalava == 'joo' or $varok == 1) and $sarjanrook == 1) {

  if ($tee == "varastoon") {
    require ("varastoon.inc");

    // takaisin ihan alkuun
    $toiminto     = "dummy";
    $ytunnus      = "";
    $toimittajaid   = "";
  }
  else {

    if ($tee == "eitullia") {

      if (isset($poikkeavat_tulliprosentit) and is_array($poikkeavat_tulliprosentit)) {
        // poikkeava_tulliprosentti
        paivita_poikkeavat_tulliprosentit($poikkeavat_tulliprosentit, $rivi_oletus_tulliprosentti);
      }

      //merkataan ne rivit joita ei haluta viedä varastoon
      $query = "UPDATE tilausrivi
                SET varastoon = 0
                WHERE uusiotunnus = '$laskurow[tunnus]'
                and yhtio         = '$kukarow[yhtio]'
                and kpl           = 0";
      pupe_query($query);

      if (count($varastoonko) > 0) {
        foreach ($varastoonko as $varas) {
          $query = "UPDATE tilausrivi
                    SET varastoon  = 1
                    WHERE tunnus    = '$varas'
                    and uusiotunnus = '$laskurow[tunnus]'
                    and yhtio       = '$kukarow[yhtio]'
                    and kpl         = 0";
          pupe_query($query);
        }
      }

      if ($yhtiorow["varastoonvientipaiva"] == "K" and count($vva) > 0) {
        foreach ($vva as $varas => $vuosi) {

          $kuukausi = $kka[$varas];
          $paiva    = $ppa[$varas];

          if (!checkdate($kuukausi, $paiva, $vuosi)) {
            echo "<font class='error'>".t("VIRHE: Virheellinen päivämäärä")." $vuosi-$kuukausi-$paiva</font><br>";
          }
          else {
            $query = "UPDATE tilausrivi
                      SET toimitettuaika = '$vuosi-$kuukausi-$paiva 00:00:10',
                      toimitettu      = '$kukarow[kuka]'
                      WHERE tunnus    = '$varas'
                      and uusiotunnus = '$laskurow[tunnus]'
                      and yhtio       = '$kukarow[yhtio]'";
            pupe_query($query);
          }
        }
      }

      $tee = "";
    }

    if ($yhtiorow['suuntalavat'] == 'S' and $tee == '' and trim($suuntalavan_tunnus) != '' and trim($koko_suuntalava) == 'X') {

      if ($komento["Tavaraetiketti"] != '') {

        $suuntalavat = array($suuntalavan_tunnus);

        require('tulosta_tavaraetiketti.inc');
      }

      echo "<br />";
      echo "<form method='post'>";
      echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
      echo "<input type='hidden' name='toiminto' value='kalkyyli'>";
      echo "<input type='hidden' name='tee' value=''>";
      echo "<input type='hidden' name='otunnus' value='$otunnus'>";
      echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavan_tunnus}' />";

      if (trim($suuntalavanhyllypaikka) != '') {
        echo "<input type='hidden' name='suuntalavanhyllypaikka' value='{$suuntalavanhyllypaikka}' />";
      }
      else {
        echo "<input type='hidden' name='suuntalavanhyllyalue' value='{$suuntalavanhyllyalue}' />";
        echo "<input type='hidden' name='suuntalavanhyllynro' value='{$suuntalavanhyllynro}' />";
        echo "<input type='hidden' name='suuntalavanhyllyvali' value='{$suuntalavanhyllyvali}' />";
        echo "<input type='hidden' name='suuntalavanhyllytaso' value='{$suuntalavanhyllytaso}' />";
      }

      echo "<input type='hidden' name='koko_suuntalava' value='X' />";
      echo "<input type='hidden' name='vietiinko_koko_suuntalava' value='{$vietiinko_koko_suuntalava}' />";

      echo "<table><tr>";
      echo "<th>",t("Tulosta tavaraetiketti"),"</th>";

      echo "<td><select name='komento[Tavaraetiketti]'>";
      $query = "SELECT *
                FROM kirjoittimet
                WHERE yhtio = '{$kukarow['yhtio']}'
                ORDER BY kirjoitin";
      $kires = pupe_query($query);

      echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

      while ($kirow = mysql_fetch_assoc($kires)) {
        $sel = $kirow['tunnus'] == $kukarow['kirjoitin'] ? " selected" : "";
        echo "<option value='{$kirow['komento']}'{$sel}>{$kirow['kirjoitin']}</option>";
      }

      echo "</select></td>";
      echo "<td class='back'><input type='submit' value='",t("Tulosta"),"' /></td>";

      echo "</tr></table>";
      echo "</form>";
    }

    require("varastoon.inc");

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value='kalkyyli'>";
    echo "<input type='hidden' name='tee' value='varastoon'>";
    echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    echo "<input type='hidden' name='suuntalavan_tunnus' value='{$suuntalavan_tunnus}' />";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";

    if ($yhtiorow['suuntalavat'] == 'S' and $tee == '' and trim($suuntalavan_tunnus) != '' and trim($koko_suuntalava) == 'X') {

      if (trim($suuntalavanhyllypaikka) != '') {
        echo "<input type='hidden' name='suuntalavanhyllypaikka' value='{$suuntalavanhyllypaikka}' />";
      }
      else {
        echo "<input type='hidden' name='suuntalavanhyllyalue' value='{$suuntalavanhyllyalue}' />";
        echo "<input type='hidden' name='suuntalavanhyllynro' value='{$suuntalavanhyllynro}' />";
        echo "<input type='hidden' name='suuntalavanhyllyvali' value='{$suuntalavanhyllyvali}' />";
        echo "<input type='hidden' name='suuntalavanhyllytaso' value='{$suuntalavanhyllytaso}' />";
      }
      echo "<input type='hidden' name='koko_suuntalava' value='X' />";
      echo "<input type='hidden' name='vietiinko_koko_suuntalava' value='{$vietiinko_koko_suuntalava}' />";
    }

    echo "<input type='submit' value='".t("Hyvältä näyttää, vie varastoon!")."'>";
    echo "</form>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
    echo "<input type='hidden' name='toiminto' value=''>";
    echo "<input type='hidden' name='ytunnus' value='$laskurow[ytunnus]'>";
    echo "<input type='hidden' name='toimipaikka' value='{$toimipaikka}' />";

    if ($nappikeikalle == 'menossa') {
      echo "<input type='hidden' name='nappikeikalla' value='ollaan'>";
      echo "<input type='hidden' name='otunnus' value='$otunnus'>";
    }

    echo "<input type='submit' value='".t("Peruuta")."'>";
    echo "</form>";
  }
}
else {
  echo "<br><br><font class='error'>".t("Saapumisen tiedot eivät täsmää! Korjaa asia")."!</font><br>";
}
