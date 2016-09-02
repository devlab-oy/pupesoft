<?php

$pupe_DataTables = "maksutapahtumaselaus";

if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == "NAYTATILAUS") {
  $nayta_pdf = 1;
}

require "../inc/parametrit.inc";

if (isset($tilaus) and $tilaus["toiminto"] == "kuittikopio") {
  require "tulosta_asiakkaan_kuitti.inc";

  $kuitti_params = array(
    "avaa_lipas_lopuksi" => false
  );

  tulosta_asiakkaan_kuitti($tilaus["laskunro"], $kukarow["kuittitulostin"], $kuitti_params);
}

if (isset($tilaus) and $tilaus["toiminto"] == "pdf_kuitti_ruudulle") {
  require "tulosta_asiakkaan_kuitti.inc";

  $kuitti_params = array(
    "pdf_kuitti" => true,
    "pdf_kuitti_ruudulle" => true,
    "avaa_lipas_lopuksi" => false
  );

  tulosta_asiakkaan_kuitti($tilaus["laskunro"], 0, $kuitti_params);
  exit;
}

$rajaus = isset($rajaus) ? $rajaus : array();
$rajaus = kasittele_rajaus($rajaus);

js_openFormInNewWindow();

echo "<h1 class='head'>" . t("Maksutapahtumaselaus") . "<h1><hr>";

piirra_hakuformi($rajaus);

$tilaukset = hae_tilaukset($rajaus);

piirra_tilaus_table($tilaukset, $rajaus, $pupe_DataTables);

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

  $query = "SELECT DISTINCT lasku.laskunro,
            lasku.tunnus,
            asiakas.nimi AS asiakas,
            asiakas.asiakasnro,
            asiakas.tunnus AS asiakkaan_tunnus,
            lasku.laskutettu,
            kuka.nimi AS myyja,
            lasku.summa,
            lasku.asiakkaan_tilausnumero,
            lasku.viesti,
            lasku.yhtio,
            myyntilasku.tunnus AS myyntilaskun_tunnus
            FROM lasku
            INNER JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio
              AND maksuehto.tunnus      = lasku.maksuehto)
            INNER JOIN asiakas ON (asiakas.yhtio = lasku.yhtio
              AND asiakas.tunnus        = lasku.liitostunnus)
            INNER JOIN kuka ON (kuka.yhtio = lasku.yhtio
              AND kuka.tunnus           = lasku.myyja)
            INNER JOIN lasku AS myyntilasku ON (myyntilasku.yhtio = lasku.yhtio
              AND myyntilasku.laskunro  = lasku.laskunro
              AND myyntilasku.tila      = 'U'
              AND myyntilasku.alatila   = 'X')
            WHERE lasku.yhtio           = '{$kukarow["yhtio"]}'
            AND lasku.tila              = 'L'
            AND lasku.alatila           = 'X'
            AND DATE(lasku.laskutettu) BETWEEN '{$rajaus["alku"]["pvm"]}' AND '{$rajaus["loppu"]["pvm"]}'
            AND maksuehto.kateinen     != ''
            ORDER BY lasku.laskutettu DESC
            LIMIT {$rajaus["limit"]}";

  return pupe_query($query);
}

