<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 2;

require "../inc/parametrit.inc";

if (!isset($tee)) {
  $tee = "";
}

echo "<font class='head'>".t("Tiliöintien tilit")."</font><hr><br>";

echo t("Haetaan tiliöinnit ja tietueet joilla on virheellinen kirjanpidon tilinumero").".<br>";

// käyttis
echo "<form method='POST'>";
echo "<input type='hidden' name='tee' value='raportoi'>";
echo "<br><input type='submit' value='".t("Aja raportti")."'>";
echo "</form>";
echo "<br><br>";

if ($tee != "") {
  $query = "SELECT distinct tili.tilino t, tiliointi.tilino
            FROM tiliointi
            LEFT JOIN tili ON tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino
            WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
            and tiliointi.korjattu = ''
            and tiliointi.tilino   > 0
            HAVING t IS NULL";
  $result = pupe_query($query);

  while ($tili = mysql_fetch_array($result)) {
    $query = "SELECT tapvm viimeisin, selite
              FROM tiliointi
              WHERE yhtio  = '$kukarow[yhtio]'
              and tilino   = $tili[tilino]
              and korjattu = ''
              ORDER BY tapvm asc
              LIMIT 1";
    $res = pupe_query($query);
    $viimrow = mysql_fetch_array($res);

    echo "<br><font class='error'>Tiliä $tili[tilino] ei ole enää olemassa! Viimeisin tiliöinti $viimrow[viimeisin], $viimrow[selite]</font><br>";
  }

  //Tsekattavat sarakkeet
  $tables   = array();

  $tables["asiakas"][] = "tilino";
  $tables["asiakas"][] = "tilino_eu";
  $tables["asiakas"][] = "tilino_ei_eu";
  $tables["asiakas"][] = "tilino_kaanteinen";
  $tables["asiakas"][] = "tilino_marginaali";
  $tables["asiakas"][] = "tilino_osto_marginaali";
  $tables["asiakas"][] = "tilino_triang";

  $tables["budjetti"][] = "tili";

  $tables["kassalipas"][] = "kassa";
  $tables["kassalipas"][] = "pankkikortti";
  $tables["kassalipas"][] = "luottokortti";
  $tables["kassalipas"][] = "kateistilitys";
  $tables["kassalipas"][] = "kassaerotus";
  $tables["kassalipas"][] = "kateisotto";

  $tables["kuka"][] = "oletustili";

  $tables["tiliointisaanto"][] = "tilino";

  $tables["tiliotesaanto"][] = "tilino";
  $tables["tiliotesaanto"][] = "tilino2";

  $tables["toimi"][] = "tilino";
  $tables["toimi"][] = "tilino_alv";

  $tables["tuote"][] = "tilino";
  $tables["tuote"][] = "tilino_eu";
  $tables["tuote"][] = "tilino_ei_eu";
  $tables["tuote"][] = "tilino_kaanteinen";
  $tables["tuote"][] = "tilino_marginaali";
  $tables["tuote"][] = "tilino_osto_marginaali";
  $tables["tuote"][] = "tilino_triang";

  $tables["tuotteen_alv"][] = "tilino";
  $tables["tuotteen_alv"][] = "tilino_eu";
  $tables["tuotteen_alv"][] = "tilino_ei_eu";
  $tables["tuotteen_alv"][] = "tilino_kaanteinen";
  $tables["tuotteen_alv"][] = "tilino_marginaali";
  $tables["tuotteen_alv"][] = "tilino_osto_marginaali";
  $tables["tuotteen_alv"][] = "tilino_triang";

  $tables["yhtio"][] = "kassa";
  $tables["yhtio"][] = "pankkikortti";
  $tables["yhtio"][] = "luottokortti";
  $tables["yhtio"][] = "kassaerotus";
  $tables["yhtio"][] = "kateistilitys";
  $tables["yhtio"][] = "myynti";
  $tables["yhtio"][] = "myynti_eu";
  $tables["yhtio"][] = "myynti_ei_eu";
  $tables["yhtio"][] = "myynti_kaanteinen";
  $tables["yhtio"][] = "tilino_triang";
  $tables["yhtio"][] = "myynti_marginaali";
  $tables["yhtio"][] = "osto_marginaali";
  $tables["yhtio"][] = "osto_rahti";
  $tables["yhtio"][] = "osto_kulu";
  $tables["yhtio"][] = "osto_rivi_kulu";
  $tables["yhtio"][] = "myyntisaamiset";
  $tables["yhtio"][] = "luottotappiot";
  $tables["yhtio"][] = "factoringsaamiset";
  $tables["yhtio"][] = "konsernimyyntisaamiset";
  $tables["yhtio"][] = "ostovelat";
  $tables["yhtio"][] = "konserniostovelat";
  $tables["yhtio"][] = "valuuttaero";
  $tables["yhtio"][] = "myynninvaluuttaero";
  $tables["yhtio"][] = "kassaale";
  $tables["yhtio"][] = "myynninkassaale";
  $tables["yhtio"][] = "muutkulut";
  $tables["yhtio"][] = "pyoristys";
  $tables["yhtio"][] = "varasto";
  $tables["yhtio"][] = "raaka_ainevarasto";
  $tables["yhtio"][] = "varastonmuutos";
  $tables["yhtio"][] = "raaka_ainevarastonmuutos";
  $tables["yhtio"][] = "varastonmuutos_valmistuksesta";
  $tables["yhtio"][] = "varastonmuutos_epakurantti";
  $tables["yhtio"][] = "varastonmuutos_inventointi";
  $tables["yhtio"][] = "varastonmuutos_rahti";
  $tables["yhtio"][] = "matkalla_olevat";
  $tables["yhtio"][] = "alv";
  $tables["yhtio"][] = "siirtosaamiset";
  $tables["yhtio"][] = "siirtovelka";
  $tables["yhtio"][] = "konsernisaamiset";
  $tables["yhtio"][] = "konsernivelat";
  $tables["yhtio"][] = "selvittelytili";
  $tables["yhtio"][] = "ostolasku_kotimaa_kulu";
  $tables["yhtio"][] = "ostolasku_kotimaa_rahti";
  $tables["yhtio"][] = "ostolasku_kotimaa_vaihto_omaisuus";
  $tables["yhtio"][] = "ostolasku_kotimaa_raaka_aine";
  $tables["yhtio"][] = "ostolasku_eu_kulu";
  $tables["yhtio"][] = "ostolasku_eu_rahti";
  $tables["yhtio"][] = "ostolasku_eu_vaihto_omaisuus";
  $tables["yhtio"][] = "ostolasku_eu_raaka_aine";
  $tables["yhtio"][] = "ostolasku_ei_eu_kulu";
  $tables["yhtio"][] = "ostolasku_ei_eu_rahti";
  $tables["yhtio"][] = "ostolasku_ei_eu_vaihto_omaisuus";
  $tables["yhtio"][] = "ostolasku_ei_eu_raaka_aine";

  $tables["yhtion_toimipaikat"][] = "tilino";
  $tables["yhtion_toimipaikat"][] = "tilino_eu";
  $tables["yhtion_toimipaikat"][] = "tilino_ei_eu";
  $tables["yhtion_toimipaikat"][] = "tilino_kaanteinen";
  $tables["yhtion_toimipaikat"][] = "tilino_marginaali";
  $tables["yhtion_toimipaikat"][] = "tilino_osto_marginaali";
  $tables["yhtion_toimipaikat"][] = "tilino_triang";
  $tables["yhtion_toimipaikat"][] = "toim_alv";

  $tables["yriti"][] = "oletus_kulutili";
  $tables["yriti"][] = "oletus_rahatili";
  $tables["yriti"][] = "oletus_selvittelytili";

  foreach ($tables as $taulu => $kentat) {

    echo "<br><font class='message'>Tarkastetaan taulu $taulu</font><br>";

    foreach ($kentat as $kentta) {
      $query = "SELECT distinct $kentta tilino
                FROM $taulu
                WHERE yhtio  = '$kukarow[yhtio]'
                and $kentta not in ('','0')";
      $haku = pupe_query($query);

      while ($row = mysql_fetch_assoc($haku)) {
        $query = "SELECT tunnus
                  FROM tili
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tilino  = '$row[tilino]'";
        $tarkresr = pupe_query($query);

        if (mysql_num_rows($tarkresr) == 0) {
          echo "<font class='error'>$taulu.$kentta '$row[tilino]' tiliä ei löydy.</font><br>";
        }
      }
    }
  }
}

require "inc/footer.inc";
