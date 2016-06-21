<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "inc/parametrit.inc";

echo "<font class='head'>".t("Uudelleenlähetä LogMaster-keräyssanoma")."</font><hr>";

if ($tee == "laheta" and $tilaukset != "") {

  $tilaukset = pupesoft_cleanstring(str_replace(array("\r", "\n"), "", $tilaukset));

  $query = "SELECT distinct lasku.tunnus, varastopaikat.ulkoinen_jarjestelma
            FROM lasku
            JOIN varastopaikat ON (lasku.yhtio=varastopaikat.yhtio AND lasku.varasto=varastopaikat.tunnus AND varastopaikat.ulkoinen_jarjestelma in ('L','P'))
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            AND lasku.tila    in ('L','N', 'G')
            AND lasku.tunnus  in ($tilaukset)";
  $res  = pupe_query($query);

  if (mysql_num_rows($result) > 0 and in_array($yhtiorow['ulkoinen_jarjestelma'], array('','K'))) {
    while ($laskurow = mysql_fetch_assoc($res)) {
      echo t("Uudelleenlähetetään LogMaster-keräyssanoma").": $laskurow[tunnus]<br>";
      posten_outbounddelivery($laskurow["tunnus"], $laskurow['ulkoinen_jarjestelma']);
    }
  }
  else {
    echo "<font class='error'>".t("Tilauksia ei löytynyt").": $tilaukset!</font><br>";
  }
}

echo "<br><br><font class='message'>".t("Anna tilausnumerot pilkulla eroteltuna")."</font><br>";
echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='laheta'>";
echo "<textarea name='tilaukset' rows='10' cols='60'></textarea>";
echo "<br><input type='submit' value='".t("Lähetä keräyssanomat")."'>";
echo "</form>";

require "inc/footer.inc";
