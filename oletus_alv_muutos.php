<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Alv-oletuksen vaihto")."</font><hr>";

if ($tee == "") {
  echo "<br><form method='post' autocomplete='off'>";
  echo "<input type='hidden' name='tee' value='LASKE'>";
  echo "<select name='suunta'>";
  if ($yhtiorow["alv_kasittely"] == "o") echo "<option value='LISAA'>".t("Vaihda verottomat hinnat verollisiksi")."</option>";
  if ($yhtiorow["alv_kasittely"] == "")  echo "<option value='POIST'>".t("Vaihda verolliset hinnat verottomiksi")."</option>";
  echo "</select> ";
  echo " <input type='Submit' value='".t("Tee")."'></form>";
}
else {

  $loput = "";

  if ($suunta == "LISAA") {
    $oper = "*";
    $teksti = "Lisätään vero";
    $yhtion_para = "";
  }
  else {
    $oper = "/";
    $teksti = "Poistetaan vero";
    $yhtion_para = "o";
  }

  // poistetaan lukot
  $query = "LOCK TABLES yhtion_parametrit WRITE, asiakashinta WRITE, hinnasto WRITE, toimitustapa WRITE, rahtimaksut WRITE, tilausrivi WRITE, tuote WRITE, maksupositio WRITE, lasku READ, avainsana READ";
  $locre = mysql_query($query) or pupe_error($query);

  $query  = " UPDATE yhtion_parametrit
        SET alv_kasittely = '$yhtion_para'
        WHERE yhtio = '{$kukarow["yhtio"]}'";
  $result = pupe_query($query);

  //yhtiön oletusalvi!
  $query = "  SELECT selite
        FROM avainsana
        where yhtio = '{$kukarow["yhtio"]}'
        and laji = 'alv'
        and selitetark != ''";
  $wtres  = pupe_query($query);

  if (mysql_num_rows($wtres) == 1) {

    $wtrow = mysql_fetch_assoc($wtres);
    $alv = $wtrow["selite"];

    echo "<br>".t("Yhtiön oletus ALV").": $alv%<br><br>";

    echo "<table>";

    $query  = "SHOW TABLES FROM $dbkanta";
    $tabresult = pupe_query($query);

    while ($tables = mysql_fetch_array($tabresult)) {

      $query  = "DESCRIBE $tables[0]";
      $fieldresult = pupe_query($query);

      while ($fields = mysql_fetch_array($fieldresult)) {

        if (strpos($fields[0], "hinta") !== FALSE or strpos($fields[0], "summa") !== FALSE or strpos($fields[0], "arvo") !== FALSE) {

          if ($tables[0] == "asiakashinta" and  $fields[0] == "hinta") {
            echo "<tr><th>$tables[0]</th><th>$fields[0]</th><th>$teksti</th></tr>";

            $query  = "  UPDATE asiakashinta
                  JOIN tuote on tuote.yhtio=asiakashinta.yhtio and tuote.tuoteno=asiakashinta.tuoteno
                  SET asiakashinta.hinta = round(asiakashinta.hinta {$oper} (1 + (tuote.alv / 100)), {$yhtiorow['hintapyoristys']})
                  where asiakashinta.yhtio = '{$kukarow["yhtio"]}'
                  and asiakashinta.tuoteno != ''";
            $result = pupe_query($query);

            $query  = "  UPDATE asiakashinta
                  SET asiakashinta.hinta = round(asiakashinta.hinta {$oper} (1 + ($alv / 100)), {$yhtiorow['hintapyoristys']})
                  where asiakashinta.yhtio = '{$kukarow["yhtio"]}'
                  and asiakashinta.tuoteno = ''";
            $result = pupe_query($query);
          }
          elseif ($tables[0] == "hinnasto" and $fields[0] == "hinta") {
            echo "<tr><th>$tables[0]</th><th>$fields[0]</th><th>$teksti</th></tr>";

            $query  = " UPDATE hinnasto
                  JOIN tuote on tuote.yhtio=hinnasto.yhtio and tuote.tuoteno=hinnasto.tuoteno
                  SET hinnasto.hinta = round(hinnasto.hinta {$oper} (1 + (tuote.alv / 100)), {$yhtiorow['hintapyoristys']})
                  WHERE hinnasto.yhtio  = '{$kukarow["yhtio"]}'
                  AND hinnasto.valkoodi = '{$yhtiorow["valkoodi"]}'";
            $result = pupe_query($query);
          }
          elseif ($tables[0] == "toimitustapa" and $fields[0] == "jvkulu") {
            echo "<tr><th>$tables[0]</th><th>$fields[0]</th><th>$teksti</th></tr>";

            $updalv = $alv;

            if ($yhtiorow["jalkivaatimus_tuotenumero"] != "") {
              $query  = "  SELECT alv
                    FROM tuote
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    AND tuoteno = '{$yhtiorow["jalkivaatimus_tuotenumero"]}'";
              $tuotenores = pupe_query($query);

              if (mysql_num_rows($tuotenores) == 1) {
                $tuotenorow = mysql_fetch_assoc($tuotenores);
                $updalv = $tuotenorow["alv"];
              }
            }

            $query  = " UPDATE toimitustapa
                  SET jvkulu = round(jvkulu {$oper} (1 + ({$updalv} / 100)), {$yhtiorow['hintapyoristys']})
                  WHERE yhtio = '{$kukarow["yhtio"]}'";
            $result = pupe_query($query);
          }
          elseif ($tables[0] == "toimitustapa" and $fields[0] == "erilliskasiteltavakulu") {
            echo "<tr><th>$tables[0]</th><th>$fields[0]</th><th>$teksti</th></tr>";

            $updalv = $alv;

            if ($yhtiorow["erilliskasiteltava_tuotenumero"] != "") {
              $query  = "  SELECT alv
                    FROM tuote
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    AND tuoteno = '{$yhtiorow["erilliskasiteltava_tuotenumero"]}'";
              $tuotenores = pupe_query($query);

              if (mysql_num_rows($tuotenores) == 1) {
                $tuotenorow = mysql_fetch_assoc($tuotenores);
                $updalv = $tuotenorow["alv"];
              }
            }

            $query  = " UPDATE toimitustapa
                  SET erilliskasiteltavakulu = round(erilliskasiteltavakulu {$oper} (1 + ({$updalv} / 100)), {$yhtiorow['hintapyoristys']})
                  WHERE yhtio = '{$kukarow["yhtio"]}'";
            $result = pupe_query($query);
          }
          elseif ($tables[0] == "rahtimaksut" and  $fields[0] == "rahtihinta") {
            echo "<tr><th>$tables[0]</th><th>$fields[0]</th><th>$teksti</th></tr>";

            $updalv = $alv;

            if ($yhtiorow["rahti_tuotenumero"] != "") {
              $query  = "  SELECT alv
                    FROM tuote
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    AND tuoteno = '{$yhtiorow["rahti_tuotenumero"]}'";
              $tuotenores = pupe_query($query);

              if (mysql_num_rows($tuotenores) == 1) {
                $tuotenorow = mysql_fetch_assoc($tuotenores);
                $updalv = $tuotenorow["alv"];
              }
            }

            $query  = " UPDATE rahtimaksut
                  SET rahtihinta = round(rahtihinta {$oper} (1 + ({$updalv} / 100)), {$yhtiorow['hintapyoristys']})
                  WHERE yhtio = '{$kukarow["yhtio"]}'";
            $result = pupe_query($query);
          }
          elseif ($tables[0] == "tilausrivi" and ($fields[0] == "hinta" or $fields[0] == "hinta_valuutassa")) {
            echo "<tr><th>$tables[0]</th><th>$fields[0]</th><th>$teksti</th></tr>";

            $query = "  UPDATE tilausrivi
                  SET $fields[0] = round($fields[0] {$oper} (1 + (alv / 100)), {$yhtiorow['hintapyoristys']})
                  WHERE yhtio = '{$kukarow["yhtio"]}'
                  and tyyppi in ('0','B','E','F','G','L','M','N','T','V','W','Z')
                  and alv > 0
                  and alv < 500";
            $result = pupe_query($query);
          }
          elseif ($tables[0] == "tuote" and ($fields[0] == "myyntihinta" or $fields[0] == "myymalahinta" or $fields[0] == "nettohinta" or $fields[0] == "rahtivapaa_alarajasumma" or $fields[0] == "tuntihinta")) {

            echo "<tr><th>$tables[0]</th><th>$fields[0]</th><th>$teksti</th></tr>";

            $query = "  UPDATE tuote
                  SET {$fields[0]} = round({$fields[0]} {$oper} (1 + (alv / 100)), {$yhtiorow['hintapyoristys']})
                  WHERE yhtio = '{$kukarow["yhtio"]}'";
            $result = pupe_query($query);
          }
          elseif ($tables[0] == "maksupositio" and  $fields[0] == "summa") {
            echo "<tr><th>$tables[0]</th><th>$fields[0]</th><th>$teksti</th></tr>";

            $query  = "  SELECT *
                  FROM maksupositio
                  where yhtio = '{$kukarow["yhtio"]}'";
            $posres = pupe_query($query);

            while ($posrow = mysql_fetch_array($posres)) {

              $updalv = $alv;

              $query  = "  SELECT alv
                    FROM lasku
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    AND tunnus = '{$posrow["otunnus"]}'";
              $laskures = pupe_query($query);

              if (mysql_num_rows($tuotenores) == 1) {
                $laskurow = mysql_fetch_assoc($laskures);
                $updalv = $laskurow["alv"];
              }

              $query = "  UPDATE maksupositio
                    SET summa = round(summa {$oper} (1 + ({$updalv} / 100)), {$yhtiorow['hintapyoristys']})
                    WHERE yhtio = '{$kukarow["yhtio"]}'
                    and tunnus  = '{$posrow["tunnus"]}'";
              $result = pupe_query($query);
            }
          }
          elseif ($tables[0] == "yhtion_parametrit" and ($fields[0] == "erikoisvarastomyynti_alarajasumma_rivi" or $fields[0] == "korvaavan_hinta_ylaraja")) {
            echo "<tr><th>$tables[0]</th><th>$fields[0]</th><th>$teksti</th></tr>";

            $query  = " UPDATE yhtion_parametrit
                  SET $fields[0] = round($fields[0] {$oper} (1 + ({$updalv} / 100)), {$yhtiorow['hintapyoristys']})
                  WHERE yhtio = '{$kukarow["yhtio"]}'";
            $result = pupe_query($query);
          }
          else {
            $loput .= "<tr><td>$tables[0]</td><td>$fields[0]</td><td>Ei muuteta</td></tr>";
          }
        }
      }
    }
  }
  echo "<tr><td class='back'><br><br></td></tr>";
  echo $loput;
  echo "</table>";

  // poistetaan lukot
  $query = "UNLOCK TABLES";
  $locre = mysql_query($query) or pupe_error($query);
}
