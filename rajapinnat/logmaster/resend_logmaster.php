<?php

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

require "../../inc/parametrit.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

echo "<font class='head'>".t("Uudelleenl‰het‰ LogMaster-ker‰yssanoma")."</font><hr>";

if (!in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K'))) {
  echo "Ker‰tt‰vien tilauksien l‰hett‰minen estetty yhtiˆtasolla!<br>";
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
    echo t("Uudelleenl‰hetet‰‰n LogMaster-ker‰yssanoma").": {$tilaukset}<br>";

    while ($laskurow = mysql_fetch_assoc($res)) {
      $filename = logmaster_outbounddelivery($laskurow['tunnus']);

      if ($filename === false) {
        echo "Tilauksen {$laskurow['tunnus']} sanoman luonti ep‰onnistui.<br>";
      }
      else {
        $palautus = logmaster_send_file($filename);

        if ($palautus == 0) {
          pupesoft_log('logmaster_outbound_delivery', "Siirretiin tilaus {$otunnus} {$uj_nimi} -j‰rjestelm‰‰n.");
          echo "Siirretiin tilaus {$otunnus} {$uj_nimi} -j‰rjestelm‰‰n.<br>";
        }
        else {
          pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$otunnus} siirto {$uj_nimi} -j‰rjestelm‰‰n ep‰onnistui.");
          echo "Tilauksen {$otunnus} siirto {$uj_nimi} -j‰rjestelm‰‰n ep‰onnistui.<br>";
        }
      }
    }
  }
  else {
    echo "<font class='error'>".t("Tilauksia ei lˆytynyt").": {$tilaukset}!</font><br>";
  }
}

echo "<br><br><font class='message'>".t("Anna tilausnumerot pilkulla eroteltuna")."</font><br>";
echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='laheta'>";
echo "<textarea name='tilaukset' rows='10' cols='60'></textarea>";
echo "<br><input type='submit' value='".t("L‰het‰ ker‰yssanomat")."'>";
echo "</form>";

require "inc/footer.inc";
