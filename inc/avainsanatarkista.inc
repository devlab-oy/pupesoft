<?php

if (!function_exists("avainsanatarkista")) {
  function avainsanatarkista(&$t, $i, $result, $tunnus, &$virhe, $trow) {
    global $kukarow, $yhtiorow, $alias_set, $ulosarray, $varastot_array;

    static $seliteerrori, $tem_kieli, $tem_laji, $tem_nakyvyys, $tem_selite, $seliteind, $tem_selitetark, $tem_selitetark_2, $tem_selitetark_3;

    if (mysql_field_name($result, $i) == "kieli") {
      $tem_kieli = trim($t[$i]);
    }

    if (mysql_field_name($result, $i) == "laji") {
      $tem_laji = trim($t[$i]);

      // Synkattavien avainsanojen lajia ei saa muuttaa, muuten synkkaus menee out of sync
      if (isset($trow["laji"]) and $trow["laji"] != $tem_laji and stripos($yhtiorow["synkronoi"], "avainsana") !== FALSE) {
        $abulisa = preg_match("/(^|,)(avainsana\|*([\|a-zA-Z_\-]*))($|,)/i", $yhtiorow["synkronoi"], $regs);
        $tem_lajit = explode("|", strtolower($regs[3]));

        if (in_array(strtolower($trow["laji"]), $tem_lajit)) {
          $virhe[$i] = t("Synkronoitavien avainsanojen lajia ei saa muuttaa")."!";
        }
      }

      if ($t['laji'] == 'TYOM_TYOKENTAT') {
        $query = "  DESCRIBE tyomaarays";
        $tyomaarays_result = pupe_query($query);

        $onko_input_ok = false;
        $sallitut_kentat = "";
        while ($tyomaarays_kentat = mysql_fetch_assoc($tyomaarays_result)) {
          //dynaaminen kenttä ei saa olla yhtio tai otunnus
          if ($tyomaarays_kentat['Field'] != 'yhtio' and $tyomaarays_kentat['Field'] != 'otunnus') {
            $sallitut_kentat .= $tyomaarays_kentat['Field'] . ', ';
            if ($t['selite'] == $tyomaarays_kentat['Field']) {
              $onko_input_ok = true;
            }
          }
        }
        $sallitut_kentat = substr($sallitut_kentat, 0, -2);

        if (!$onko_input_ok) {
          $virhe[$i] = t("Tyomääräyksen sallituja kenttiä on").":" . $sallitut_kentat;
        }
      }
    }

    if (mysql_field_name($result, $i) == "nakyvyys") {
      $tem_nakyvyys = trim($t[$i]);
    }

    if (mysql_field_name($result, $i) == "selite") {
      $tem_selite = trim($t[$i]);
      $seliteind = $i;

      if ($tem_selite == '') {
        $seliteerrori = 1;
      }

      if ($tem_laji == 'INVASTEPAIKKA') {

        if (preg_match("/\A[\w#äöåÄÖÅ!]{1,5}-[\w#äöåÄÖÅ!]{1,5}-[\w#äöåÄÖÅ!]{1,5}-[\w#äöåÄÖÅ!]{1,5}\z/", $t[$i]) == false) {
          $virhe[$i] = t("Tuotepaikka on virheellinen");
        }
      }

      if ($tem_laji == 'HINNAT_CRON' or $tem_laji == 'TUOTE_EXP_CRON') {

        $date = $t[$i];

        // Huom. preg_match tarkoituksella "== false", koska se palauttaa virheessä 0 tai false
        if (preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $date) == false or strtotime($date) === false) {
          $virhe[$i] = t("Selite on pakollinen ja se pitää olla muodossa vvvv-kk-pp tt:mm:ss");
        }
      }

      if ($tem_laji == 'LABEL') {
        if (trim($tem_selite) == '') {
          $virhe[$i] = t("Selite on pakollinen ja se pitää olla muodossa #123 tai #112233");
        }
        elseif (substr($tem_selite, 0, 1) != '#') {
          $virhe[$i] = t("Selitteen ensimmäinen kirjain pitää olla #");
        }
        elseif (strlen($tem_selite) != 4 and strlen($tem_selite) != 7) {
          $virhe[$i] = t("Selite täytyy olla 4 tai 7 merkkiä pitkä");
        }
      }

      if (strtoupper($tem_laji) == "OSTOTIL_TILTYYP") {

        if (trim($tem_selite) == '') {
          $virhe[$i] = t("Selite on pakollinen");
        }
        elseif (strlen($tem_selite) > 1) {
          $virhe[$i] = t("Selite on liian pitkä, maksimipituus on yksi merkki");
        }
      }

      if (strtoupper($tem_laji) == "ALVULK") {
        //  ALV pitää olla pisteellä, ei pilkulla
        $t[$i] = round((float) str_replace(",", ".", $tem_selite), 2);
      }

      if (strlen($tem_selite) > 3 and $tem_laji == 'Y') {
        $virhe[$i] = t("Selite on liian pitkä, maksimipituus on kolme merkkiä")."!";
      }

      if (strlen($tem_selite) > 1 and $tem_laji == 'ASIAKASLUOKKA') {
        $virhe[$i] = t("Selite on liian pitkä, maksimipituus on yksi merkki")."!";
      }

      if ($tem_laji == "TOIMEHTO" and strlen($tem_selite) != 3) {
        $virhe[$i] = t("Selitteen tulee olla kolme merkkiä pitkä")."!";
      }

      if (in_array($tem_laji, array("TRY", "OSASTO", "PIIRI", "VALMISTUSLINJA"))) {
        if (!is_numeric($tem_selite)) {
          $virhe[$i] = t("Selitteen on oltava numeerinen")."!";
        }
        else {
          $t[$i] = (int) $t[$i];
        }
      }

      if (($tem_laji == 'VAKIOVIESTI' or $tem_laji == 'MAINOSTXT_LASKU' or $tem_laji == 'VAKIOVIESTI_TIL') and trim($tem_selite) == '') {
        $virhe[$i] = t("Selite on pakollinen");
      }

      if ($tem_laji == "PARAMETRI") {
        preg_match_all('/([a-zA-Z_0-9])+/', $tem_selite, $match);

        if ($match[0][0] != $tem_selite) {
          $virhe[$i] = t("Vain aakkoset, numerot ja alaviivat ovat sallittuja merkkejä");
        }
      }

      if (strtoupper($tem_laji) == "HUOLTOP_EMAIL" and trim($tem_selite) == '') {
        $virhe[$i] = t("Selite on pakollinen");
      }

      if ($tem_selite != '' and ($tem_laji == 'TRY' or $tem_laji == 'OSASTO' or $tem_laji == 'DPAVAINSANALAJI' or $tem_laji == 'LITETY_TKIRJAST')) {
        $query = "SELECT tunnus
                  FROM avainsana
                  WHERE yhtio  = '$kukarow[yhtio]'
                  AND kieli    = '$tem_kieli'
                  AND laji     = '$tem_laji'
                  AND selite   = '$tem_selite'
                  and tunnus  != '$tunnus'";
        $results = pupe_query($query);

        if (mysql_num_rows($results) > 0) {
          $virhe[$seliteind] = $virhe[$i] = t("Avainsana annetuilla tiedoilla löytyy kannasta. Duplikaatit ovat kiellettyjä!");
        }
      }

      if ($tem_laji == "SALDOVAHVISTUS") {
        $query = "SELECT *
                  FROM avainsana
                  WHERE avainsana.yhtio  = '{$kukarow['yhtio']}'
                  AND selite             = '{$tem_selite}'
                  AND laji               = '{$tem_laji}'
                  AND tunnus            != '{$tunnus}'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $virhe[$i] = t("Saldovahvistuksen nimi pitää olla uniikki");
        }
      }

      if ($tem_laji == "VEHT_RAHTI") {
        $query = "SELECT avainsana.*
                  FROM avainsana
                  WHERE avainsana.yhtio  = '{$kukarow['yhtio']}'
                  AND avainsana.laji = '{$tem_laji}'
                  AND avainsana.selite = '{$tem_selite}'
                  AND avainsana.tunnus != '{$tunnus}'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $virhe[$i] = t("Vaihtoehtoisen rahtituotenumeron pitää olla uniikki");
        }

        $query = "SELECT tuote.*
                  FROM tuote
                  WHERE tuote.yhtio = '{$kukarow['yhtio']}'
                  AND tuote.tuoteno = '{$tem_selite}'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 0) {
          $virhe[$i] = t("Vaihtoehtoisen rahtituotenumeron tulee löytyä järjestelmästä");
        }
      }

      if ($tem_laji == "ASIAKASHINNASTO") {
        $query = "SELECT *
                  FROM avainsana
                  WHERE avainsana.yhtio  = '{$kukarow['yhtio']}'
                  AND selite             = '{$tem_selite}'
                  AND laji               = '{$tem_laji}'
                  AND tunnus            != '{$tunnus}'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $virhe[$i] = t("Asiakashinnaston parametri löytyy jo");
        }
      }
    }

    if ($tem_laji == 'POSTEN_TKOODI') {
      if (mysql_field_name($result, $i) == "selite" and trim($t[$i]) != '') {
        $query = "SELECT *
                  FROM avainsana
                  WHERE avainsana.yhtio = '{$kukarow['yhtio']}'
                  AND avainsana.selite  = '{$tem_selite}'
                  AND avainsana.laji    = '{$tem_laji}'
                  AND tunnus           != '{$tunnus}'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $virhe[$i] = t("Sinulla voi olla vain yksi tämän lajinen avainsana per selite");
        }
      }

      if ((mysql_field_name($result, $i) == "selite" or mysql_field_name($result, $i) == "selitetark") and trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto sarakkeessa on pakollinen");
      }
    }

    if (mysql_field_name($result, $i) == "selitetark") {
      $tem_selitetark = trim($t[$i]);

      // Yritetään asettaa oletusverokanta
      if (strtoupper($tem_laji) == "ALV" and trim($t[$i]) != "") {
        $query = "SELECT tunnus
                  FROM avainsana
                  WHERE yhtio     = '{$kukarow["yhtio"]}'
                  AND laji        = 'ALV'
                  AND selitetark != ''
                  AND tunnus     != '$tunnus'";
        $results = pupe_query($query);

        if (mysql_num_rows($results) > 0) {
          $virhe[$seliteind] = $virhe[$i] = t("Sinulla ei voi olla kuin yksi oletusverokanta!");
        }
      }

      if ($seliteerrori == 1 and trim($t[$i]) == '' and $tem_laji != 'VAKIOVIESTI' and $tem_laji != 'MAINOSTXT_LASKU' and $tem_laji != 'VAKIOVIESTI_TIL' and $tem_laji != 'HINNAT_CRON' and $tem_laji != 'TUOTE_EXP_CRON' and $tem_laji != 'UNIFAUN_REF') {
        $virhe[$i] = t("Selite ja selitetark ei voi olla tyhjiä")."!";
      }

      if ($tem_laji == "VARASTORYHMA" and !is_array($varastot_array) and count($varastot_array) == 0) {
        $virhe[$i] = t("Et valinnut yhtään varastoa")."!";
      }
      elseif ($tem_laji == 'VARASTORYHMA') {
        if (is_array($varastot_array) and count($varastot_array) > 0) {
          $varastot = '';
          foreach ($varastot_array as $varasto_apu) {
            $varastot .= "$varasto_apu,";
          }
          $varastot = substr($varastot, 0, -1);
          $t[$i] = $varastot;
        }
      }
    }

    if (mysql_field_name($result, $i) == "selitetark_2") {
      $tem_selitetark_2 = trim($t[$i]);

      if (strtoupper($tem_laji) == "INVEN_LAJI" and $tem_selitetark_2 != "") {

        $query = "SELECT tunnus
                  FROM tili
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tilino  = '{$tem_selitetark_2}'";
        $tili_chk_res = pupe_query($query);

        if (mysql_num_rows($tili_chk_res) == 0) $virhe[$i] = t("Tilinumero ei ole sallittu")."!";
      }

      if (strtoupper($tem_laji) == 'VALMISTUSLINJA') {
        if (!is_numeric($tem_selitetark_2) or $tem_selitetark_2 < 0) {
          $virhe[$i] = t("Valmistuslinjan päiväkapasiteetin (selitetark_2) on oltava suurempi tai yhtäsuuri kuin 0")."!";
        }
      }
    }

    if (mysql_field_name($result, $i) == "selitetark_3") {
      $tem_selitetark_3 = trim($t[$i]);
    }

    if ($tem_laji == "SAHKTILTUN" and
      (mysql_field_name($result, $i) == "selite" or
        mysql_field_name($result, $i) == "selitetark" or
        mysql_field_name($result, $i) == "selitetark_2" or
        mysql_field_name($result, $i) == "selitetark_3" or
        mysql_field_name($result, $i) == "selitetark_4" or
        mysql_field_name($result, $i) == "selitetark_5")) {

      if (trim($t[$i]) == "") {
        $virhe[$i] = t("Tieto pakollinen")."!";
      }
    }

    if (mysql_field_name($result, $i) == 'tunnus') {

      $st2lisa = "selitetark_2  = '$tem_selitetark_2'";
      $st3lisa = "selitetark_3  = '$tem_selitetark_3'";

      if (empty($tem_selitetark_2)) {
        $st2lisa .= " OR selitetark_2 IS NULL";
      }

      if (empty($tem_selitetark_3)) {
        $st3lisa .= " OR selitetark_3 IS NULL";
      }

      $query = "SELECT kieli, selite, laji
                FROM avainsana
                WHERE yhtio       = '$kukarow[yhtio]'
                AND kieli         = '$tem_kieli'
                AND laji          = '$tem_laji'
                AND nakyvyys      = '$tem_nakyvyys'
                AND selite        = '$tem_selite'
                AND selitetark    = '$tem_selitetark'
                AND ({$st2lisa})
                AND ({$st3lisa})
                and tunnus       != '$tunnus'";
      $results = pupe_query($query);

      if (mysql_num_rows($results) > 0) {
        $virhe[$seliteind] = $virhe[$i] = t("Avainsana annetuilla tiedoilla löytyy kannasta. Duplikaatit ovat kiellettyjä!");
      }

      if (empty($virhe) and $tem_laji == 'MAARYHMA' and $tem_selite != $trow['selite'] and $trow['selite'] != '') {
        $query = "UPDATE tuote SET
                  vienti      = '{$tem_selite}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND vienti  = '{$trow['selite']}'";
        $updres = pupe_query($query);
      }
    }

    if ($tem_laji == 'EXTRANET_H') {
      if (mysql_field_name($result, $i) == "laji") {
        $query = "SELECT *
                  FROM avainsana
                  WHERE avainsana.yhtio = '{$kukarow['yhtio']}'
                  AND avainsana.laji    = '{$tem_laji}'
                  AND kieli             = '$tem_kieli'
                  AND tunnus           != '{$tunnus}'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $virhe[$i] = t("Sinulla voi olla vain yksi tämän lajinen avainsana");
        }
      }

      if ((mysql_field_name($result, $i) == "selite") and trim($t[$i]) == '') {
        $virhe[$i] = t("Tieto sarakkeessa on pakollinen");
      }
    }

    $_arr = array("RAJATOIMIPAIKAT", "MAARATOIMPAIKKA");

    if (in_array(strtoupper($tem_laji), $_arr)) {
      if (mysql_field_name($result, $i) == "selite") {
        $selite = trim($tem_selite);

        if (empty($selite)) {
          $virhe[$i] = t("Selite on pakollinen");
        }
      }
      elseif (mysql_field_name($result, $i) == "selitetark") {
        $selitetark = trim($tem_selitetark);

        if (empty($selitetark)) {
          $virhe[$i] = t("Selitetark on pakollinen");
        }
      }
    }
  }
}
