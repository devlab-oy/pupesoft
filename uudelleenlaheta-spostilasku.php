<?php

require "inc/parametrit.inc";

// Timeout in 5h
ini_set("mysql.connect_timeout", 18000);
ini_set("max_execution_time", 18000);

echo "<font class='head'>".t("Uudelleenlähetä sähköpostilasku")."</font><hr>";

if ($tee == "laheta" and $laskunumerot != "") {

  $laskunumerot = trim($laskunumerot);

  //Haetaan laskut jotka laitetaan aineistoon
  $query = "SELECT *
            from lasku
            where yhtio  = '$kukarow[yhtio]'
            and tila     = 'U'
            and alatila  = 'X'
            and chn      = '666'
            and laskunro in ($laskunumerot)";
  $res   = pupe_query($query);

  $lkm = count(explode(',', $laskunumerot));

  echo "<br><font class='message'>".t("Syötit")." $lkm ".t("laskua").".</font><br>";
  echo "<font class='message'>".t("Aineistoon lisätään")." ".mysql_num_rows($res)." ".t("laskua").".</font><br><br>";

  require 'tilauskasittely/tulosta_lasku.inc';

  while ($laskurow = mysql_fetch_assoc($res)) {
    // Haetaan maksuehdon tiedot
    $query  = "SELECT pankkiyhteystiedot.*, maksuehto.*
               FROM maksuehto
               LEFT JOIN pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
               WHERE maksuehto.yhtio='$kukarow[yhtio]' and maksuehto.tunnus='$laskurow[maksuehto]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      $masrow = array();

      if ($laskurow["erpcm"] == "0000-00-00") {
        echo "<font class='message'><br>\n".t("Maksuehtoa")." $laskurow[maksuehto] ".t("ei löydy!")." Tunnus $laskurow[tunnus] ".t("Laskunumero")." $laskurow[laskunro] ".t("epäonnistui pahasti")."!</font><br>\n<br>\n";
      }
    }
    else {
      $masrow = mysql_fetch_assoc($result);
    }

    //Haetaan factoringsopimuksen tiedot
    if (isset($masrow["factoring_id"])) {
      $query = "SELECT *
                FROM factoring
                WHERE yhtio  = '$kukarow[yhtio]'
                and tunnus   = '$masrow[factoring_id]'
                and valkoodi = '$laskurow[valkoodi]'";
      $fres = pupe_query($query);
      $frow = mysql_fetch_assoc($fres);
    }
    else {
      unset($frow);
    }

    $pankkitiedot = array();

    //Laitetaan pankkiyhteystiedot kuntoon
    if (isset($masrow["factoring_id"])) {
      $pankkitiedot["pankkinimi1"]  =  $frow["pankkinimi1"];
      $pankkitiedot["pankkitili1"]  =  $frow["pankkitili1"];
      $pankkitiedot["pankkiiban1"]  =  $frow["pankkiiban1"];
      $pankkitiedot["pankkiswift1"] =  $frow["pankkiswift1"];
      $pankkitiedot["pankkinimi2"]  =  $frow["pankkinimi2"];
      $pankkitiedot["pankkitili2"]  =  $frow["pankkitili2"];
      $pankkitiedot["pankkiiban2"]  =  $frow["pankkiiban2"];
      $pankkitiedot["pankkiswift2"] = $frow["pankkiswift2"];
      $pankkitiedot["pankkinimi3"]  =  "";
      $pankkitiedot["pankkitili3"]  =  "";
      $pankkitiedot["pankkiiban3"]  =  "";
      $pankkitiedot["pankkiswift3"] =  "";

    }
    elseif ($masrow["pankkinimi1"] != "") {
      $pankkitiedot["pankkinimi1"]  =  $masrow["pankkinimi1"];
      $pankkitiedot["pankkitili1"]  =  $masrow["pankkitili1"];
      $pankkitiedot["pankkiiban1"]  =  $masrow["pankkiiban1"];
      $pankkitiedot["pankkiswift1"] =  $masrow["pankkiswift1"];
      $pankkitiedot["pankkinimi2"]  =  $masrow["pankkinimi2"];
      $pankkitiedot["pankkitili2"]  =  $masrow["pankkitili2"];
      $pankkitiedot["pankkiiban2"]  =  $masrow["pankkiiban2"];
      $pankkitiedot["pankkiswift2"] = $masrow["pankkiswift2"];
      $pankkitiedot["pankkinimi3"]  =  $masrow["pankkinimi3"];
      $pankkitiedot["pankkitili3"]  =  $masrow["pankkitili3"];
      $pankkitiedot["pankkiiban3"]  =  $masrow["pankkiiban3"];
      $pankkitiedot["pankkiswift3"] =  $masrow["pankkiswift3"];
    }
    else {
      $pankkitiedot["pankkinimi1"]  =  $yhtiorow["pankkinimi1"];
      $pankkitiedot["pankkitili1"]  =  $yhtiorow["pankkitili1"];
      $pankkitiedot["pankkiiban1"]  =  $yhtiorow["pankkiiban1"];
      $pankkitiedot["pankkiswift1"] =  $yhtiorow["pankkiswift1"];
      $pankkitiedot["pankkinimi2"]  =  $yhtiorow["pankkinimi2"];
      $pankkitiedot["pankkitili2"]  =  $yhtiorow["pankkitili2"];
      $pankkitiedot["pankkiiban2"]  =  $yhtiorow["pankkiiban2"];
      $pankkitiedot["pankkiswift2"] = $yhtiorow["pankkiswift2"];
      $pankkitiedot["pankkinimi3"]  =  $yhtiorow["pankkinimi3"];
      $pankkitiedot["pankkitili3"]  =  $yhtiorow["pankkitili3"];
      $pankkitiedot["pankkiiban3"]  =  $yhtiorow["pankkiiban3"];
      $pankkitiedot["pankkiswift3"] =  $yhtiorow["pankkiswift3"];
    }

    $asiakas_apu_query = "SELECT *
                          FROM asiakas
                          WHERE yhtio = '$kukarow[yhtio]'
                          AND tunnus  = '$laskurow[liitostunnus]'";
    $asiakas_apu_res = pupe_query($asiakas_apu_query);

    if (mysql_num_rows($asiakas_apu_res) == 1) {
      $asiakas_apu_row = mysql_fetch_assoc($asiakas_apu_res);
    }
    else {
      $asiakas_apu_row = array();
    }

    $kieli = trim(strtoupper($yhtiorow["kieli"]));

    if (!empty($asiakas_apu_row["kieli"])) {
      $kieli = trim(strtoupper($asiakas_apu_row["kieli"]));
    }

    $vientierittelymail    = "";
    $vientierittelykomento = "";

    // Saatekirje tulee käyttöliittymästä suomeksi:
    if (!empty($saatekirje)) {
      $query = "SELECT avainsana_kieli.tunnus
                FROM avainsana
                JOIN avainsana as avainsana_kieli on (
                  avainsana_kieli.yhtio = avainsana.yhtio
                  and avainsana_kieli.laji = avainsana.laji
                  and avainsana_kieli.perhe = avainsana.perhe
                  and avainsana_kieli.perhe > 0
                  and avainsana_kieli.kieli = '$kieli')
                WHERE avainsana.yhtio = '$kukarow[yhtio]'
                AND avainsana.tunnus  = '$saatekirje'";
      $sakeres = pupe_query($query);

      if ($sakerow = mysql_fetch_assoc($sakeres)) {
        $saatekirje = $sakerow['tunnus'];
      }
   }

    tulosta_lasku($laskurow['tunnus'], $kieli, "VERKKOLASKU", "", -99, "", $saatekirje);

    echo  t("Lähetetään lasku").": $laskurow[laskunro]<br>\n";

    if (($laskurow["vienti"] == "E" or $laskurow["vienti"] == "K") and $yhtiorow["vienti_erittelyn_tulostus"] != "E") {
      $uusiotunnus = $laskurow["tunnus"];

      require 'tilauskasittely/tulosta_vientierittely.inc';

      //keksitään uudelle failille joku varmasti uniikki nimi:
      list($usec, $sec) = explode(' ', microtime());
      mt_srand((float) $sec + ((float) $usec * 100000));
      $pdffilenimi = "/tmp/Vientierittely-".md5(uniqid(mt_rand(), true)).".pdf";

      //kirjoitetaan pdf faili levylle..
      $fh = fopen($pdffilenimi, "w");
      if (fwrite($fh, $Xpdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
      fclose($fh);

      if ($vientierittelykomento == "email" or $vientierittelymail != "") {
        // lähetetään meili
        if ($vientierittelymail != "") {
          $komento = $vientierittelymail;
        }
        else {
          $komento = "";
        }

        $kutsu = t("Lasku", $kieli)." $laskurow[laskunro] ".t("Vientierittely", $kieli);

        if ($yhtiorow["liitetiedostojen_nimeaminen"] == "N") {
          $kutsu .= ", ".trim($laskurow["nimi"]);
        }

        $liite              = $pdffilenimi;
        $sahkoposti_cc      = "";
        $content_subject    = "";
        $content_body       = "";
        include "inc/sahkoposti.inc"; // sanotaan include eikä require niin ei kuolla
      }

      echo t("Vientierittely lähetetään")."...<br>\n";

      unset($Xpdf);
    }

    // Ei laiteta kaikkia sähköposteja samaan aikaan. Odotetaan muutama sekuntti
    flush();
    sleep(5);
  }
}
else {

  echo "<br><form method='post'>";
  echo "<input type='hidden' name='tee' value='laheta'>";

  echo "<table>";

  $query = "SELECT *
            FROM avainsana
            WHERE yhtio = '$kukarow[yhtio]'
            AND laji    = 'LASKUTUS_SAATE'
            AND kieli   = '$yhtiorow[kieli]'";
  $result = pupe_query($query);

  echo "<tr><th>".t("Valitse saatekirje").":</th>";
  echo "<td colspan='3'><select name='saatekirje'>";
  echo "<option value=''>".t("Ei saatetta")."</option>";

  while ($saaterow = mysql_fetch_array($result)) {
    echo "<option value='$saaterow[tunnus]'>$saaterow[selite]</option>";
  }

  echo "</select></td></tr>";

  echo "<tr><th>".t("Anna laskunumerot pilkulla eroteltuna").":</th>";

  echo "<td><textarea name='laskunumerot' rows='10' cols='60'></textarea></td>";

  echo "</table><br>";

  echo "<input type='submit' value='".t("Lähetä laskut")."'>";
  echo "</form>";
}
