<?php

require 'inc/parametrit.inc';

if (!isset($tee)) $tee = '';

if ($tee == "lataa_tiedosto") {
  echo file_get_contents("$pupe_root_polku/dataout/".$filenimi);
  exit;
}

echo "<font class='head'>".t("Arvonlisäveron yhteenvetoilmoitus")."</font><hr><br>";

// Oletusvalinta edellinen kuukausi
if (isset($kohdekausi) and $kohdekausi != "") {
  $default_kausi = $kohdekausi;
}
else {
  $default_kausi = date("m/Y", mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
}

echo "<form method = 'post'  class = 'multisubmit'>";
echo "<input type = 'hidden' name = 'tee' value = 'VSRALVYV'>";

echo "<table>";
echo "<tr>";
echo "<th>".t("Valitse kohdekuukausi")."</th>";
echo "<td>";
echo "<select name = 'kohdekausi'>";

// Näytetään kausivalinnat kaksi kuukautta eteenpäin ja vuosi taaksepäin
for ($i = 0; $i < 15; $i++) {
  $kuukausi = date("m/Y", mktime(0, 0, 0, date("m") - 1 + $i, 1, date("Y")-1));
  $sel = ($kuukausi == $default_kausi) ? " selected" : "";
  echo "<option value = '$kuukausi'$sel>$kuukausi</option>";
}

echo "</select>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "<br>";
echo "<input type='submit' value='".t("Aja")."'>";
echo "</form>";
echo "<br><br>";

if ($tee == "VSRALVYV") {

  // Erotellaan kuukausi ja vuosi
  list($kuukausi, $vuosi) = explode("/", $kohdekausi);

  // Tehdään alku ja loppupäivä
  $alkupvm  = date("Y-m-d", mktime(0, 0, 0, $kuukausi,   1, $vuosi));
  $loppupvm = date("Y-m-d", mktime(0, 0, 0, $kuukausi+1, 0, $vuosi));

  // Haetaan EU-vientilaskujen summat per koodi, ytunnus, maa (Huom, "koodi" on t-funktioitu piirtovaiheessa)
  $query = "SELECT
            if (lasku.kolmikantakauppa != '', 'Kolmikanta', if (tuote.tuotetyyppi = 'K', 'Palvelu', 'Tavara')) koodi,
            lasku.ytunnus,
            if (lasku.maa = '', asiakas.maa, lasku.maa) maa,
            if (lasku.maa = '', 'X', '') asiakkaan_maa,
            max(asiakas.nimi) nimi,
            round(sum(rivihinta), 2) summa,
            count(distinct(lasku.tunnus)) laskuja
            FROM lasku USE INDEX (yhtio_tila_tapvm)
            JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.uusiotunnus = lasku.tunnus AND tilausrivi.tyyppi = 'L')
            JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.tuoteno != '{$yhtiorow["ennakkomaksu_tuotenumero"]}')
            JOIN asiakas ON (asiakas.yhtio = lasku.yhtio AND lasku.liitostunnus = asiakas.tunnus)
            WHERE lasku.yhtio       = '{$kukarow["yhtio"]}'
            AND lasku.tila          = 'U'
            AND lasku.alatila       = 'X'
            AND lasku.tapvm         >= '$alkupvm'
            AND lasku.tapvm         <= '$loppupvm'
            AND lasku.vienti        = 'E'
            and lasku.tilaustyyppi != '9'
            GROUP BY 1, 2, 3, 4";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<table>";

    echo "<tr>";
    echo "<th>".t("Koodi")."</th>";
    echo "<th>".t("Maatunnus")."</th>";
    echo "<th>".t("Ytunnus")."</th>";
    echo "<th>".t("Asiakas")."</th>";
    echo "<th>".t("Myynti")."</th>";
    echo "<th>".t("Laskuja")."</th>";
    echo "</tr>";

    // Yhteissumma
    $summa_yhteensa = 0;

    // Tehdään tietuetta
    $tietue_rivi = 0;
    $tietue_rivitiedot = "";

    // Tähän kerätään raportin data
    $yhteenvetoilmoitus_array = array();

    // Myynnit
    while ($row = mysql_fetch_array($result)) {
      $array_key = $row["koodi"].$row["ytunnus"].$row["maa"];
      $yhteenvetoilmoitus_array[$array_key]["koodi"] = $row["koodi"];
      $yhteenvetoilmoitus_array[$array_key]["ytunnus"] = $row["ytunnus"];
      $yhteenvetoilmoitus_array[$array_key]["maa"] = $row["maa"];
      $yhteenvetoilmoitus_array[$array_key]["asiakkaan_maa"] = $row["asiakkaan_maa"];
      $yhteenvetoilmoitus_array[$array_key]["nimi"] = $row["nimi"];
      $yhteenvetoilmoitus_array[$array_key]["laskuja"] = $row["laskuja"];
      $yhteenvetoilmoitus_array[$array_key]["summa"] = $row["summa"];
      $yhteenvetoilmoitus_array[$array_key]["kale"] = 0;
    }

    // Tavaramyynnin käteisalennukset
    list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, "", "", "fi311", 0, TRUE);

    if (is_resource($ttres)) {
      while ($trow = mysql_fetch_assoc($ttres)) {
        $bruttosumma = round($kakerroinlisa * $trow['bruttosumma'], 2);
        if ($bruttosumma != 0) {
          $array_key = "Tavara".$trow["ytunnus"].$trow["maa"];
          $yhteenvetoilmoitus_array[$array_key]["koodi"] = "Tavara";
          $yhteenvetoilmoitus_array[$array_key]["ytunnus"] = $trow["ytunnus"];
          $yhteenvetoilmoitus_array[$array_key]["maa"] = $trow["maa"];
          $yhteenvetoilmoitus_array[$array_key]["asiakkaan_maa"] = "";
          $yhteenvetoilmoitus_array[$array_key]["nimi"] = $trow["laskunimi"];
          $yhteenvetoilmoitus_array[$array_key]["summa"] += $bruttosumma;
          $yhteenvetoilmoitus_array[$array_key]["laskuja"] += 1;
          $yhteenvetoilmoitus_array[$array_key]["kale"] += 1;
        }
      }
    }

    // Palvelumyynnin käteisalennukset
    list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, "", "", "fi312", 0, TRUE);

    if (is_resource($ttres)) {
      while ($trow = mysql_fetch_assoc($ttres)) {
        $bruttosumma = round($kakerroinlisa * $trow['bruttosumma'], 2);
        if ($bruttosumma != 0) {
          $array_key = "Palvelu".$trow["ytunnus"].$trow["maa"];
          $yhteenvetoilmoitus_array[$array_key]["koodi"] = "Palvelu";
          $yhteenvetoilmoitus_array[$array_key]["ytunnus"] = $trow["ytunnus"];
          $yhteenvetoilmoitus_array[$array_key]["maa"] = $trow["maa"];
          $yhteenvetoilmoitus_array[$array_key]["asiakkaan_maa"] = "";
          $yhteenvetoilmoitus_array[$array_key]["nimi"] = $trow["laskunimi"];
          $yhteenvetoilmoitus_array[$array_key]["summa"] += $bruttosumma;
          $yhteenvetoilmoitus_array[$array_key]["laskuja"] += 1;
          $yhteenvetoilmoitus_array[$array_key]["kale"] += 1;
        }
      }
    }

    // Kolmikantamyynnin käteisalennukset
    list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, "", "", "kolmikanta", 0, TRUE);

    if (is_resource($ttres)) {
      while ($trow = mysql_fetch_assoc($ttres)) {
        $bruttosumma = round($kakerroinlisa * $trow['bruttosumma'], 2);
        if ($bruttosumma != 0) {
          $array_key = "Kolmikanta".$trow["ytunnus"].$trow["maa"];
          $yhteenvetoilmoitus_array[$array_key]["koodi"] = "Kolmikanta";
          $yhteenvetoilmoitus_array[$array_key]["ytunnus"] = $trow["ytunnus"];
          $yhteenvetoilmoitus_array[$array_key]["maa"] = $trow["maa"];
          $yhteenvetoilmoitus_array[$array_key]["asiakkaan_maa"] = "";
          $yhteenvetoilmoitus_array[$array_key]["nimi"] = $trow["laskunimi"];
          $yhteenvetoilmoitus_array[$array_key]["summa"] += $bruttosumma;
          $yhteenvetoilmoitus_array[$array_key]["laskuja"] += 1;
          $yhteenvetoilmoitus_array[$array_key]["kale"] += 1;
        }
      }
    }

    // Sortataan multidimensoinen array. Pitää ensiksi tehdä sortattavista keystä omat arrayt
    $apusort_jarj1 = $apusort_jarj2 = $apusort_jarj3 = array();

    foreach ($yhteenvetoilmoitus_array as $apusort_key => $apusort_row) {
      $apusort_jarj1[$apusort_key] = $apusort_row['koodi'];
      $apusort_jarj2[$apusort_key] = $apusort_row['maa'];
      $apusort_jarj3[$apusort_key] = $apusort_row['ytunnus'];
    }

    // Sortataan taulukko koodi, maa, ytunnus järjestykseen
    array_multisort($apusort_jarj1, SORT_DESC, $apusort_jarj2, SORT_ASC, $apusort_jarj3, SORT_ASC, $yhteenvetoilmoitus_array);

    // Piirretään data ruudulle
    foreach ($yhteenvetoilmoitus_array as $row) {
      echo "<tr class='aktiivi'>";
      echo "<td>".t($row["koodi"])."</td>";
      echo "<td>{$row["maa"]}</td>";
      echo "<td>{$row["ytunnus"]}</td>";
      echo "<td>{$row["nimi"]}</td>";
      echo "<td align='right'>{$row["summa"]}</td>";
      echo "<td align='right'>{$row["laskuja"]}</td>";

      if ($row["maa"] == "") {
        echo "<td class='back'><font class='error'>";
        echo t("VIRHE! Maa puuttuu laskulta sekä asiakkaalta")."!<br>";
        echo t("Riviä ei huomioida yhteissummassa").".<br>";
        echo t("Korjaa asiakkaan tiedot ennen ilmoittamista")."!";
        echo "</font></td>";
      }
      elseif ($row["maa"] != "" and $row["asiakkaan_maa"] == "X") {
        echo "<td class='back'><font class='info'>".t("HUOM: Maa haettu asiakkaan tiedoista")."</font></td>";
      }

      if ($row["kale"] != 0 and $row["kale"] != $row["laskuja"]) {
        echo "<td class='back'><font class='info'>".t("HUOM: Myynnissä on huomioitu %s kassa-alennus(ta)", $kukarow["kieli"], $row["kale"]).".</font></td>";
      }

      if ($row["kale"] != 0 and $row["kale"] == $row["laskuja"]) {
        echo "<td class='back'><font class='info'>".t("HUOM: Kassa-alennus", $kukarow["kieli"], $row["kale"]).".</font></td>";
      }

      echo "</tr>";

      if ($row["maa"] != "") {
        $summa_yhteensa += $row["summa"];

        // Tehdään tietuetta
        $tietue_rivi++;

        // Kauppatapakoodit
        if ($row["koodi"] == "Kolmikanta") {
          $koodi = 3;
        }
        elseif ($row["koodi"] == "Palvelu") {
          $koodi = 4;
        }
        else {
          $koodi = "";
        }

        $ytunnus = sprintf("%012.12s", str_ireplace(array($row["maa"], "-", "_"), "", $row["ytunnus"]));
        $arvo = round($row["summa"] * 100);

        // Rivitiedot
        $tietue_rivitiedot .= "102:{$row["maa"]}\n";  // Maatunnus. Asiakkaan arvonlisäverotunnisteen maatunnusosa.
        $tietue_rivitiedot .= "103:{$ytunnus}\n";    // Asiakkaan arvonlisäverotunniste. Asiakkaan arvonlisäverotunniste ilman maatunnusta.
        $tietue_rivitiedot .= "210:{$arvo}\n";      // Myynnin arvo EU-maihin
        $tietue_rivitiedot .= "104:{$koodi}\n";      // Kauppatapakoodi. Tavaramyynnin koodi on tyhjä. Kolmikantakaupassa koodi on 3. Palvelumyynnin koodi on 4.
        $tietue_rivitiedot .= "009:{$tietue_rivi}\n";  // Toistuvien osatietoryhmien välimerkki: juokseva numero.
      }
    }

    echo "<tr>";
    echo "<th colspan = '4'>".t("Yhteensä")."</th>";
    echo "<td class = 'tumma' align = 'right'>".sprintf("%.2f", $summa_yhteensa)."</th>";
    echo "<th></th>";
    echo "</tr>";

    echo "</table>";
    echo "<br>";

    // Tehdään tietue
    $uytunnus = tulosta_ytunnus($yhtiorow["ytunnus"]);
    $arvo = round($summa_yhteensa * 100);
    $tunniste = substr(md5(date("YmdHis")), 0, 9);

    $tietue  = "000:VSRALVYV\n";          // Tietovirran nimi
    $tietue .= "100:".date("dmY")."\n";        // Saapumispäivä ppkkvvvv. Ilmoituksen arvopäivä eli päivä, jona tiedonkeruupalvelu vastaanotti tiedot.
    $tietue .= "051:".date("H:i:s").":00\n";    // Saapumispäivän kellonaika hh:mm:ss:dd. Ilmoituksen arvopäivän kellonaika, jona tiedonkeruupalvelu vastaanotti tiedot.
    $tietue .= "105:VW\n";              // Vastaanottavan palvelun tunnus, joka sovitaan palvelukohtaisesti.
    // Positio 1: E=Itella, S=TeliaSonera, U=Aditro, N=Logica, K=Koivuniemi, V=Ilmoitin.fi
    // Positio 2: W=Webin kautta syötetty tietue, O=Ohjelmistointegraation tuottama
    $tietue .= "107:{$tunniste}\n";          // Tunniste, jonka tiedonkeruupalvelu muodostaa yksilöimään ilmoituksen
    $tietue .= "010:{$uytunnus}\n";          // Y-tunnus tai Y-tunnus ja siihen liitetty toimipaikkatunnus
    $tietue .= "053:{$kohdekausi}\n";        // Kohdekausi kk/vvvv
    $tietue .= "098:1\n";              // Rahayksikkö, millä tiedot annetaan: euro=1
    $tietue .= "101:{$arvo}\n";            // Koko yhteisömyynnin arvo kohdekautena
    $tietue .= "001:{$tietue_rivi}\n";        // Toistuvien osatietoryhmien lukumäärä ja alkumerkki (= n ryhmää)
    $tietue .= $tietue_rivitiedot;
    $tietue .= "999:1\n";              // Lopputunnus: Tietueen juokseva numero. Verovelvolliskohtainen ilmoitus päättyy.

    $filenimi = "VSRALVYV-$vuosi-$kuukausi-".date("His").".txt";
    file_put_contents("$pupe_root_polku/dataout/$filenimi", $tietue);

    echo "<form method='post' class = 'multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='lataa_tiedosto' value='1'>";
    echo "<input type='hidden' name='kaunisnimi' value='$filenimi'>";
    echo "<input type='hidden' name='filenimi' value='$filenimi'>";
    echo "<input type='submit' name='tallenna' value='".t("Tallenna tiedosto")."'>";
    echo "</form>";
  }
  else {
    echo "<font class='error'>".t("Ei aineistoa valitulla kaudella")."!</font>";
  }
}

require "inc/footer.inc";
