<?php

if ($tee == "kaupankohde") {
  $positio = "Ajoneuvo";
  $liitos = "myyntirivitunnus";
}
else {
  $positio = "Vaihtoajoneuvo";
  $liitos = "ostorivitunnus";
}

$query = "SELECT sarjanumeroseuranta.tunnus
          FROM tilausrivi
          JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.{$liitos}
          JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.positio='$positio'
          WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
          and tilausrivi.otunnus = '{$laskurow['laskutunnus']}'
          LIMIT 1";
$ajres = pupe_query($query);

if ($ajrow = mysql_fetch_assoc($ajres)) {

  $query = "SELECT *
            FROM sarjanumeron_lisatiedot
            WHERE yhtio      = '$kukarow[yhtio]'
            and liitostunnus = '$ajrow[tunnus]'";
  $lisares = pupe_query($query);
  $lisarow = mysql_fetch_assoc($lisares);

  if ($lisarow["tunnus"] != 0) {
    $ylisa = "&tunnus=$lisarow[tunnus]";
  }
  else {
    $ylisa = "&liitostunnus=$sarjarow[tunnus]&uusi=1";
  }

  echo "<iframe id='yllapito_iframe' src='{$palvelin2}yllapito.php?toim=sarjanumeron_lisatiedot!!!!!!TRUE$ylisa&mista=ajoneuvon_tiedot' style='width: 100%; border: 0px; display: block; overflow:hidden;' scrolling='no' frameborder='0'></iFrame>";

  echo "<script LANGUAGE='JavaScript'>
          $(\"#yllapito_iframe\").load(function() {
              $(this).height( $(this).contents().find(\"body\").height() );
          });
        </script>";
}
else {
  echo "<font class='error'>".t("VIRHE: Ajoneuvoa ei löydy tilaukselta")."!</font>";
}
