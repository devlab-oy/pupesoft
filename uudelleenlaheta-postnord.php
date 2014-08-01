<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

require "inc/parametrit.inc";

echo "<font class='head'>".t("Uudelleenl�het� PostNord-ker�yssanoma")."</font><hr>";

if ($tee == "laheta" and $tilaukset != "") {

  $tilaukset = pupesoft_cleanstring(str_replace(array("\r", "\n"), "", $tilaukset));

  $query = "SELECT distinct lasku.tunnus
            FROM lasku
            JOIN varastopaikat ON (lasku.yhtio=varastopaikat.yhtio AND lasku.varasto=varastopaikat.tunnus AND varastopaikat.ulkoinen_jarjestelma = 'P')
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            AND lasku.tila    in ('L','N')
            AND lasku.tunnus  in ($tilaukset)";
  $res  = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    while ($laskurow = mysql_fetch_assoc($res)) {
      echo t("Uudelleenl�hetet��n PostNord-ker�yssanoma").": $laskurow[tunnus]<br>";
      posten_outbounddelivery($laskurow["tunnus"]);
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
