<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

require "../../inc/parametrit.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

echo "<font class='head'>".t("Uudelleenl�het� LogMaster-ker�yssanoma")."</font><hr>";

if (!in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K'))) {
  echo "Ker�tt�vien tilauksien l�hett�minen estetty yhti�tasolla!<br>";
  require "inc/footer.inc";
  exit;
}

if ($tee == "laheta" and $tilaukset != "") {

  $tilaukset = pupesoft_cleanstring(str_replace(array("\r", "\n"), "", $tilaukset));

  $query = "SELECT GROUP_CONCAT(DISTINCT lasku.tunnus) AS tunnukset
            FROM lasku
            JOIN varastopaikat ON (lasku.yhtio=varastopaikat.yhtio
              AND lasku.varasto=varastopaikat.tunnus
              AND varastopaikat.ulkoinen_jarjestelma IN ('L','P')
            )
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            AND lasku.tila    in ('L','N', 'G')
            AND lasku.tunnus  in ($tilaukset)";
  $res          = pupe_query($query);
  $tunnuksetrow = mysql_fetch_assoc($res);
  $tunnukset    = $tunnuksetrow['tunnukset'];

  if ($tunnukset != '') {
    echo t("Uudelleenl�hetet��n LogMaster-ker�yssanoma").": {$tunnukset}<br>";
    require "rajapinnat/logmaster/outbound_delivery.php";
  }
  else {
    echo "<font class='error'>".t("Tilauksia ei l�ytynyt").": {$tilaukset}!</font><br>";
  }
}

echo "<br><br><font class='message'>".t("Anna tilausnumerot pilkulla eroteltuna")."</font><br>";
echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='laheta'>";
echo "<textarea name='tilaukset' rows='10' cols='60'></textarea>";
echo "<br><input type='submit' value='".t("L�het� ker�yssanomat")."'>";
echo "</form>";

require "inc/footer.inc";
