<?php

$tee = isset($_POST["tee"]) ? $_POST["tee"] : "";
$kaunisnimi = isset($_POST["kaunisnimi"]) ? $_POST["kaunisnimi"] : "";

if ($tee == 'lataa_tiedosto') {
  $lataa_tiedosto = 1;
}

if ($kaunisnimi != '') {
  $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

require "inc/parametrit.inc";

if (isset($tuotetiedot_exceliin)) {
  setcookie("reseptin_tuotetiedot_exceliin", true);
  $myos_tuotetiedot_exceliin = true;
}
elseif (isset($tuotetiedot_nappulaa_klikattu)) {
  setcookie("reseptin_tuotetiedot_exceliin", false, time() - 3600);
  $myos_tuotetiedot_exceliin = false;
}
elseif ($reseptin_tuotetiedot_exceliin) {
  $myos_tuotetiedot_exceliin = true;
}
else {
  $myos_tuotetiedot_exceliin = false;
}

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

if ($livesearch_tee == "TUOTEHAKU") {
  livesearch_tuotehaku();
  exit;
}

echo "<script src='js/tuoteperhe.js'></script>";

// Enaboidaan ajax kikkare
enable_ajax();

echo "<font class='head'>";

if ($toim == "PERHE") {
  echo t("Tuoteperheet")." (".t("Vain isätuotetta voi tilata").")";
  $hakutyyppi = "P";
}
elseif ($toim == "OSALUETTELO") {
  echo t("Tuotteen osaluettelo")." (".t("Kaikkia tuotteita voi tilata").")";
  $hakutyyppi = "O";
}
elseif ($toim == "TUOTEKOOSTE") {
  echo t("Tuotteen koosteluettelo")." (".t("Vain lapsituotteita voi tilata").")";
  $hakutyyppi = "V";
}
elseif ($toim == "LISAVARUSTE") {
  echo t("Tuotteen lisävarusteet");
  $hakutyyppi = "L";
}
elseif ($toim == "VSUUNNITTELU") {
  echo t("Samankaltaisten tuotteiden määrittely");
  $hakutyyppi = "S";
}
elseif ($toim == "RESEPTI") {
  echo t("Tuotereseptit");
  $hakutyyppi = "R";

  $resepti_kentat_result = t_avainsana("RESEPTI_KENTAT");
  $resepti_kentat = array();

  while ($resepti_kentta = mysql_fetch_assoc($resepti_kentat_result)) {
    array_push($resepti_kentat, $resepti_kentta);
  }
}
else {
  echo t("Tuntematon toiminto");
  exit;
}

echo "</font><hr>";

if ($toim == "PERHE") {
  // Haetaan viimeisin valinta keksistä
  $keijo = isset($_COOKIE["pupesoft_tuoteperhe"]) ? $_COOKIE["pupesoft_tuoteperhe"] : "";
  // Tsekboksi valituksi jos viimeisin valinta oli checked
  if ($keijo == "ohitetaan") {
    $chk_ohita_kerays = "CHECKED";
  }
}

if ($tee == "KOPIOI") {

  if ($kop_isatuo == "") {
    echo "<br><br>";
    echo "<table>";
    echo "  <form method='post' action='tuoteperhe.php' name='valinta' autocomplete='off'>
          <input type='hidden' name='toim' value='$toim'>
          <input type='hidden' name='tee' value='KOPIOI'>
          <input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";

    echo "<tr><th>";

    if ($toim == "PERHE") {
      echo t("Syötä isä jolle perhe kopioidaan");
    }
    elseif ($toim == "LISAVARUSTE") {
      echo t("Syötä tuote jolle lisävarusteet kopioidaan");
    }
    elseif ($toim == "OSALUETTELO") {
      echo t("Syötä tuote jolle osaluettelo kopioidaan");
    }
    elseif ($toim == "TUOTEKOOSTE") {
      echo t("Syötä tuote jolle tuotekooste kopioidaan");
    }
    elseif ($toim == "VSUUNNITTELU") {
      echo t("Syötä tuote jolle samankaltaisuus kopioidaan");
    }
    else {
      echo t("Syötä valmiste jolle resepti kopioidaan");
    }

    echo "</th>";

    echo "<td>";
    echo livesearch_kentta("valinta", "TUOTEHAKU", "kop_isatuo", 140, $kop_isatuo, 'X');
    echo "</td>";

    foreach ($kop_tuoteno as $_i => $tuoteno) {
      echo "<input type='hidden' name='kop_tuoteno[$_i]' value='$kop_tuoteno[$_i]'>";
      echo "<input type='hidden' name='kop_kerroin[$_i]' value='$kop_kerroin[$_i]'>";
      echo "<input type='hidden' name='kop_hinkerr[$_i]' value='$kop_hinkerr[$_i]'>";
      echo "<input type='hidden' name='kop_alekerr[$_i]' value='$kop_alekerr[$_i]'>";
      echo "<input type='hidden' name='kop_fakta[$_i]' value='$kop_fakta[$_i]'>";

      if ($toim == "PERHE") {
        echo "<input type='hidden' name='kop_ohita_kerays[$_i]' value='$kop_ohita_kerays[$_i]'>";
        echo "<input type='hidden' name='kop_ei_nayteta[$_i]' value='$kop_ei_nayteta[$_i]'>";
        echo "<input type='hidden' name='kop_hintatyyppi[$_i]' value='$kop_hintatyyppi[$_i]'>";
      }
    }

    echo "<td><input type='submit' value='".t("Kopioi")."'></td></tr>";
    echo "</form>";
    echo "</table>";
  }
  else {

    $query = "SELECT tuote.*,
              tuoteperhe.isatuoteno isa
              FROM tuote
              LEFT JOIN tuoteperhe ON (tuote.yhtio = tuoteperhe.yhtio
               AND tuote.tuoteno     = tuoteperhe.isatuoteno
               AND tuoteperhe.tyyppi = '$hakutyyppi')
              WHERE tuote.tuoteno    = '$kop_isatuo'
              AND tuote.yhtio        = '$kukarow[yhtio]'
              AND tuote.status       NOT IN ('P','X')
              AND tuoteperhe.isatuoteno is null";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      foreach ($kop_tuoteno as $kop_index => $tuoteno) {
        if ($tuoteno != $kop_isatuo) {

          $insert_lisa = "";

          if ($toim == "PERHE") {
            $insert_lisa = "ohita_kerays = '{$kop_ohita_kerays[$kop_index]}',";
            $insert_lisa .= "ei_nayteta = '{$kop_ei_nayteta[$kop_index]}',";
            $insert_lisa .= "hintatyyppi = '{$kop_hintatyypp[$kop_index]}',";
          }

          $query = "INSERT into  tuoteperhe set
                    isatuoteno   = '$kop_isatuo',
                    tuoteno      = '$kop_tuoteno[$kop_index]',
                    kerroin      = '$kop_kerroin[$kop_index]',
                    hintakerroin = '$kop_hinkerr[$kop_index]',
                    alekerroin   = '$kop_alekerr[$kop_index]',
                    #rivikommentti  = '$kop_rivikom[$kop_index]',
                    fakta        = '$kop_fakta[$kop_index]',
                    {$insert_lisa}
                    yhtio        = '$kukarow[yhtio]',
                    laatija      = '$kukarow[kuka]',
                    luontiaika   = now(),
                    tyyppi       = '$hakutyyppi'";
          $result = pupe_query($query);
        }
      }

      echo "<br><br><font class='message'>";

      if ($toim == "PERHE") {
        echo t("Tuoteperhe kopioitu");
      }
      elseif ($toim == "LISAVARUSTE") {
        echo t("Lisävarusteet kopioitu");
      }
      elseif ($toim == "OSALUETTELO") {
        echo t("Osaluettelo kopioitu");
      }
      elseif ($toim == "TUOTEKOOSTE") {
        echo t("Tuotekooste kopioitu");
      }
      elseif ($toim == "VSUUNNITTELU") {
        echo t("Samankaltaisuus kopioitu");
      }
      else {
        echo t("Resepti kopioitu");
      }

      echo "!</font><br>";

      $hakutuoteno = $kop_isatuo;
      $isatuoteno  = $kop_isatuo;
      $tee      = "";
    }
    else {
      echo "<br><br><font class='error'>";

      if ($toim == "PERHE") {
        echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo perhe");
      }
      elseif ($toim == "LISAVARUSTE") {
        echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo lisävarusteita");
      }
      elseif ($toim == "OSALUETTELO") {
        echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo osaluettelo");
      }
      elseif ($toim == "TUOTEKOOSTE") {
        echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo tuotekooste");
      }
      else {
        echo t("Tuotetta ei löydy järjestelmästä tai tuotteella on jo resepti");
      }

      echo "!</font><br>";
      $tee = "";
    }
  }
}

