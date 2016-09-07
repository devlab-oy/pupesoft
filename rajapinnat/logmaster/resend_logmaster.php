<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "../../inc/parametrit.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

$tee = empty($tee) ? '' : $tee;

echo "<font class='head'>".t("Uudelleenlähetä LogMaster-keräyssanoma")."</font><hr>";

if (!LOGMASTER_RAJAPINTA or !in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K'))) {
  echo t("Kerättävien tilauksien lähettäminen estetty yhtiötasolla")."!<br>";
  $tee = '';
}

if ($tee == "laheta" and $tilaukset != "") {

  $tilaukset = str_replace(array("\r", "\n", " "), "", $tilaukset);
  $tilaukset = explode(",", $tilaukset);
  $tilaukset = array_filter($tilaukset, 'is_numeric');
  $tilaukset = implode(",", $tilaukset);

  # Tilaus pitää olla jo lähetetty ulkoiseen varastoon, jotta se voidaan lähettää uudestaan
  $query = "SELECT DISTINCT lasku.tunnus
            FROM lasku
            JOIN varastopaikat ON (lasku.yhtio = varastopaikat.yhtio
              AND lasku.varasto = varastopaikat.tunnus
              AND varastopaikat.ulkoinen_jarjestelma IN ('L','P')
            )
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.lahetetty_ulkoiseen_varastoon > 0
            AND lasku.tila IN ('L','G')
            AND lasku.tunnus IN ({$tilaukset})";
  $res = pupe_query($query);

  if (mysql_num_rows($res) > 0) {
    echo t("Uudelleenlähetetään LogMaster-keräyssanoma").": {$tilaukset}<br>";

    while ($laskurow = mysql_fetch_assoc($res)) {
      $filename = logmaster_outbounddelivery($laskurow['tunnus']);

      if ($filename === false) {
        echo t("Tilauksen %d sanoman luonti epäonnistui", '', $laskurow['tunnus'])."<br>";
        continue;
      }

      $palautus = logmaster_send_file($filename);

      if ($palautus == 0) {
        pupesoft_log('logmaster_outbound_delivery', "Siirretiin tilaus {$laskurow['tunnus']}.");
        echo t("Siirretiin tilaus %d", '', $laskurow['tunnus'])."<br>";
      }
      else {
        pupesoft_log('logmaster_outbound_delivery', "Tilauksen {$laskurow['tunnus']} siirto epäonnistui.");
        echo t("Tilauksen %d siirto epäonnistui", '', $laskurow['tunnus'])."<br>";
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
