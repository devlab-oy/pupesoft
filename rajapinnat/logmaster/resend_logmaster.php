<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "../../inc/parametrit.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

echo "<font class='head'>".t("Uudelleenlähetä LogMaster-keräyssanoma")."</font><hr>";

if (!in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K'))) {
  echo "Kerättävien tilauksien lähettäminen estetty yhtiötasolla!<br>";
  require "inc/footer.inc";
  exit;
}

if ($tee == "laheta" and $tilaukset != "") {

  $tilaukset = pupesoft_cleanstring(str_replace(array("\r", "\n"), "", $tilaukset));

  $query = "SELECT DISTINCT lasku.tunnus
            FROM lasku
            JOIN varastopaikat ON (lasku.yhtio=varastopaikat.yhtio
              AND lasku.varasto=varastopaikat.tunnus
              AND varastopaikat.ulkoinen_jarjestelma IN ('L','P')
            )
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            AND lasku.tila    in ('L','N', 'G')
            AND lasku.tunnus  in ($tilaukset)";
  $res = pupe_query($query);

  if (mysql_num_rows($res) > 0) {
    echo t("Uudelleenlähetetään LogMaster-keräyssanoma").": {$tilaukset}<br>";

    while ($laskurow = mysql_fetch_assoc($res)) {
      $filename = logmaster_outbounddelivery($laskurow['tunnus']);

      if ($filename === false) {
        echo "Tilauksen {$laskurow['tunnus']} sanoman luonti epäonnistui.<br>";
      }
      else {
        $palautus = logmaster_send_file($filename);

        if ($palautus == 0) {
          logmaster_sent_timestamp($laskurow['tunnus']);
          pupesoft_log('logmaster_outbound_delivery', "Siirretiin tilaus {$laskurow['tunnus']}.");
          echo "Siirretiin tilaus {$laskurow['tunnus']}.<br>";
        }
        else {
          pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$laskurow['tunnus']} siirto epäonnistui.");
          echo "Tilauksen {$laskurow['tunnus']} siirto epäonnistui.<br>";
        }
      }
    }
  }
  else {
    echo "<font class='error'>".t("Tilauksia ei löytynyt").": {$tilaukset}!</font><br>";
  }
}

echo "<br><br><font class='message'>".t("Anna tilausnumerot pilkulla eroteltuna")."</font><br>";
echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='laheta'>";
echo "<textarea name='tilaukset' rows='10' cols='60'></textarea>";
echo "<br><input type='submit' value='".t("Lähetä keräyssanomat")."'>";
echo "</form>";

require "inc/footer.inc";
