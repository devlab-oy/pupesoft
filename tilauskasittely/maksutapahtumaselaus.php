<?php

require "../inc/parametrit.inc";

if (isset($tilaus) and $tilaus["toiminto"] == "kuittikopio") {
  require "tulosta_asiakkaan_kuitti.inc";

  $kuitti_params = array(
    "avaa_lipas_lopuksi" => false
  );

  tulosta_asiakkaan_kuitti($tilaus["laskunro"], $kukarow["kuittitulostin"], $kuitti_params);
}

$rajaus = isset($rajaus) ? $rajaus : array();
$rajaus = kasittele_rajaus($rajaus);

echo "<h1 class='head'>" . t("Maksutapahtumaselaus") . "<h1><hr>";

piirra_hakuformi($rajaus);

$tilaukset = hae_tilaukset($rajaus);

piirra_tilaus_table($tilaukset);

require "inc/footer.inc";

function piirra_hakuformi($rajaus) {
  echo "<form name='hakuformi' id='hakuformi'>";
  echo "<table>";

  echo "<tr>";
  echo "<th><label for='rajaus_alku_paiva'>" . t("Syötä alkupäivämäärä") .
       " (Pp-Kk-Vvvv)</label></th>";
  echo "<td>";
  echo "<input type='number'
               name='rajaus[alku][paiva]'
               id='rajaus_alku_paiva'
               required
               min='1'
               max='31'
               value='{$rajaus["alku"]["paiva"]}'>";
  echo "<input type='number'
               name='rajaus[alku][kuukausi]'
               id='rajaus_alku_kuukausi'
               required
               min='1'
               max='12'
               value='{$rajaus["alku"]["kuukausi"]}'>";
  echo "<input type='number'
               name='rajaus[alku][vuosi]'
               id='rajaus_alku_vuosi'
               required
               min='1'
               max='9999'
               value='{$rajaus["alku"]["vuosi"]}'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='rajaus_loppu_paiva'>" . t("Syötä loppupäivämäärä") .
       " (Pp-Kk-Vvvv)</label></th>";
  echo "<td>";
  echo "<input type='number'
               name='rajaus[loppu][paiva]'
               id='rajaus_loppu_paiva'
               required
               min='1'
               max='31'
               value='{$rajaus["loppu"]["paiva"]}'>";
  echo "<input type='number'
               name='rajaus[loppu][kuukausi]'
               id='rajaus_loppu_kuukausi'
               required
               min='1'
               max='12'
               value='{$rajaus["loppu"]["kuukausi"]}'>";
  echo "<input type='number'
               name='rajaus[loppu][vuosi]'
               id='rajaus_loppu_vuosi'
               required
               min='1'
               max='9999'
               value='{$rajaus["loppu"]["vuosi"]}'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th><label for='rajaus_limit'>" . t("Näytä %d uusinta tilausta", "", 500) . "</label></th>";

  $checked = $rajaus["limit"] == 500 ? " checked" : "";

  echo "<td><input type='checkbox' name='rajaus[limit]' id='rajaus_limit' value='500'{$checked}></td>";
  echo "<td class='back'>";
  echo "<input type='submit' value='" . t("Hae") . "'>";
  echo "</td>";
  echo "</tr>";

  echo "</table>";
  echo "</form>";
}

function hae_tilaukset($rajaus) {
  global $kukarow;

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
            AND lasku.laskutettu BETWEEN '{$rajaus["alku"]["pvm"]}' AND '{$rajaus["loppu"]["pvm"]}'
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

    echo "<td class='back'>";
    echo "<form>";
    echo "<input type='hidden' name='tilaus[laskunro]' value='{$tilaus["laskunro"]}'>";
    echo "<input type='hidden' name='tilaus[toiminto]' value='kuittikopio'>";
    echo "<input type='submit' value='" . t("Kuittikopio") . "'>";
    echo "</form>";
    echo "</td>";

    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}

function kasittele_rajaus($rajaus) {
  $kuukausi_sitten = strtotime("1 month ago");

  if (!$rajaus["alku"]["vuosi"]) $rajaus["alku"]["vuosi"] = date("Y", $kuukausi_sitten);
  if (!$rajaus["alku"]["kuukausi"]) $rajaus["alku"]["kuukausi"] = date("m", $kuukausi_sitten);
  if (!$rajaus["alku"]["paiva"]) $rajaus["alku"]["paiva"] = date("d", $kuukausi_sitten);

  $alku = strtotime("{$rajaus["alku"]["vuosi"]}-" .
                    "{$rajaus["alku"]["kuukausi"]}-" .
                    "{$rajaus["alku"]["paiva"]}");
  $rajaus["alku"]["pvm"] = date("Y-m-d", $alku);

  if (!$rajaus["loppu"]["vuosi"]) $rajaus["loppu"]["vuosi"] = date("Y");
  if (!$rajaus["loppu"]["kuukausi"]) $rajaus["loppu"]["kuukausi"] = date("m");
  if (!$rajaus["loppu"]["paiva"]) $rajaus["loppu"]["paiva"] = date("d");

  $loppu = strtotime("{$rajaus["loppu"]["vuosi"]}-" .
                     "{$rajaus["loppu"]["kuukausi"]}-" .
                     "{$rajaus["loppu"]["paiva"]}");
  $rajaus["loppu"]["pvm"] = date("Y-m-d", $loppu);

  if (!$rajaus["limit"]) $rajaus["limit"] = 50;

  return $rajaus;
}
