<?php

require "../inc/parametrit.inc";

$rajaus = isset($rajaus) ? $rajaus : array();

echo "<h1 class='head'>" . t("Maksutapahtumaselaus") . "<h1><hr>";

piirra_hakuformi();

$tilaukset = hae_tilaukset($rajaus);

piirra_tilaus_table($tilaukset);

require "inc/footer.inc";

function piirra_hakuformi() {
  echo "<form name='hakuformi' id='hakuformi'>";
  echo "<table>";

  echo "<tr>";
  echo "<th><label for='rajaus_alku_paiva'>" . t("Syötä alkupäivämäärä") .
       " (Pp-Kk-Vvvv)</label></th>";
  echo "<td>";
  echo "<input type='number'
               name='rajaus[alku][paiva]'
               id='rajaus_alku_paiva'
               min='1'
               max='31'>";
  echo "<input type='number'
               name='rajaus[alku][kuukausi]'
               id='rajaus_alku_kuukausi'
               min='1'
               max='12'>";
  echo "<input type='number'
               name='rajaus[alku][vuosi]'
               id='rajaus_alku_vuosi'
               min='1'
               max='9999'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='rajaus_loppu_paiva'>" . t("Syötä loppupäivämäärä") .
       " (Pp-Kk-Vvvv)</label></th>";
  echo "<td>";
  echo "<input type='number'
               name='rajaus[loppu][paiva]'
               id='rajaus_loppu_paiva'
               min='1'
               max='31'>";
  echo "<input type='number'
               name='rajaus[loppu][kuukausi]'
               id='rajaus_loppu_kuukausi'
               min='1'
               max='12'>";
  echo "<input type='number'
               name='rajaus[loppu][vuosi]'
               id='rajaus_loppu_vuosi'
               min='1'
               max='9999'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='rajaus_limit'>" . t("Näytä %d uusinta tilausta", "", 500) . "</label></th>";
  echo "<td><input type='checkbox' name='rajaus[limit]' id='rajaus_limit' value='500'></td>";
  echo "<td class='back'>";
  echo "<input type='submit' value='Hae'>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";
  echo "</form>";
}

function hae_tilaukset($rajaus) {
  global $kukarow;

  $rajaus["limit"] = isset($rajaus["limit"]) ? $rajaus["limit"] : 50;

  $query = "SELECT lasku.laskunro,
            lasku.tunnus,
            asiakas.nimi AS asiakas,
            asiakas.asiakasnro,
            lasku.laskutettu,
            kuka.nimi AS myyja,
            lasku.summa,
            lasku.asiakkaan_tilausnumero,
            lasku.viite
            FROM lasku
            INNER JOIN maksupaatetapahtumat ON (maksupaatetapahtumat.yhtio = lasku.yhtio
              AND maksupaatetapahtumat.tilausnumero = lasku.tunnus)
            INNER JOIN asiakas ON (asiakas.yhtio = lasku.yhtio
              AND asiakas.tunnus = lasku.liitostunnus)
            INNER JOIN kuka ON (kuka.yhtio = lasku.yhtio
              AND kuka.tunnus = lasku.myyja)
            WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
            ORDER BY lasku.laskutettu DESC
            LIMIT {$rajaus["limit"]};";

  return pupe_query($query);
}

function piirra_tilaus_table($tilaukset) {
  global $yhtiorow;

  echo "<table>";
  echo "<thead>";

  echo "<tr>";
  echo "<th>" . t("Kuitti") . "</th>";
  echo "<th>" . t("Tilaus") . "</th>";
  echo "<th>" . t("Asiakas") . "</th>";
  echo "<th>" . t("As.nro") . "</th>";
  echo "<th>" . t("Aika") . "</th>";
  echo "<th>" . t("Myyjä") . "</th>";
  echo "<th>" . t("Summa") . "</th>";
  echo "<th>" . t("Astilno") . "</th>";
  echo "<th>" . t("Tilausviite") . "</th>";
  echo "</trLi>";

  echo "</thead>";
  echo "<tbody>";

  while ($tilaus = mysql_fetch_assoc($tilaukset)) {
    $tilaus["summa"] = number_format($tilaus["summa"], $yhtiorow["hintapyoristys"], ",", " ");

    echo "<tr>";
    echo "<td class='text-right'>{$tilaus["laskunro"]}</td>";
    echo "<td class='text-right'>{$tilaus["tunnus"]}</td>";
    echo "<td>{$tilaus["asiakas"]}</td>";
    echo "<td class='text-right'>{$tilaus["asiakasnro"]}</td>";
    echo "<td>{$tilaus["laskutettu"]}</td>";
    echo "<td>{$tilaus["myyja"]}</td>";
    echo "<td class='text-right'>{$tilaus["summa"]}</td>";
    echo "<td class='text-right'>{$tilaus["asiakkaan_tilausnumero"]}</td>";
    echo "<td class='text-right'>{$tilaus["viite"]}</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}
