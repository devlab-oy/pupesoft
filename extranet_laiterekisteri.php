<?php

if (strpos($_SERVER['SCRIPT_NAME'], "extranet_laiterekisteri.php") !== FALSE) {
  require "parametrit.inc";
}

$request = array();

if ($kukarow['extranet'] == '') die(t("K�ytt�j�n parametrit - T�m� ominaisuus toimii vain extranetiss�"));

require "asiakasvalinta.inc";

piirra_kayttajan_laitteet();

function piirra_kayttajan_laitteet() {

  echo "<font class='head'>".t("Laiterekisteri")."</font><hr>";

  $naytettavat_laitteet = hae_kayttajan_laitteet();
  if (count($naytettavat_laitteet) > 0) {
    echo "<form name ='laiteformi'>";
    echo "<table>";
    echo "<tr>";
    piirra_headerit();
    echo "</tr>";

    foreach ($naytettavat_laitteet as $laite) {
      piirra_laiterivi($laite);
    }

    echo "</table>";
    echo "</form>";
  }
  else {
    echo "<br><font class='error'>".t('Laiterekisterist� ei l�ydy yht��n laitetta tai niill� ei ole sopimuksia')."!</font><br/>";
  }
}

function hae_kayttajan_laitteet() {
  global $kukarow;

  $laitteet = array();

  if ($kukarow['oletus_asiakas'] == '') {
    return $laitteet;
  }

  $query = "SELECT
            laite.*,
            avainsana.selitetark valmistaja,
            tuote.tuotemerkki malli
            FROM laite
            LEFT JOIN tuote ON (tuote.yhtio = laite.yhtio
            AND tuote.tuoteno                           = laite.tuoteno)
            LEFT JOIN avainsana ON (avainsana.yhtio = tuote.yhtio
            AND avainsana.laji                          = 'TRY'
            AND avainsana.selite                        = tuote.try)
            JOIN laitteen_sopimukset ON (laitteen_sopimukset.laitteen_tunnus = laite.tunnus)
            JOIN tilausrivi ON (laitteen_sopimukset.yhtio = tilausrivi.yhtio
            AND laitteen_sopimukset.sopimusrivin_tunnus = tilausrivi.tunnus)
            JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus)
            WHERE laite.yhtio                           = '{$kukarow['yhtio']}'
            AND lasku.liitostunnus                      = '{$kukarow['oletus_asiakas']}'
            GROUP BY laite.sarjanro,laite.tuoteno";
  $result = pupe_query($query);
  while ($row = mysql_fetch_assoc($result)) {
    $laitteet[] = $row;
  }
  return $laitteet;
}

function piirra_headerit() {
  $headerit = array(
    t("Nro"),
    t("Valmistaja"),
    t("Malli"),
    t("Sarjanumero"),
    t("Tuotenumero"),
    t("Sla"),
    t("Sd sla"),
    t("Vc"),
    t("Vc end"),
    t("Lcm info"),
    t("Ip"),
    t("Mac")
  );
  foreach ($headerit as $header) {
    echo "<th>{$header}</th>";
  }
}

function piirra_laiterivi($laite) {
  global $palvelin2;

  echo "<tr>";
  echo "<td>{$laite['tunnus']}</td>";
  echo "<td>{$laite['valmistaja']}</td>";
  echo "<td>{$laite['malli']}</td>";
  echo "<td>{$laite['sarjanro']}</td>";
  echo "<td>{$laite['tuoteno']}</td>";
  echo "<td>{$laite['sla']}</td>";
  echo "<td>{$laite['sd_sla']}</td>";
  echo "<td>{$laite['valmistajan_sopimusnumero']}</td>";
  echo "<td>{$laite['valmistajan_sopimus_paattymispaiva']}</td>";
  echo "<td>{$laite['kommentti']}</td>";
  echo "<td>{$laite['ip_osoite']}</td>";
  echo "<td>{$laite['mac_osoite']}</td>";
  echo "<td class='back'><a href='{$palvelin2}extranet_tyomaaraykset.php?tyom_toiminto=UUSI&laite_tunnus={$laite['tunnus']}'>".t('Uusi ty�m��r�ys')."</a></td>";
  echo "</tr>";
}