function piirra_tilaus_table($tilaukset, $rajaus, $pupe_DataTables) {
  global $yhtiorow, $kukarow, $palvelin2;

  $maksupaate_kassamyynti = (($yhtiorow['maksupaate_kassamyynti'] == 'K' and
      $kukarow["maksupaate_kassamyynti"] == "") or
    $kukarow["maksupaate_kassamyynti"] == "K");

  $kateisvaihto = tarkista_oikeus("eikateinen.php", "KATEISESTAKATEINEN");

  $totcol = 12;

  pupe_DataTables(array(array($pupe_DataTables, 8, $totcol)));

  echo "<table class='display dataTable' id='$pupe_DataTables'>";
  echo "<thead>";

  echo "<tr>";
  echo "<th>" . t("Kuitti") . "</th>";
  echo "<th>" . t("Tilaus") . "</th>";
  echo "<th>" . t("Asiakas") . "</th>";
  echo "<th>" . t("As.nro") . "</th>";
  echo "<th>" . t("Aika") . "</th>";
  echo "<th>" . t("Myyjä") . "</th>";
  echo "<th>" . t("Summa") . "</th>";
  echo "<th>" . t("Tilausviite") . "</th>";
  echo "<th class='back'></th>";
  echo "<th class='back'></th>";
  echo "<th class='back'></th>";
  echo "<th class='back'></th>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><input type='text' class='search_field' name='search_kuitti'></td>";
  echo "<td><input type='text' class='search_field' name='search_tilaus'></td>";
  echo "<td><input type='text' class='search_field' name='search_asiakas'></td>";
  echo "<td><input type='text' class='search_field' name='search_asnro'></td>";
  echo "<td><input type='text' class='search_field' name='search_aika'></td>";
  echo "<td><input type='text' class='search_field' name='search_myyja'></td>";
  echo "<td><input type='text' class='search_field' name='search_summa'></td>";
  echo "<td><input type='text' class='search_field' name='search_tilausviite'></td>";
  echo "<td class='back'></td>";
  echo "<td class='back'></td>";
  echo "<td class='back'></td>";
  echo "<td class='back'></td>";
  echo "</tr>";

  echo "</thead>";
  echo "<tbody>";

  while ($tilaus = mysql_fetch_assoc($tilaukset)) {
    $tilaus["summa"] = number_format($tilaus["summa"], $yhtiorow["hintapyoristys"], ",", " ");
    $lopetus = "{$palvelin2}tilauskasittely/maksutapahtumaselaus.php////" .
      "rajaus[alku][paiva]={$rajaus["alku"]["paiva"]}//" .
      "rajaus[alku][kuukausi]={$rajaus["alku"]["kuukausi"]}//" .
      "rajaus[alku][vuosi]={$rajaus["alku"]["vuosi"]}//" .
      "rajaus[loppu][paiva]={$rajaus["loppu"]["paiva"]}//" .
      "rajaus[loppu][kuukausi]={$rajaus["loppu"]["kuukausi"]}//" .
      "rajaus[loppu][vuosi]={$rajaus["loppu"]["vuosi"]}//" .
      "rajaus[limit]={$rajaus["limit"]}";

    echo "<tr>";
    echo "<td class='text-right'><a name='{$tilaus["laskunro"]}'></a>";

    if ($kateisvaihto) {
      echo "<a href='{$palvelin2}myyntires/eikateinen.php?toim=KATEISESTAKATEINEN&laskuno={$tilaus["laskunro"]}&lopetus=$lopetus///{$tilaus["laskunro"]}'>{$tilaus["laskunro"]}</a>";
    }
    else {
      echo "{$tilaus["laskunro"]}";
    }

    echo "</td>";
    echo "<td class='text-right'>{$tilaus["tunnus"]}</td>";
    echo "<td>{$tilaus["asiakas"]}</td>";
    echo "<td class='text-right'>{$tilaus["asiakasnro"]}</td>";
    echo "<td>{$tilaus["laskutettu"]}</td>";
    echo "<td>{$tilaus["myyja"]}</td>";
    echo "<td class='text-right'>{$tilaus["summa"]}</td>";
    echo "<td class='text-right'>{$tilaus["viesti"]}</td>";

    if ($maksupaate_kassamyynti) {
      echo "<td class='back'>";
      echo "<form>";
      echo "<input type='hidden' name='rajaus[alku][vuosi]' value='{$rajaus["alku"]["vuosi"]}'>";
      echo "<input type='hidden' name='rajaus[alku][kuukausi]' value='{$rajaus["alku"]["kuukausi"]}'>";
      echo "<input type='hidden' name='rajaus[alku][paiva]' value='{$rajaus["alku"]["paiva"]}'>";
      echo "<input type='hidden' name='rajaus[loppu][vuosi]' value='{$rajaus["loppu"]["vuosi"]}'>";
      echo "<input type='hidden' name='rajaus[loppu][kuukausi]' value='{$rajaus["loppu"]["kuukausi"]}'>";
      echo "<input type='hidden' name='rajaus[loppu][paiva]' value='{$rajaus["loppu"]["paiva"]}'>";
      echo "<input type='hidden' name='rajaus[limit]' value='{$rajaus["limit"]}'>";
      echo "<input type='hidden' name='tilaus[laskunro]' value='{$tilaus["laskunro"]}'>";
      echo "<input type='hidden' name='tilaus[toiminto]' value='kuittikopio'>";
      echo "<input type='submit' style='min-width: 0px;' value='" . t("Kuittikopio") . "'>";
      echo "</form>";
      echo "</td>";
    }
    else {
      echo "<td class='back'>";
      echo "<form id='tulostakopioform_{$tilaus["laskunro"]}' name='tulostakopioform_{$tilaus["laskunro"]}'>";
      echo "<input type='hidden' name='rajaus[alku][vuosi]' value='{$rajaus["alku"]["vuosi"]}'>";
      echo "<input type='hidden' name='rajaus[alku][kuukausi]' value='{$rajaus["alku"]["kuukausi"]}'>";
      echo "<input type='hidden' name='rajaus[alku][paiva]' value='{$rajaus["alku"]["paiva"]}'>";
      echo "<input type='hidden' name='rajaus[loppu][vuosi]' value='{$rajaus["loppu"]["vuosi"]}'>";
      echo "<input type='hidden' name='rajaus[loppu][kuukausi]' value='{$rajaus["loppu"]["kuukausi"]}'>";
      echo "<input type='hidden' name='rajaus[loppu][paiva]' value='{$rajaus["loppu"]["paiva"]}'>";
      echo "<input type='hidden' name='rajaus[limit]' value='{$rajaus["limit"]}'>";
      echo "<input type='hidden' name='tilaus[laskunro]' value='{$tilaus["laskunro"]}'>";
      echo "<input type='hidden' name='tilaus[toiminto]' value='pdf_kuitti_ruudulle'>";
      echo "<input type='hidden' name='tee' value='NAYTATILAUS'>";
      echo "<input type='submit' style='min-width: 0px;' onClick=\"js_openFormInNewWindow('tulostakopioform_{$tilaus["laskunro"]}', '', 500); return false;\" value='" . t("Kuittikopio") . "'>";
      echo "</form>";
      echo "</td>";
    }

    echo "<td class='back'>";
    echo "<form id='asiakkaantilaus_{$tilaus["laskunro"]}' action='../raportit/asiakkaantilaukset.php'>";
    echo "<input type='hidden' name='tee' value='NAYTA'>";
    echo "<input type='hidden' name='toim' value='MYYNTI'>";
    echo "<input type='hidden' name='tunnus' value='{$tilaus["tunnus"]}'>";
    echo "<input type='submit' style='min-width: 0px;' onClick=\"js_openFormInNewWindow('asiakkaantilaus_{$tilaus["laskunro"]}', '', 900); return false;\" value='" . t("Näytä") . "'>";
    echo "</form>";
    echo "</td>";

    echo "<td class='back'>";
    echo "<form action='../monistalasku.php'>";
    echo "<input type='hidden' name='tee' value='MONISTA'>";
    echo "<input type='hidden' name='monistettavat[{$tilaus["myyntilaskun_tunnus"]}]' value='HYVITA'>";
    echo "<input type='hidden' name='mistatultiin' value='maksutapahtumaselaus'>";
    echo "<input type='hidden' name='lopetus' value='{$lopetus}'>";
    echo "<input type='submit' style='min-width: 0px;' value='" . t("Korjaa") . "'>";
    echo "</form>";
    echo "</td>";

    echo "<td class='back'>";
    echo "<form action='../monistalasku.php'>";
    echo "<input type='hidden' name='tee' value='MONISTA'>";
    echo "<input type='hidden' name='kklkm' value='1'>";
    echo "<input type='hidden' name='monistettavat[{$tilaus["myyntilaskun_tunnus"]}]' value='MONISTA'>";
    echo "<input type='hidden' name='mistatultiin' value='maksutapahtumaselaus'>";
    echo "<input type='hidden' name='lopetus' value='{$lopetus}'>";
    echo "<input type='submit' style='min-width: 0px;' value='" . t("Monista") . "'>";
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