if ($tee != "KOPIOI") {

  $formi  = 'performi';
  $kentta = 'hakutuoteno';

  if ($hakutuoteno2 != "") $hakutuoteno = trim($hakutuoteno2);

  echo "<br>";
  echo "<table>";
  echo "<form method='post' action='tuoteperhe.php' name='$formi' autocomplete='off'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<tr>";

  if ($toim == "PERHE") {
    echo "<th>".t("Etsi tuoteperhettä")."</th>";
  }
  elseif ($toim == "LISAVARUSTE") {
    echo "<th>".t("Etsi tuotteen lisävausteita")."</th>";
  }
  elseif ($toim == "OSALUETTELO") {
    echo "<th>".t("Etsi osaluetteloa")."</th>";
  }
  elseif ($toim == "TUOTEKOOSTE") {
    echo "<th>".t("Etsi tuotekoostetta")."</th>";
  }
  elseif ($toim == "VSUUNNITTELU") {
    echo "<th>".t("Etsi samankaltaisia")."</th>";
  }
  else {
    echo "<th>".t("Etsi tuotereseptiä")."</th>";
  }

  echo "<td>".livesearch_kentta($formi, "TUOTEHAKU", "hakutuoteno", 210)."</td>";
  echo "<tr><th>".t("Rajaa hakumäärää")."</th>";
  echo "<td>";

  if (!isset($limitti)) $limitti = 20;

  if ($limitti == 20) {
    $sel0 = "selected";
  }
  elseif ($limitti == 100) {
    $sel1 = "selected";
  }
  elseif ($limitti == 1000) {
    $sel2 = "selected";
  }
  elseif ($limitti == 5000) {
    $sel3 = "selected";
  }
  elseif ($limitti == "U") {
    $sel5 = "selected";
  }
  else {
    $sel4 = "selected";
  }

  echo "<select name='limitti'>";
  echo "<option value='20' $sel0>".t("20 ensimmäistä")."</option>";
  echo "<option value='100' $sel1>".t("100 ensimmäistä")."</option>";
  echo "<option value='1000' $sel2>".t("1000 ensimmäistä")."</option>";
  echo "<option value='5000' $sel3>".t("5000 ensimmäistä")."</option>";
  echo "<option value='' $sel4>".t("Koko lista")."</option>";
  echo "<option value='U' $sel5>".t("Tuoreimmasta vanhimpaan näyttäen koko lista")."</option>";
  echo "</select>";
  echo "</td>";
  echo "<td class='back'><input type='submit' value='".t("Jatka")."'></td>";
  echo "</table></form>";
}

if ($tee == 'LISAA' and $oikeurow['paivitys'] == '1') {

  echo "<br>";

  if (trim($isatuoteno) != trim($tuoteno)) {
    $ok = 1;
    $isatuoteno = trim($isatuoteno);
    $tuoteno = trim($tuoteno);

    $query  = "SELECT *
               from tuoteperhe
               where isatuoteno = '$isatuoteno'
               and yhtio        = '$kukarow[yhtio]'
               and tyyppi       = '$hakutyyppi'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      //katsotaan ettei tämä isa/lapsi kombinaatio ole jo olemassa
      $query  = "SELECT *
                 from tuoteperhe
                 where isatuoteno = '$isatuoteno'
                 and tuoteno      = '$tuoteno'
                 and yhtio        = '$kukarow[yhtio]'
                 and tyyppi       = '$hakutyyppi'";
      $result = pupe_query($query);

      //Jos tunnus on erisuuri kuin tyhjä niin ollan päivittämässä olemassa olevaaa kombinaatiota
      if (mysql_num_rows($result) > 0 and $tunnus == "") {

        if ($yhtiorow["tuoteperhe_kasittely"] == "") {
          echo "<font class='error'>";
          echo t("Tämä tuoteperhekombinaatio on jo olemassa, sitä ei voi lisätä toiseen kertaan");
          echo "</font>";
          echo "<br>";
          $ok = 0;
        }
        else {
          $_count = mysql_num_rows($result) + 1;
          $_str = "HUOM: Tämä tuoteperhekombinaatio on jo olemassa,";
          $_str .= " laspsituote löytyy tästä perheestä %s kertaa";

          echo "<font class='message'>";
          echo t($_str, "", $_count);
          echo "</font>";
          echo "<br>";
        }
      }
    }

    if ($ok == 1) {
      //tarkistetaan tuotteiden olemassaolo
      $error = '';

      $query   = "SELECT *
                  FROM tuote
                  WHERE tuoteno = '$isatuoteno'
                  AND yhtio     = '$kukarow[yhtio]'";
      $res = pupe_query($query);
      $isatrow = mysql_fetch_assoc($res);

      if (mysql_num_rows($res) == 0) {
        $error .= "<font class='error'>";
        $error .= t("Tuotenumero");
        $error .= "{$isatuoteno} ";
        $error .= t("ei ole tuoterekisterissä, riviä ei voida lisätä");
        $error .= "</font><br>";
      }

      $query = "SELECT *
                FROM tuote
                WHERE tuoteno = '$tuoteno'
                AND yhtio     = '$kukarow[yhtio]'";
      $res = pupe_query($query);
      $laptrow = mysql_fetch_assoc($res);

      if (mysql_num_rows($res) == 0) {
        $error .= "<font class='error'>";
        $error .= t("Tuotenumero");
        $error .= " {$tuoteno} ";
        $error .= t("ei ole tuoterekisterissä, riviä ei voida lisätä");
        $error .= "</font><br>";
      }

      if ($hintatyyppi == 'I' and $laptrow['kehahin'] == 0) {
        $error .= "<font class='error'>";
        $error .= t("Tuotteella");
        $error .= " {$tuoteno} ";
        $error .= t("ei ole keskihankintahintaa, riviä ei voida lisätä");
        $error .= "</font><br>";
      }

      if ($error == '') {
        $kerroin      = str_replace(',', '.', $kerroin);
        $hintakerroin = str_replace(',', '.', $hintakerroin);
        $alekerroin   = str_replace(',', '.', $alekerroin);

        //lisätään rivi...
        if ($kerroin == '') {
          $kerroin = '1';
        }

        if ($hintakerroin == '') {
          if ($yhtiorow["pura_osaluettelot"] != "" and $toim == "OSALUETTELO") {
            $hintakerroin = $laptrow['myyntihinta'];
          }
          else {
            $hintakerroin = '1';
          }
        }

        if ($alekerroin == '') {
          $alekerroin = '1';
        }

        if ($tunnus == "") {
          $query = "  INSERT INTO ";
          $postq = " , laatija  = '$kukarow[kuka]',
                       luontiaika  = now()";
        }
        else {
          $query = "   UPDATE ";
          $postq = "   , muuttaja = '$kukarow[kuka]',
                         muutospvm = now()
                         WHERE tunnus = '$tunnus' ";
        }

        $querylisa = "";

        if ($toim == "PERHE") {
          $querylisa = "ohita_kerays = '{$ohita_kerays}',";
        }
        elseif ($toim == "RESEPTI") {
          $querylisa = "";

          foreach ($resepti_kentat as $resepti_kentta) {
            $querylisa .= "{$resepti_kentta["selite"]} = '{${$resepti_kentta["selite"]}}',";
          }
        }

        $query  .= "  tuoteperhe set
                isatuoteno     = '{$isatrow['tuoteno']}',
                tuoteno        = '{$laptrow['tuoteno']}',
                kerroin        = '$kerroin',
                hintakerroin   = '$hintakerroin',
                alekerroin     = '$alekerroin',
                #rivikommentti = '$rivikommentti',
                yhtio          = '$kukarow[yhtio]',
                tyyppi         = '$hakutyyppi',
                {$querylisa}
                hintatyyppi    = '$hintatyyppi',
                ei_nayteta     = '$ei_nayteta'
                $postq";
        $result = pupe_query($query);

        $tunnus = "";
        $tee   = "";
      }
      else {
        echo "$error<br>";
        $tee = "";
      }
    }
  }
  else {
    echo t("Tuoteperheen isä ei voi olla sekä isä että lapsi samassa perhessä")."!<br>";
  }
  $tee = "";
}

if ($tee == 'POISTA' and $oikeurow['paivitys'] == '1') {
  $isatuoteno = trim($isatuoteno);

  // Varmistetaan, että faktat ei mene rikki
  $query = "SELECT
            group_concat(distinct if(fakta = '', null, fakta)) fakta,
            group_concat(distinct if(fakta2 = '', null, fakta2)) fakta2,
            group_concat(distinct if(omasivu = '', null, omasivu)) omasivu
            FROM tuoteperhe
            WHERE yhtio    = '$kukarow[yhtio]'
            and tyyppi     = '$hakutyyppi'
            and isatuoteno = '$isatuoteno'";
  $result = pupe_query($query);
  $fakrow = mysql_fetch_array($result);

  $fakta   = $fakrow["fakta"];
  $fakta2  = $fakrow["fakta2"];
  $omasivu = $fakrow["omasivu"];
  $tee     = "TALLENNAFAKTA";

  //poistetaan rivi..
  $query  = "DELETE from tuoteperhe
             where tunnus = '$tunnus'
             and yhtio    = '$kukarow[yhtio]'
             and tyyppi   = '$hakutyyppi'";
  $result = pupe_query($query);
  $tunnus = '';
}

