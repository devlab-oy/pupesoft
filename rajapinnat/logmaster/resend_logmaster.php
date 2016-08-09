<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

require "inc/parametrit.inc";
require "rajapinnat/logmaster/logmaster-functions.php";

echo "<font class='head'>".t("Uudelleenl�het� LogMaster-ker�yssanoma")."</font><hr>";

if ($tee == "laheta" and $tilaukset != "") {

  $tilaukset = pupesoft_cleanstring(str_replace(array("\r", "\n"), "", $tilaukset));

  $query = "SELECT distinct lasku.tunnus, varastopaikat.ulkoinen_jarjestelma
            FROM lasku
            JOIN varastopaikat ON (lasku.yhtio=varastopaikat.yhtio AND lasku.varasto=varastopaikat.tunnus AND varastopaikat.ulkoinen_jarjestelma in ('L','P'))
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            AND lasku.tila    in ('L','N', 'G')
            AND lasku.tunnus  in ($tilaukset)";
  $res  = pupe_query($query);

  if (mysql_num_rows($result) > 0 and in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K'))) {
    while ($laskurow = mysql_fetch_assoc($res)) {
      echo t("Uudelleenl�hetet��n LogMaster-ker�yssanoma").": $laskurow[tunnus]<br>";
      posten_outbounddelivery($laskurow["tunnus"], $laskurow['ulkoinen_jarjestelma']);
    }
  }
  else {
    echo "<font class='error'>".t("Tilauksia ei l�ytynyt").": $tilaukset!</font><br>";
  }
}

echo "<br><br><font class='message'>".t("Anna tilausnumerot pilkulla eroteltuna")."</font><br>";
echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='laheta'>";
echo "<textarea name='tilaukset' rows='10' cols='60'></textarea>";
echo "<br><input type='submit' value='".t("L�het� ker�yssanomat")."'>";
echo "</form>";

require "inc/footer.inc";