if ($tee == 'TALLENNAFAKTA' and $oikeurow['paivitys'] == '1') {
  $isatuoteno = trim($isatuoteno);

  $query = "UPDATE tuoteperhe SET
            fakta          = '',
            fakta2         = '',
            omasivu        = ''
            WHERE yhtio    = '$kukarow[yhtio]'
            and tyyppi     = '$hakutyyppi'
            and isatuoteno = '$isatuoteno'";
  $result = pupe_query($query);

  $query = "UPDATE tuoteperhe SET
            fakta          = '$fakta',
            fakta2         = '$fakta2',
            omasivu        = '$omasivu'
            WHERE yhtio    = '$kukarow[yhtio]'
            and tyyppi     = '$hakutyyppi'
            and isatuoteno = '$isatuoteno'
            ORDER BY isatuoteno, tuoteno
            LIMIT 1";
  $result = pupe_query($query);

  if (isset($ei_nayteta)) {
    $query = "UPDATE tuoteperhe
              SET ei_nayteta = '$ei_nayteta'
              WHERE yhtio    = '$kukarow[yhtio]'
              and tyyppi     = '$hakutyyppi'
              and isatuoteno = '$isatuoteno'";
    $result = pupe_query($query);
  }

  // Päivitetään hintatyyppi tuoteperheelle
  if (isset($hintatyyppi)) {

    // Hinta määritellään isätuotteelle
    if ($hintatyyppi == 'I') {
      $query = "SELECT tuoteperhe.tunnus
                FROM tuoteperhe
                join tuote using(yhtio, tuoteno)
                WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                and tuoteperhe.tyyppi     = '$hakutyyppi'
                and tuoteperhe.isatuoteno = '$isatuoteno'
                AND tuote.kehahin         = 0";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        echo "<br>";
        echo "<font class='error'>";
        echo t("Kaikilla tuotteilla ei ole keskihankintahintaa, ei voida käyttää tätä hintatyyppiä.");
        echo "</font>";

        $hintatyyppi = '';
      }
    }

    // "Hinta määritellään isätuotteelle, lapsituote ei tarvitse keskihankintahintaa
    if ($hintatyyppi == 'L') {
      $query = "SELECT count(tuote.tunnus) as yht, sum(if(tuote.kehahin = 0, 1, 0)) as nolla
                FROM tuoteperhe
                JOIN tuote using(yhtio, tuoteno)
                WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                AND tuoteperhe.tyyppi     = '$hakutyyppi'
                AND tuoteperhe.isatuoteno = '$isatuoteno'";
      $result = pupe_query($query);
      $row = mysql_fetch_assoc($result);

      if ($row['yht'] === $row['nolla']) {
        echo "<br>";
        echo "<font class='error'>";
        echo t("Yhdelläkään lapsituotteella ei ole keskihankintahintaa, ei voida käyttää tätä hintatyyppiä.");
        echo "</font>";

        $hintatyyppi = '';
      }
    }

    $query = "UPDATE tuoteperhe
              SET hintatyyppi = '$hintatyyppi'
              WHERE yhtio    = '$kukarow[yhtio]'
              and tyyppi     = '$hakutyyppi'
              and isatuoteno = '$isatuoteno'";
    $result = pupe_query($query);
  }

  echo "<br>";
  echo "<font class='error'>".t("Tiedot tallennettu")."!</font>";
  echo "<br><br>";

  $tee = '';
}

if (($hakutuoteno != '' or $isatuoteno != '') and $tee == "") {

  $lisa = "";
  $tchk = "";

  $isatuoteno  = trim($isatuoteno);
  $hakutuoteno = trim($hakutuoteno);

  if ($isatuoteno != '') {
    $lisa = " and isatuoteno = '$isatuoteno'";
    $tchk = $isatuoteno;
  }
  else {
    $lisa = " and isatuoteno = '$hakutuoteno'";
    $tchk = $hakutuoteno;
  }

  $query  = "SELECT tuoteno
             FROM tuote
             WHERE yhtio = '$kukarow[yhtio]'
             AND tuoteno = '$tchk'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {

    $query  = "SELECT distinct isatuoteno
               FROM tuoteperhe
               WHERE yhtio = '$kukarow[yhtio]'
               AND tyyppi  = '$hakutyyppi'
               $lisa
               ORDER BY isatuoteno, tuoteno";
    $result = pupe_query($query);

    // Haettavaa tuotetta ei löytynyt
    if (mysql_num_rows($result) == 0 and ($hakutuoteno != '' or $isatuoteno != '')) {

      echo "<form method='post' action='tuoteperhe.php' name='lisaa' autocomplete='off'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='tunnus' value='$zrow[tunnus]'>";
      echo "<input type='hidden' name='tee' value='LISAA'>";
      echo "<input type='hidden' name='isatuoteno' value='$hakutuoteno'>";
      echo "<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";

      if ($toim == "PERHE") {
        echo "<br><font class='error'>";
        echo t("Tuotenumeroa") . " $hakutuoteno " . t("ei löydy mistään tuoteperheestä");
        echo "!</font><br>";

        echo "<br><font class='head'>";
        echo t("Perusta uusi tuoteperhe tuotteelle");
        echo ": $hakutuoteno</font><hr><br>";
      }
      elseif ($toim == "LISAVARUSTE") {
        echo "<br><font class='error'>";
        echo t("Lisävarusteita ei ole määritelty tuotteelle") . " $hakutuoteno!";
        echo "</font><br>";

        echo "<br><font class='head'>";
        echo t("Lisää lisävaruste tuotteelle") . ": $hakutuoteno";
        echo "</font><hr><br>";
      }
      elseif ($toim == "OSALUETTELO") {
        echo "<br><font class='error'>";
        echo t("Tuotenumeroa") . " $hakutuoteno " . t("ei löydy mistään osaluettelosta");
        echo "!</font><br>";

        echo "<br><font class='head'>";
        echo t("Perusta uusi osaluettelo tuotteelle") . ": $hakutuoteno";
        echo "</font><hr><br>";
      }
      elseif ($toim == "TUOTEKOOSTE") {
        echo "<br><font class='error'>";
        echo t("Tuotekoostetta ei ole määritelty tuotteelle") . " $hakutuoteno!";
        echo "</font><br>";

        echo "<br><font class='head'>";
        echo t("Lisää tuotekooste tuotteelle") . ": $hakutuoteno";
        echo "</font><hr><br>";
      }
      elseif ($toim == "VSUUNNITTELU") {
        echo "<br><font class='error'>";
        echo t("Samankaltaisuutta ei ole määritelty tuotteelle") . " $hakutuoteno!";
        echo "</font><br>";

        echo "<br><font class='head'>";
        echo t("Lisää samankaltaisuus tuotteelle") . ": $hakutuoteno";
        echo "</font><hr><br>";
      }
      else {
        echo "<br><font class='error'>";
        echo t("Tuotenumeroa") . " $hakutuoteno " . t("ei löydy mistään tuotereseptistä") . "!";
        echo "</font><br>";

        echo "<br><font class='head'>";
        echo t("Lisää raaka-aine valmisteelle") . ": $hakutuoteno";
        echo "</font><hr><br>";
      }

      echo "<table>";
      echo "<tr>";
      echo "<th>".t("Tuoteno")."</th>";
      echo "<th>".t("Määräkerroin")."</th>";

      if ($toim == "PERHE") {
        echo "<th>".t("Hintakerroin")."</th>";
        echo "<th>".t("Alennuskerroin")."</th>";

        echo "<th>".t("Ohita Keräys")."</th>";
        echo "<th>".t("Ei näytetä")."</th>";
      }

      if ($toim == "RESEPTI") {
        for ($i = 0; $i < count($resepti_kentat); $i++) {
          echo "<th>".t($resepti_kentat[$i]["selitetark"])."</th>";
        }
      }

      echo "<td class='back'></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>".livesearch_kentta("lisaa", "TUOTEHAKU", "tuoteno", 140, '', 'X')."</td>";

      if ($toim == "VSUUNNITTELU") {
        echo "<td class='text-right'><input type='hidden' name='kerroin' value='1'>1</td>";
      }
      else {
        echo "<td><input type='text' name='kerroin' size='20'></td>";

        if ($toim == "PERHE") {
          echo "<td><input type='text' name='hintakerroin' size='20'></td>";
          echo "<td><input type='text' name='alekerroin' size='20'></td>";

          echo "<td><input type='checkbox' name='ohita_kerays' {$chk_ohita_kerays}></td>";
          echo "<td><input type='checkbox' name='ei_nayteta' value='E' {$chk_ei_nayteta}></td>";
          echo "<input type='hidden' name='tallenna_keksiin' value='joo'>";
        }

        if ($toim == "RESEPTI") {
          for ($i = 0; $i < count($resepti_kentat); $i++) {
            echo "<td><input type='text' name='{$resepti_kentat[$i]["selite"]}' size='10'></td>";
          }
        }

      }

      echo "<td class='back'><input type='submit' value='".t("Lisää rivi")."'></td>";
      echo "</tr>";
      echo "</table>";

      echo "</form>";
    }
    // Haulla löytyi yksi tuote
    elseif (mysql_num_rows($result) == 1) {

      $row = mysql_fetch_array($result);
      $isatuoteno  = $row['isatuoteno'];

      if ($oikeurow['paivitys'] == '1') {
        echo "<form method='post' action='tuoteperhe.php' autocomplete='off'>";
        echo "<input type='hidden' name='toim' value='$toim'>";
        echo "<input type='hidden' name='tee' value='TALLENNAFAKTA'>";
        echo "<input type='hidden' name='tunnus' value='$prow[tunnus]'>";
        echo "<input type='hidden' name='isatuoteno' value='$isatuoteno'>";
        echo "<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
      }

      //isätuotteen checkki
      $error = "";

      $query = "SELECT *
                FROM tuote
                WHERE tuoteno = '$isatuoteno'
                AND yhtio     = '$kukarow[yhtio]'";
      $res = pupe_query($query);

      if (mysql_num_rows($res) == 0) {
        echo "<font class='error'>".t("Tuote ei enää rekisterissä")."!</font><br>";
      }
      else {
        $isarow = mysql_fetch_array($res);
      }

      echo "<br>";
      echo "<table>";
      echo "<tr>";

      if ($toim == "PERHE") {
        echo "<th>".t("Tuoteperheen isätuote")."</th>";
      }
      elseif ($toim == "LISAVARUSTE") {
        echo "<th>".t("Tuotenumero")."</th>";
      }
      elseif ($toim == "OSALUETTELO") {
        echo "<th>".t("Tuotenumero")."</th>";
      }
      elseif ($toim == "TUOTEKOOSTE") {
        echo "<th>".t("Tuotenumero")."</th>";
      }
      elseif ($toim == "VSUUNNITTELU") {
        echo "<th>".t("Tuotenumero")."</th>";
      }
      else {
        echo "<th>".t("Tuotereseptin valmiste")."</th>";
      }

      echo "<td>$isatuoteno - $isarow[nimitys]</td>";
      echo "</tr>";

      if ($toim == "PERHE") {
        $query = "SELECT *
                  FROM tuoteperhe
                  WHERE yhtio    = '$kukarow[yhtio]'
                  AND tyyppi     = '$hakutyyppi'
                  AND isatuoteno = '$isatuoteno'
                  ORDER BY isatuoteno, tuoteno
                  LIMIT 1";
        $ressu = pupe_query($query);
        $faktarow = mysql_fetch_array($ressu);
        $hintatyyppi = $faktarow['hintatyyppi'];

        echo "<tr>";
        echo "<th>".t("Esitysmuoto")."</th>";
        echo "<td>";

        if ($faktarow["ei_nayteta"] == "") {
          $sel1 = "SELECTED";
          if ($oikeurow['paivitys'] != '1') echo t("Kaikki rivit näytetään");
        }
        elseif ($faktarow["ei_nayteta"] == "E") {
          $sel2 = "SELECTED";
          if ($oikeurow['paivitys'] != '1') echo t("Lapsirivejä ei näytetä");
        }

        if ($oikeurow['paivitys'] == '1') {
          echo "<select name='ei_nayteta'>";
          echo "<option value='' $sel1>".t("Kaikki rivit näytetään")."</option>";
          echo "<option value='E' $sel2>".t("Lapsirivejä ei näytetä")."</option>";
          echo "</select>";
        }

        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>".t("Hinnoittelu")."</th>";
        echo "<td>";

        if ($faktarow["hintatyyppi"] == "") {
          $sel_hinta1 = "SELECTED";
          if ($oikeurow['paivitys'] != '1') echo t("Hinta summataan isä- ja lapsituotteilta");
        }
        elseif ($faktarow["hintatyyppi"] == "I") {
          $sel_hinta2 = "SELECTED";
          if ($oikeurow['paivitys'] != '1') echo t("Hinta määritellään isätuotteelle");
        }
        elseif ($faktarow["hintatyyppi"] == "L") {
          $sel_hinta3 = "SELECTED";
          if ($oikeurow['paivitys'] != '1') echo t("Hinta määritellään isätuotteelle, lapsituote ei tarvitse keskihankintahintaa");
        }

        if ($oikeurow['paivitys'] == '1') {
          echo "<select name='hintatyyppi'>";
          echo "<option value='' $sel_hinta1>".t("Hinta summataan isä- ja lapsituotteilta")."</option>";
          echo "<option value='I' $sel_hinta2>".t("Hinta määritellään isätuotteelle")."</option>";
          echo "<option value='L' $sel_hinta3>".t("Hinta määritellään isätuotteelle, lapsituote ei tarvitse keskihankintahintaa")."</option>";
          echo "</select>";
        }

        echo "</td>";
        echo "</tr>";
      }

      $query = "SELECT omasivu
                FROM tuoteperhe
                WHERE yhtio     = '$kukarow[yhtio]'
                AND tyyppi      = '$hakutyyppi'
                AND isatuoteno  = '$isatuoteno'
                AND omasivu    != ''
                ORDER BY isatuoteno, tuoteno
                LIMIT 1";
      $ressu = pupe_query($query);
      $faktarow = mysql_fetch_array($ressu);

      if ($toim == "RESEPTI") {

        echo "<tr>";
        echo "<th>".t("Reseptin tulostus")."</th>";

        echo "<td>";

        if ($faktarow['omasivu'] != 'X') {
          $sel1 = "SELECTED";
          $sel2 = "";
          if ($oikeurow['paivitys'] != '1') echo t("Resepti tulostetaan normaalisti");
        }
        else {
          $sel1 = "";
          $sel2 = "SELECTED";
          if ($oikeurow['paivitys'] != '1') echo t("Resepti tulostetaan omalle sivulle");
        }

        if ($oikeurow['paivitys'] == '1') {
          echo "<select name='omasivu'>";
          echo "<option value='' $sel1>".t("Resepti tulostetaan normaalisti")."</option>";
          echo "<option value='X' $sel2>".t("Resepti tulostetaan omalle sivulle")."</option>";
          echo "</select>";
        }

        echo "</td>";
        echo "</tr>";
      }

      echo "<tr>";

      if ($toim == "PERHE") {
        echo "<th colspan='2'>".t("Tuoteperheen faktat")."</th>";
      }
      elseif ($toim == "LISAVARUSTE") {
        echo "<th colspan='2'>".t("Lisävarusteiden faktat")."</th>";
      }
      elseif ($toim == "OSALUETTELO") {
        echo "<th colspan='2'>".t("Osaluettelon faktat")."</th>";
      }
      elseif ($toim == "TUOTEKOOSTE") {
        echo "<th colspan='2'>".t("Tuotekoosteen faktat")."</th>";
      }
      elseif ($toim == "VSUUNNITTELU") {
        echo "<th colspan='2'>".t("Samankaltaisuuden faktat")."</th>";
      }
      else {
        echo "<th colspan='2'>".t("Reseptin faktat")."</th>";
      }

      $query = "SELECT fakta
                FROM tuoteperhe
                WHERE yhtio    = '$kukarow[yhtio]'
                and tyyppi     = '$hakutyyppi'
                and isatuoteno = '$isatuoteno'
                and trim(fakta) != ''
                ORDER BY LENGTH(fakta) desc
                LIMIT 1";
      $ressu = pupe_query($query);
      $faktarow = mysql_fetch_array($ressu);

      echo "</tr>";
      echo "<tr>";
      echo "<td colspan='2'>";

      if ($oikeurow['paivitys'] == '1') {
        echo "<textarea cols='80' rows='5' name='fakta'>{$faktarow["fakta"]}</textarea>";
      }
      else {
        echo "$faktarow[fakta]";
      }

      echo "</td>";
      echo "</tr>";

      if ($toim == "RESEPTI") {
        $query = "SELECT fakta2
                  FROM tuoteperhe
                  WHERE yhtio    = '$kukarow[yhtio]'
                  and tyyppi     = '$hakutyyppi'
                  and isatuoteno = '$isatuoteno'
                  and trim(fakta2) != ''
                  ORDER BY LENGTH(fakta2) desc
                  LIMIT 1";
        $ressu = pupe_query($query);
        $faktarow = mysql_fetch_array($ressu);

        echo "<tr>";
        echo "<th colspan='2'>".t("Yhdistämisen lisätiedot")."</th>";
        echo "</tr>";
        echo "<tr>";
        echo "<td colspan='2'>";

        if ($oikeurow['paivitys'] == '1') {
          echo "<textarea cols='80' rows='7' name='fakta2'>{$faktarow["fakta2"]}</textarea>";
        }
        else {
          echo "$faktarow[fakta2]";
        }

        echo "</td>";
        echo "</tr>";
      }

      echo "</table>";

      if ($oikeurow['paivitys'] == '1') {
        echo "<br>";
        echo "<input type='submit' value='".t("Tallenna")."'>";
      }

      echo "</form>";
      echo "<br><br>";

      include 'inc/pupeExcel.inc';

      $worksheet    = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi    = 0;
      $excelsarake = 0;
      $worksheet->writeString($excelrivi, $excelsarake++, t("Isätuote"));

      echo "<table>";
      echo "<tr>";

      if ($toim == "PERHE") {

        echo "<th>".t("Isätuote")."</th>";
        echo "<th>".t("Nimitys")."</th>";
        echo "<th></th>";

        if ($hintatyyppi == '') {
          echo "<th></th>";
          echo "<th></th>";
        }

        echo "<th>".t("Myyntihinta")."</th>";
        echo "<th>".t("Kehahin")."</th>";
        echo "<th>".t("Kehahin")."</th>";
        echo "<th></th>";
        echo "<th></th>";
        echo "</tr><tr>";

        echo "<td>$isarow[tuoteno]</th>";
        echo "<td>$isarow[nimitys]</th>";
        echo "<td></th>";

        if ($hintatyyppi == '') {
          echo "<td></th>";
          echo "<td></th>";
        }

        echo "<td class='text-right'>".round($isarow["myyntihinta"], $yhtiorow["hintapyoristys"])."</th>";
        echo "<td class='text-right'>". (float) $isarow["kehahin"]."</th>";
        echo "<td class='text-right'>". (float) $isarow["kehahin"]."</th>";
        echo "<td></th>";
        echo "<td></th>";
        echo "</tr><tr>";
        echo "<td class='back'><br></td>";
        echo "</tr><tr>";
        echo "<th>".t("Lapset")."</th>";
        echo "<th>".t("Nimitys")."</th>";
        echo "<th>".t("Määräkerroin")."</th>";

        if ($hintatyyppi == '') {
          echo "<th>".t("Hintakerroin")."</th>";
          echo "<th>".t("Alennuskerroin")."</th>";
        }

        echo "<th>".t("Myyntihinta * Hintakerroin")."</th>";
        echo "<th>".t("Kehahin")."</th>";
        echo "<th>".t("Kehahin * Kerroin")."</th>";
        echo "<th>".t("Ohita keräys")."</th>";
        echo "<th>".t("Ei näytetä")."</th>";

        $worksheet->writeString($excelrivi, $excelsarake++, t("Lapset"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Määräkerroin"));

        if ($hintatyyppi == '') {
          $worksheet->writeString($excelrivi, $excelsarake++, t("Hintakerroin"));
          $worksheet->writeString($excelrivi, $excelsarake++, t("Alennuskerroin"));
        }

        $worksheet->writeString($excelrivi, $excelsarake++, t("Myyntihinta*Hintakerroin"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kehahin"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kehahin*Kerroin"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Ohita keräys"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Ei näytetä"));
      }
      elseif ($toim == "LISAVARUSTE") {
        echo "<th>".t("Lisävarusteet")."</th>";
        echo "<th>".t("Nimitys")."</th>";
        echo "<th>".t("Kehahin")."</th>";
        echo "<th>".t("Kehahin*Kerroin")."</th>";

        $worksheet->writeString($excelrivi, $excelsarake++, t("Lisävarusteet"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kehahin"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kehahin*Kerroin"));
      }
      elseif ($toim == "OSALUETTELO") {
        echo "<th>".t("Osaluettelot")."</th>";
        echo "<th>".t("Nimitys")."</th>";
        echo "<th>".t("Kerroin")."</th>";
        echo "<th>".t("Hinta")."</th>";
        echo "<th>".t("Kehahin")."</th>";
        echo "<th>".t("Kehahin*Kerroin")."</th>";

        $worksheet->writeString($excelrivi, $excelsarake++, t("Osaluettelot"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kerroin"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Hinta"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kehahin*Kerroin"));
      }
      elseif ($toim == "TUOTEKOOSTE") {
        echo "<th>".t("Tuotekoosteet")."</th>";
        echo "<th>".t("Nimitys")."</th>";
        echo "<th>".t("Kerroin")."</th>";
        echo "<th>".t("Kehahin")."</th>";
        echo "<th>".t("Kehahin*Kerroin")."</th>";

        $worksheet->writeString($excelrivi, $excelsarake++, t("Tuotekoosteet"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kerroin"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kehahin*Kerroin"));
      }
      elseif ($toim == "VSUUNNITTELU") {
        echo "<th>".t("Samankaltaisuudet")."</th>";
        echo "<th>".t("Nimitys")."</th>";
        echo "<th>".t("Kerroin")."</th>";

        $worksheet->writeString($excelrivi, $excelsarake++, t("Samankaltaisuudet"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kerroin"));
      }
      else {
        echo "<th>".t("Raaka-aineet")."</th>";
        echo "<th class='full-width'>".t("Nimitys")."</th>";
        echo "<th>".t("Määrä")."-<br>".t("kerroin")."</th>";
        echo "<th>".t("Yksikkö")."</th>";

        foreach ($resepti_kentat as $resepti_kentta) {
          echo "<th>{$resepti_kentta["selitetark"]}</th>";
        }

        echo "<th>".t("Ostohinta")."</th>";
        echo "<th>".t("Kehahin")."</th>";
        echo "<th>".t("Kehahin*Kerroin")."</th>";

        $worksheet->writeString($excelrivi, $excelsarake++, t("Raaka-aineet"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));

        if ($myos_tuotetiedot_exceliin) {
          $worksheet->writeString($excelrivi, $excelsarake++, t("Malli"));
          $worksheet->writeString($excelrivi, $excelsarake++, t("Mallitarkenne"));
          $worksheet->writeString($excelrivi, $excelsarake++, t("Tuotemerkki"));
        }

        $worksheet->writeString($excelrivi, $excelsarake++, t("Määräkerroin"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Yksikkö"));

        foreach ($resepti_kentat as $resepti_kentta) {
          $worksheet->writeString($excelrivi, $excelsarake++, $resepti_kentta["selitetark"]);
        }

        $worksheet->writeString($excelrivi, $excelsarake++, t("Ostohinta"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Ostoh.val"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kehahin"));
        $worksheet->writeString($excelrivi, $excelsarake++, t("Kehahin*Kerroin"));
      }

      echo "<td class='back'></td>";
      echo "</tr>";

      $excelrivi++;

      $query = "SELECT *
                FROM tuoteperhe
                WHERE isatuoteno = '$row[isatuoteno]'
                AND yhtio        = '$kukarow[yhtio]'
                AND tyyppi       = '$hakutyyppi'
                ORDER BY isatuoteno, tuoteno";
      $res = pupe_query($query);

      $resyht = 0;
      $reshikeyht = 0;
      $myyntihintayht = 0;

      $kop_index   = 0;
      $kop_tuoteno = array();
      $kop_kerroin = array();
      $kop_hinkerr = array();
      $kop_alekerr = array();
      $kop_rivikom = array();
      $kop_fakta   = array();
      $kop_ohita_kerays = array();
      $kop_ei_nayteta = array();
      $kop_hintatyyppi = array();

      if ($oikeurow['paivitys'] == '1' and $tunnus == "") {
        echo "<form method='post' action='tuoteperhe.php' name='lisaa' autocomplete='off'>";
        echo "<input type='hidden' name='toim' value='$toim'>";
        echo "<input type='hidden' name='tee' value='LISAA'>";
        echo "<input type='hidden' name='isatuoteno' value='$row[isatuoteno]'>";
        echo "<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
        echo "<input type='hidden' name='hintatyyppi' value='$hintatyyppi'>";

        echo "<tr>";
        echo "<td>".livesearch_kentta("lisaa", "TUOTEHAKU", "tuoteno", "", '', 'X')."</td>";

        // Nimitys
        echo "<td></td>";

        if ($toim == "PERHE") {

          echo "<td><input type='text' name='kerroin' size='10'></td>";

          if ($hintatyyppi == '') {
            echo "<td><input type='text' name='hintakerroin' size='10'></td>";
            echo "<td><input type='text' name='alekerroin' size='10'></td>";
          }

          echo "<td></td>";
          echo "<td></td>";
          echo "<td></td>";
          echo "<td><input type='checkbox' name='ohita_kerays' {$chk_ohita_kerays}></td>";
          echo "<td><input type='checkbox' name='ei_nayteta' value='E' {$chk_ei_nayteta}></td>";

          echo "<input type='hidden' name='tallenna_keksiin' value='joo'>";
        }
        elseif ($toim == "LISAVARUSTE") {
          echo "<td></td>";
          echo "<td></td>";
        }
        elseif ($toim == "OSALUETTELO") {
          echo "<td><input type='text' name='kerroin' size='10'></td>";
          echo "<td><input type='text' name='hintakerroin' size='10'></td>";
          echo "<td></td>";
          echo "<td></td>";
        }
        elseif ($toim == "TUOTEKOOSTE") {
          echo "<td><input type='text' name='kerroin' size='10'></td>";
          echo "<td></td>";
          echo "<td></td>";
        }
        elseif ($toim == "VSUUNNITTELU") {
          echo "<td class='text-right'><input type='hidden' name='kerroin' value='1'/>1</td>";
        }
        elseif ($toim == "RESEPTI") {
          echo "<td><input type='text' name='kerroin' size='10'></td>";
          echo "<td></td>";

          for ($i = 0; $i < count($resepti_kentat); $i++) {
            echo "<td><input type='text' name='{$resepti_kentat[$i]["selite"]}' size='10'></td>";
          }

          echo "<td></td>";
          echo "<td></td>";
          echo "<td></td>";

          echo "<input type='hidden' name='tallenna_keksiin' value='joo'>";
        }

        echo "<td class='back'>";
        echo "<input type='submit' value='".t("Lisää")."'>";
        echo "</td>";
        echo "</tr>";
        echo "</form>";
      }

      while ($prow = mysql_fetch_array($res)) {
        //Tarkistetaan löytyyko tuote enää rekisteristä
        $query = "SELECT * from tuote where tuoteno='$prow[tuoteno]' and yhtio='$kukarow[yhtio]'";
        $res1   = pupe_query($query);

        if (mysql_num_rows($res1)==0) {
          $error="<font class='error'>(".t("Tuote ei enää rekisterissä")."!)</font>";
        }
        else {
          $error = "";
          $tuoterow = mysql_fetch_array($res1);

          //Tehdään muuttujat jotta voidaan tarvittaessa kopioida resepti
          $kop_tuoteno[$kop_index] = $prow['tuoteno'];
          $kop_kerroin[$kop_index] = $prow['kerroin'];
          $kop_hinkerr[$kop_index] = $prow['hintakerroin'];
          $kop_alekerr[$kop_index] = $prow['alekerroin'];
          $kop_fakta[$kop_index]   = $prow['fakta'];

          if ($toim == "PERHE") {
            $kop_ohita_kerays[$kop_index] = $prow['ohita_kerays'];
            $kop_ei_nayteta[$kop_index] = $prow['ei_nayteta'];
            $kop_hintatyyppi[$kop_index] = $prow['hintatyyppi'];
          }

          $kop_index++;
        }

        $lapsiyht    = $tuoterow['kehahin'] * $prow['kerroin'];
        $resyht     += $lapsiyht;
        $reshikeyht += $prow['kerroin'] * $prow['hintakerroin'];

        $style = array();

        if ($toim == "RESEPTI" and $tuoterow["status"] == "P") {
          $style["strike"] = true;
          $del             = " del";
        }
        else {
          $del = "";
        }

        $excelsarake = 0;
        $worksheet->writeString($excelrivi, $excelsarake++, $prow["isatuoteno"], $style);

        if ($tunnus != $prow["tunnus"]) {
          echo "<tr class='aktiivi{$del}'>";

          echo "<td>$prow[tuoteno] $error</td>";
          echo "<td>".t_tuotteen_avainsanat($tuoterow, 'nimitys')."</td>";

          $worksheet->writeString($excelrivi, $excelsarake++, $prow["tuoteno"], $style);
          $worksheet->writeString($excelrivi, $excelsarake++, $tuoterow["nimitys"], $style);

          if ($myos_tuotetiedot_exceliin) {
            $worksheet->writeString($excelrivi, $excelsarake++, $tuoterow["malli"], $style);
            $worksheet->writeString($excelrivi, $excelsarake++, $tuoterow["mallitarkenne"], $style);
            $worksheet->writeString($excelrivi, $excelsarake++, $tuoterow["tuotemerkki"], $style);
          }

          if ($toim != "LISAVARUSTE") {
            echo "<td class='text-right'>" . (float) $prow["kerroin"] . "</td>";
            $worksheet->writeNumber($excelrivi, $excelsarake++, $prow["kerroin"], $style);
          }

          if ($toim == "PERHE") {
            if ($hintatyyppi == '') {
              $query = "SELECT myyntihinta
                        FROM tuote
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tuoteno = '{$prow['tuoteno']}'";
              $lapsituoteres = pupe_query($query);
              $lapsituoterow = mysql_fetch_assoc($lapsituoteres);

              $lapsituote_myyntihinta = $lapsituoterow['myyntihinta'] * $prow['kerroin'] * $prow['hintakerroin'];
              $myyntihintayht += $lapsituote_myyntihinta;

              echo "<td class='text-right'>{$prow['hintakerroin']}</td>";
              echo "<td class='text-right'>{$prow['alekerroin']}</td>";
              echo "<td class='text-right'>{$lapsituote_myyntihinta}</td>";

              $worksheet->writeNumber($excelrivi, $excelsarake++, $prow["hintakerroin"], $style);
              $worksheet->writeNumber($excelrivi, $excelsarake++, $prow["alekerroin"], $style);
              $worksheet->writeNumber($excelrivi, $excelsarake++, $lapsituote_myyntihinta, $style);
            }
            else {
              echo "<td class='text-right'></td>";

              $worksheet->writeString($excelrivi, $excelsarake++, "", $style);
            }
          }

          if ($toim == "OSALUETTELO") {
            echo "<td class='text-right'>$prow[hintakerroin]</td>";
            $worksheet->writeNumber($excelrivi, $excelsarake++, $prow["hintakerroin"], $style);
          }

          if ($toim == "RESEPTI") {
            echo "<td align='left'>$tuoterow[yksikko]</td>";
            $worksheet->writeString($excelrivi, $excelsarake++, $tuoterow["yksikko"], $style);

            foreach ($resepti_kentat as $resepti_kentta) {
              echo "<td>{$prow[$resepti_kentta["selite"]]}</td>";
              $worksheet->writeString($excelrivi, $excelsarake++, $prow[$resepti_kentta["selite"]], $style);
            }

            $query = "SELECT
                      tuotteen_toimittajat.liitostunnus,
                      toimi.oletus_valkoodi,
                      toimi.ytunnus,
                      if(jarjestys = 0, 9999, jarjestys) sorttaus
                      FROM tuotteen_toimittajat
                      LEFT JOIN toimi
                      ON (toimi.yhtio = tuotteen_toimittajat.yhtio
                      AND toimi.tunnus                 = tuotteen_toimittajat.liitostunnus)
                      WHERE tuotteen_toimittajat.yhtio = '{$kukarow["yhtio"]}'
                      AND tuotteen_toimittajat.tuoteno = '{$tuoterow["tuoteno"]}'
                      ORDER BY sorttaus
                      LIMIT 1";

            $ttrow = pupe_query($query);
            $ttrow = mysql_fetch_assoc($ttrow);

            $query = "SELECT kurssi
                      FROM valuu
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND nimi    = '{$ttrow['oletus_valkoodi']}'
                      ORDER BY tunnus DESC
                      LIMIT 1";

            $valuurow = pupe_query($query);
            $valuurow = mysql_fetch_assoc($valuurow);

            $params = array(
              'liitostunnus'  => $ttrow['liitostunnus'],
              'valkoodi'      => $ttrow['oletus_valkoodi'],
              'ytunnus'       => $ttrow['ytunnus'],
              'vienti_kurssi' => $valuurow['kurssi']
            );

            $ostohintatiedot = alehinta_osto($params, $tuoterow, 1, '', '', array());
            $ostohinta       = hintapyoristys(hinta_kuluineen($tuoterow['tuoteno'], $ostohintatiedot[0]));
            $valuutta        = $ostohintatiedot[3];

            echo "<td class='text-right'>{$ostohinta} {$valuutta}</td>";

            $worksheet->writeNumber($excelrivi, $excelsarake++, $ostohinta, $style);
            $worksheet->writeString($excelrivi, $excelsarake++, $valuutta, $style);
          }

          if ($toim != "VSUUNNITTELU") {
            echo "<td class='text-right'>" . (float) $tuoterow["kehahin"] . "</td>";
            echo "<td class='text-right'>".round($lapsiyht, 6)."</td>";
            $worksheet->writeNumber($excelrivi, $excelsarake++, $tuoterow["kehahin"], $style);
            $worksheet->writeNumber($excelrivi, $excelsarake++, round($lapsiyht, 6), $style);
          }

          if ($toim == "PERHE") {

            if (isset($prow['ohita_kerays']) and trim($prow['ohita_kerays']) != '') {
              $chk_ohita_kerays = t("Kyllä");
            }
            else {
              $chk_ohita_kerays = t("Ei");
            }

            if (isset($prow['ei_nayteta']) and trim($prow['ei_nayteta']) != '') {
              $chk_ei_nayteta = t("Ei näytetä");
            }
            else {
              $chk_ei_nayteta = t("Näytetään");
            }

            echo "<td>{$chk_ohita_kerays}</td>";
            echo "<td>{$chk_ei_nayteta}</td>";

            $worksheet->writeString($excelrivi, $excelsarake++, $chk_ohita_kerays, $style);
            $worksheet->writeString($excelrivi, $excelsarake++, $chk_ei_nayteta, $style);
          }

          $excelrivi++;

          echo "<td class='back'>";

          if ($oikeurow['paivitys'] == '1') {
            echo "<form method='post' action='tuoteperhe.php' autocomplete='off'>";
            echo "<input type='hidden' name='toim' value='$toim'>";
            echo "<input type='hidden' name='tunnus' value='$prow[tunnus]'>";
            echo "<input type='hidden' name='isatuoteno' value='$isatuoteno'>";
            echo "<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
            echo "<input type='hidden' name='hintatyyppi' value='$hintatyyppi'>";
            echo "<input type='submit' value='".t("Muuta")."'>";
            echo "</form>";
          }

          echo "</td>";
          echo "<td class='back'>";

          if ($oikeurow['paivitys'] == '1') {
            echo "<form method='post' action='tuoteperhe.php' autocomplete='off'>";
            echo "<input type='hidden' name='toim' value='$toim'>";
            echo "<input type='hidden' name='tee' value='POISTA'>";
            echo "<input type='hidden' name='tunnus' value='$prow[tunnus]'>";
            echo "<input type='hidden' name='isatuoteno' value='$isatuoteno'>";
            echo "<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
            echo "<input type='hidden' name='hintatyyppi' value='$hintatyyppi'>";
            echo "<input type='submit' value='".t("Poista")."'>";
            echo "</form>";
          }

          echo "</td>";
          echo "</tr>";
        }
        elseif ($tunnus == $prow["tunnus"]) {
          $query  = "SELECT tuoteperhe.*,
                     tuote.nimitys,
                     tuote.yksikko
                     FROM tuoteperhe
                     INNER JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio
                       AND tuote.tuoteno    = tuoteperhe.tuoteno)
                     WHERE tuoteperhe.yhtio = '$kukarow[yhtio]'
                     and tuoteperhe.tunnus  = '$tunnus'
                     and tuoteperhe.tyyppi  = '$hakutyyppi'";
          $zresult = pupe_query($query);
          $zrow = mysql_fetch_array($zresult);

          echo "<form method='post' action='tuoteperhe.php' autocomplete='off'>";
          echo "<input type='hidden' name='toim' value='$toim'>";
          echo "<input type='hidden' name='tunnus' value='$zrow[tunnus]'>";
          echo "<input type='hidden' name='tee' value='LISAA'>";
          echo "<input type='hidden' name='isatuoteno' value='$zrow[isatuoteno]'>";
          echo "<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";
          echo "<input type='hidden' name='hintatyyppi' value='$hintatyyppi'>";

          echo "<tr>";
          echo "<td><input type='text' name='tuoteno' size='20' value='$zrow[tuoteno]'></td>";
          echo "<td>{$zrow["nimitys"]}</td>";

          if ($toim != "LISAVARUSTE" and $toim != "VSUUNNITTELU") {
            echo "<td><input type='text' name='kerroin' size='10' value='$zrow[kerroin]'></td>";
          }

          if ($toim == "RESEPTI") {
            echo "<td>{$zrow["yksikko"]}</td>";

            foreach ($resepti_kentat as $resepti_kentta) {
              echo "<td>";
              echo "<input type='text'
                           name='{$resepti_kentta["selite"]}'
                           value='{$prow[$resepti_kentta["selite"]]}'>";
              echo "</td>";
            }
          }

          if ($toim == "PERHE" and $hintatyyppi == '') {
            echo "<td>";
            echo "<input type='text' name='hintakerroin' size='10' value='$zrow[hintakerroin]'>";
            echo "</td>";

            echo "<td>";
            echo "<input type='text' name='alekerroin' size='10' value='$zrow[alekerroin]'>";
            echo "</td>";
          }

          if ($toim == "OSALUETTELO") {
            echo "<td>";
            echo "<input type='text' name='hintakerroin' size='10' value='$zrow[hintakerroin]'>";
            echo "</td>";
          }

          if ($toim == "VSUUNNITTELU") {
            echo "<td class='text-right'>1</td>";
          }

          if ($toim != "VSUUNNITTELU") {
            echo "<td></td>";
            echo "<td style='text-align: right;'>$tuoterow[kehahin]</td>";
            echo "<td></td>";

          }

          if ($toim == "PERHE") {
            $chk_ohita_kerays = "";
            $chk_ei_nayteta = "";

            if (isset($zrow['ohita_kerays']) and trim($zrow['ohita_kerays']) != '') {
              $chk_ohita_kerays = " checked";
            }

            if (isset($zrow['ei_nayteta']) and trim($zrow['ei_nayteta']) != '') {
              $chk_ei_nayteta = " checked";
            }

            echo "<td>";
            echo "<input type='checkbox' name='ohita_kerays' {$chk_ohita_kerays}>";
            echo "<input type='hidden' name='tallenna_keksiin' value='joo'>";
            echo "</td>";

            echo "<td><input type='checkbox' name='ei_nayteta' value='E' {$chk_ei_nayteta}></td>";
          }

          if ($toim == "RESEPTI") {
            echo "<td></td>";
          }

          echo "<td class='back'>";

          if ($oikeurow['paivitys'] == '1') {
            echo "<input type='submit' value='".t("Päivitä")."'>";
          }

          echo "</td>";
          echo "</tr>";
          echo "</form>";
        }
      }

      if ($tunnus == "") {
        echo "<tr>";
        if ($toim == "PERHE") {
          if ($hintatyyppi == '') {
            echo "<td class='back' colspan='3'></td>";
          }
          else {
            echo "<td class='back'></td>";
          }
        }
        elseif ($toim == "LISAVARUSTE") {
          echo "<td class='back'></td>";
        }
        elseif ($toim == "OSALUETTELO") {
          echo "<td class='back' colspan='2'></td>";
        }
        elseif ($toim == "TUOTEKOOSTE") {
          echo "<td class='back' colspan='3'></td>";
        }
        elseif ($toim == "VSUUNNITTELU") {

        }
        else {
          $colspan = 4;
          if (!empty($resepti_kentat)) {
            $colspan += count($resepti_kentat);
          }
          echo "<td class='back' colspan='{$colspan}'></td>";
        }

        if ($toim != "VSUUNNITTELU") {

          echo "<th colspan='2' class='text-right'>".t("Yhteensä").":</th>";

          if ($toim == "OSALUETTELO") {
            $_yhteensa = sprintf('%.2f', round($reshikeyht, 6));
            echo "<td class='tumma text-right'>{$_yhteensa}</td>";
          }
          elseif ($toim == "PERHE") {
            $_yhteensa = sprintf('%.2f', round($myyntihintayht, 6));
            echo "<td class='tumma text-right'>{$_yhteensa}</td>";
            echo "<td class='tumma'></td>";
          }

          $_yhteensa = round($resyht, 6);
          echo "<td class='tumma text-right'>{$_yhteensa}</td>";
        }

        echo "</tr>";

        if ($toim == "PERHE") {
          echo "<tr>";

          if ($hintatyyppi == '') {
            echo "<td class='back' colspan='3'></td>";
          }
          else {
            echo "<td class='back'></td>";
          }

          echo "<th colspan='2' class='text-right'>".t("Yhteensä")." (".t("lapset+isä")."):</th>";

          $_yhteensa = sprintf('%.2f', round($myyntihintayht+$isarow['myyntihinta'], 6));
          echo "<td class='tumma text-right'>{$_yhteensa}</td>";
          echo "<td class='tumma'></td>";

          $_yhteensa = round($resyht+$isarow['kehahin'], 6);
          echo "<td class='tumma text-right'>{$_yhteensa}</td>";

          echo "</tr>";
        }
      }

      echo "</table>";

      $excelnimi = $worksheet->close();

      echo "<br><br><table>";
      echo "<tr><th>".t("Tallenna exceliin")."</th>";

      $checked = $myos_tuotetiedot_exceliin ? "checked" : "";

      echo "<td>
              <form>
                <input type='hidden' name='toim' value='{$toim}'>
                <input type='hidden' name='isatuoteno' value='{$isatuoteno}'>
                <input type='hidden' name='hakutuoteno' value='{$hakutuoteno}'>
                <input type='hidden' name='lopetus' value='{$lopetus}'>
                <input type='hidden' name='tuotetiedot_nappulaa_klikattu' value='kylla'>

                <label>" . t("Myös malli, mallitarkenne ja tuotemerkki") . "
                  <input type='checkbox'
                         name='tuotetiedot_exceliin'
                         value='kylla'
                         onchange='this.form.submit()'
                         {$checked}>
                </label>
              </form>
            </td>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='{$row['isatuoteno']}.xlsx'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
      echo "<td><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
      echo "</table><br>";

      echo "<br><br>";

      if ($oikeurow['paivitys'] == '1') {
        echo "<form method='post' action='tuoteperhe.php' autocomplete='off'>";
        echo "<input type='hidden' name='toim' value='$toim'>";
        echo "<input type='hidden' name='tee' value='KOPIOI'>";
        echo "<input type='hidden' name='hakutuoteno' value='$hakutuoteno'>";

        foreach ($kop_tuoteno as $kop_index => $tuoteno) {
          echo "<input type='hidden' name='kop_tuoteno[$kop_index]' value='$kop_tuoteno[$kop_index]'>";
          echo "<input type='hidden' name='kop_kerroin[$kop_index]' value='$kop_kerroin[$kop_index]'>";
          echo "<input type='hidden' name='kop_hinkerr[$kop_index]' value='$kop_hinkerr[$kop_index]'>";
          echo "<input type='hidden' name='kop_alekerr[$kop_index]' value='$kop_alekerr[$kop_index]'>";
          echo "<input type='hidden' name='kop_fakta[$kop_index]' value='$kop_fakta[$kop_index]'>";

          if ($toim == "PERHE") {
            echo "<input type='hidden' name='kop_ohita_kerays[$kop_index]' value='$kop_ohita_kerays[$kop_index]'>";
            echo "<input type='hidden' name='kop_ei_nayteta[$kop_index]' value='$kop_ei_nayteta[$kop_index]'>";
            echo "<input type='hidden' name='kop_hintatyyppi[$kop_index]' value='$kop_hintatyyppi[$kop_index]'>";
          }
        }

        echo "<input type='submit' value='".t("Kopioi")."'>";
        echo "</form>";
      }
    }
    // Haulla löytyi monta tuotetta
    else {
      echo "<table>";

      while ($row = mysql_fetch_array($result)) {
        $query = "SELECT *
                  from tuoteperhe
                  where isatuoteno = '$row[isatuoteno]'
                  and yhtio        = '$kukarow[yhtio]'
                  and tyyppi       = '$hakutyyppi'
                  order by isatuoteno, tuoteno";
        $res = pupe_query($query);

        $_isatuoteno = urlencode($row["isatuoteno"]);
        $_href = "$PHP_SELF?toim=$toim&isatuoteno={$_isatuoteno}&hakutuoteno={$_isatuoteno}";

        echo "<tr>";
        echo "<td>";
        echo "<a href='{$_href}'>{$row["isatuoteno"]}</a>";
        echo "</td>";

        while ($prow = mysql_fetch_array($res)) {
          echo "<td>$prow[tuoteno]</td>";
        }

        echo "</tr>";
      }
      echo "</table>";
    }
  }
  else {
    echo "<br>";
    echo "<font class='error'>".t("Tuotenumeroa")." $tchk ".t("ei löydy")."!</font>";
    echo "<br>";
  }
}
elseif ($tee == "") {

  $lisa1 = "";
  $lisalimit = "";

  if ($limitti !='' and $limitti !="U") {
    $limitteri = " limit $limitti";
  }
  elseif ($limitti == "U") {
    $lisalimit = "tuoteperhe.tunnus desc, ";
  }
  else {
    $limitteri = "";
  }

  if (isset ($isatuoteno_haku) and $isatuoteno_haku != '') {
    $lisa1 .= "AND (tuoteperhe.isatuoteno LIKE '%{$isatuoteno_haku}%'
                 OR ti.nimitys LIKE '%{$isatuoteno_haku}%') ";
  }

  if ($tuoteno_haku != '') {
    $lisa1 .= " and tuoteperhe.tuoteno like '%$tuoteno_haku%' ";
  }

  if ($toim == "RESEPTI") {
    $query = "SELECT DISTINCT tuoteperhe.isatuoteno
              FROM tuoteperhe
              INNER JOIN tuote ti ON (ti.yhtio = tuoteperhe.yhtio
                AND ti.tuoteno       = tuoteperhe.isatuoteno)
              INNER JOIN tuote tl ON (tl.yhtio = tuoteperhe.yhtio
                AND tl.tuoteno       = tuoteperhe.tuoteno)
              WHERE tuoteperhe.yhtio = '{$kukarow["yhtio"]}'
              AND tuoteperhe.tyyppi  = '{$hakutyyppi}'
              {$lisa1}
              ORDER BY {$lisalimit}
              tuoteperhe.isatuoteno
              {$limitteri}";
  }
  else {
    $query = "SELECT tuoteperhe.isatuoteno,
              ti.nimitys,
              group_concat(
                concat(tuoteperhe.tuoteno, ' ' , tl.nimitys) ORDER BY tuoteperhe.tuoteno,
                tuoteperhe.tunnus separator '<br>') AS tuotteet
              FROM tuoteperhe
              JOIN tuote ti ON (ti.yhtio = tuoteperhe.yhtio
                AND ti.tuoteno       = tuoteperhe.isatuoteno)
              JOIN tuote tl ON (tl.yhtio = tuoteperhe.yhtio
                AND tl.tuoteno       = tuoteperhe.tuoteno)
              WHERE tuoteperhe.yhtio = '$kukarow[yhtio]'
              AND tuoteperhe.tyyppi  = '$hakutyyppi'
              $lisa1
              GROUP BY tuoteperhe.isatuoteno
              ORDER BY $lisalimit
              tuoteperhe.isatuoteno,
              tuoteperhe.tuoteno
              $limitteri";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    // Piirrellään formi
    // Kursorinohjaus
    $formi  = "haku";
    $kentta = "isatuoteno_haku";

    $lopetus = "{$palvelin2}tuoteperhe.php" .
      "////toim={$toim}" .
      "//isatuoteno_haku={$isatuoteno_haku}" .
      "//tuoteno_haku={$tuoteno_haku}";

    echo "<form name='haku' action='tuoteperhe.php' method='post'>";
    echo "<input type='hidden' name='toim' value='$toim'>";

    echo "<br><br><table>";
    echo "<tr><th>".t("Tuoteperhe")."</th><th>".t("Tuotteet")."</th></tr>";

    echo "<tr>";
    echo "<td><input type='text' size='20' name='isatuoteno_haku' value='$isatuoteno_haku'></td>";
    echo "<td><input type='text' size='20' name='tuoteno_haku' value='$tuoteno_haku'></td>";
    echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td>";
    echo "</tr>";

    while ($prow = mysql_fetch_array($result)) {
      $_isatuoteno = urlencode($prow["isatuoteno"]);
      $_href = "{$PHP_SELF}" .
        "?toim={$toim}" .
        "&isatuoteno={$_isatuoteno}" .
        "&hakutuoteno={$_isatuoteno}" .
        "&lopetus=$lopetus";

      echo "<tr class='aktiivi'>";

      if ($toim == "RESEPTI") {
        $tuoteperhe = hae_tuoteperhe($prow["isatuoteno"]);
        $tuoteperhe = reset($tuoteperhe);

        echo "<td><a href='{$_href}'>{$prow["isatuoteno"]} {$tuoteperhe["nimitys"]}</a></td>";
        echo "<td>";

        $tuoteperhe = $tuoteperhe["lapset"];

        if (!empty($tuoteperhe)) {
          piirra_tuoteperhe($tuoteperhe);
        }

        echo "</td>";
      }
      else {
        echo "<td><a href='{$_href}'>{$prow["isatuoteno"]} {$prow["nimitys"]}</a></td>";
        echo "<td>{$prow["tuotteet"]} {$prow["nimitykset"]}</td>";
      }
      echo "</tr>";
    }

    echo "</table>";
    echo "</form>";
  }
}

require "inc/footer.inc";

function piirra_tuoteperhe($tuoteperhe, $hidden = false) {
  global $PHP_SELF, $toim, $lopetus;

  ksort($tuoteperhe);

  $hidden_class = $hidden ? "hidden" : "";

  echo "<ul class='list-unstyled {$hidden_class}'>";

  foreach ($tuoteperhe as $tuoteno => $tuote) {
    echo "<li>";

    if (empty($tuote["lapset"])) {
      if ($tuote["status"] == "P") echo "<del>";
      echo "{$tuoteno} {$tuote["nimitys"]}";
      if ($tuote["status"] == "P") echo "</del>";
    }
    else {
      echo "<a href='{$PHP_SELF}" .
        "?toim={$toim}" .
        "&isatuoteno={$tuoteno}" .
        "&hakutuoteno={$tuoteno}" .
        "&lopetus={$lopetus}'>";
      if ($tuote["status"] == "P") echo "<del>";
      echo "{$tuoteno} {$tuote["nimitys"]}";
      if ($tuote["status"] == "P") echo "</del>";
      echo "</a>";
    }

    if (!empty($tuote["lapset"])) {
      echo " <a href class='toggle-list'>+</a>";
    }

    echo "</li>";

    if (!empty($tuote["lapset"])) {
      piirra_tuoteperhe($tuote["lapset"], true);
    }
  }
  echo "</ul>";
}
