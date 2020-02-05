<?php

if (isset($_REQUEST["tee"])) {
  if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/", "", $_REQUEST["kaunisnimi"]);
}

require "inc/parametrit.inc";
require "inc/functions.inc";

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}
else {
  $db_tecdoc = mysql_connect($dbhost_tecdoc, $dbuser_tecdoc, $dbpass_tecdoc) or die('Could not connect: ' . mysql_error());
  mysql_select_db($dbkanta_tecdoc, $db_tecdoc) or die('Could not select database orum<br>');

  $link = mysql_connect($dbhost, $dbuser, $dbpass) or die('Could not connect: ' . mysql_error());
  mysql_select_db($dbkanta, $link) or die('Could not select database orum<br>');

  $tyyppi = "HA";

  echo "<font class='head'>", t("Yhteensopivuustaulukko ylläpito"), "</font><hr>";

  echo "<form name='selain' method='post'>";
  echo "<input type='hidden' name='toim' value='$toim'>";

  echo "<table>";
  echo "<tr><td class='back'>";

  $monivalintalaatikot = array('DYNAAMINEN_TUOTE');
  $monivalintalaatikot_normaali = array();

  require "tilauskasittely/monivalintalaatikot.inc";

  echo "</td></tr>";
  echo "</table>";

  echo "<br><input type='submit' name='submit_button' value='", t("Hae"), "'>";
  echo "</form>";

  if (!isset($submit_button) or $lisa_dynaaminen["tuote"] == '') {
    echo "<br><br><font class='message'>", t("Valitse ainakin yksi tuoteryhmä tai tuotemerkki"), "!</font>";
  }
  else {
    $query = "(SELECT DISTINCT T_PC.C_PCTYPENO AS typeno,
               T_PC.C_LTTYPE_VALKEY,
                T_PC.C_PK,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(COMPDESIG_FI.C_TEXT, '') AS pitkateksti,
               T_PC.C_KWFROM kw,
               T_PC.C_HPFROM hp,
               ROUND(T_PC.C_CAPLIT, 1) cc,
               T_PC.C_CYL sylinterit,
               T_PC.C_VALVESTOTAL venttiilit,
               'PC' AS tyyppi,
               YEAR(T_PC.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_PC.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_PC.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_PC.C_CONSTRYEARTO) as autoloppukk,
               T_KEYVAL_NAME.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG) AS malliosa2,
               group_concat(T_ENGINE.C_ENGINECODE) moottori
               FROM T_PC
               JOIN T_MS ON (T_PC.C_MSREF_MODELNO = T_MS.C_MODELNO AND T_MS.C_PRODUCTTYPE_VALKEY = 'PC' AND T_MS.C_SOURCE_VALKEY = 'TD')
               JOIN T_LTB ON (T_LTB.C_BRANDNO = T_MS.C_LTBRANDREF_BRANDNO)
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_PC.C_DRIVETYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_PC.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%'))
               LEFT JOIN T_MS_DESIG ON (T_MS_DESIG.C_FK = T_MS.C_PK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_PC_COMPDESIG AS COMPDESIG_FI ON (T_PC.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_PC_ENGINE ON (T_PC_ENGINE.C_FK = T_PC.C_PK)
               LEFT JOIN T_ENGINE ON (T_PC_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE T_PC.C_LTTYPE_VALKEY       = 'PC'
               AND T_PC.C_COURESTR_COUCODELIST  LIKE '%FI%'
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               UNION
               (SELECT DISTINCT T_LCV.C_LCVTYPENO AS typeno,
               T_LCV.C_LTTYPE_VALKEY,
                T_LCV.C_PK,
               IFNULL(CONCAT(' ', T_MS_GENERATION.C_TEXT, ' '), '') C_GENERATION,
               IFNULL(COMPDESIG_FI.C_TEXT, '') AS pitkateksti,
               T_LCV.C_KWFROM kw,
               T_LCV.C_HPFROM hp,
               ROUND(T_LCV.C_CAPLIT, 1) cc,
               T_LCV.C_CYL sylinterit,
               T_LCV.C_VALVESTOTAL venttiilit,
               'LCV' AS tyyppi,
               YEAR(T_LCV.C_CONSTRYEARFROM) as autoalkuvuosi,
               IFNULL(YEAR(T_LCV.C_CONSTRYEARTO), '') as autoloppuvuosi,
               MONTH(T_LCV.C_CONSTRYEARFROM) as autoalkukk,
               MONTH(T_LCV.C_CONSTRYEARTO) as autoloppukk,
               T_KEYVAL_NAME.C_TEXT voimansiirto,
               T_LTB.C_BRANDNAME AS merkki,
               T_MS_DESIG.C_TEXT AS malliosa1,
               GROUP_CONCAT(DISTINCT T_MS_CONSTRTYPE.C_DESIG) AS malliosa2,
               group_concat(T_ENGINE.C_ENGINECODE) moottori
               FROM T_LCV
               JOIN T_MS ON (T_LCV.C_MSREF_MODELNO = T_MS.C_MODELNO AND T_MS.C_PRODUCTTYPE_VALKEY = 'LCV' AND T_MS.C_SOURCE_VALKEY = 'TD')
               JOIN T_LTB ON (T_LTB.C_BRANDNO = T_MS.C_LTBRANDREF_BRANDNO)
               JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_LCV.C_DRIVETYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_LCV.C_DRIVETYPE_VALKEY)
               JOIN T_KEYVAL_NAME ON (T_KEYVAL_NAME.C_FK = T_KEYVAL.C_PK AND (T_KEYVAL_NAME.C_LNG like '%FI%' or T_KEYVAL_NAME.C_LNG like '%ZZ%'))
               LEFT JOIN T_MS_DESIG ON (T_MS_DESIG.C_FK = T_MS.C_PK AND T_MS_DESIG.C_COURESTR_COUCODELIST LIKE '%ZZ%')
               LEFT JOIN T_MS_CONSTRTYPE ON (T_MS_CONSTRTYPE.C_FK = T_MS.C_PK)
               LEFT JOIN T_LCV_COMPDESIG AS COMPDESIG_FI ON (T_LCV.C_PK = COMPDESIG_FI.C_FK AND COMPDESIG_FI.C_COURESTR_COUCODELIST LIKE '%FI%')
               LEFT JOIN T_LCV_ENGINE ON (T_LCV_ENGINE.C_FK = T_LCV.C_PK)
               LEFT JOIN T_ENGINE ON (T_LCV_ENGINE.C_ENGINE_ENGINENO = T_ENGINE.C_ENGINENO)
               LEFT JOIN T_MS_GENERATION ON (T_MS_GENERATION.C_FK = T_MS.C_PK AND (T_MS_GENERATION.C_COURESTR_COUCODELIST like '%FI%' or T_MS_GENERATION.C_COURESTR_COUCODELIST like '%ZZ%'))
               WHERE T_LCV.C_LTTYPE_VALKEY      = 'LCV'
               AND T_LCV.C_COURESTR_COUCODELIST LIKE '%FI%'
               GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
               ORDER BY merkki, malliosa1, C_GENERATION, malliosa2, pitkateksti";
    $result = pupe_query($query, $db_tecdoc);

    echo "<br><br><font class='message'>", t("Käydään läpi"), " ".mysql_num_rows($result)." ", t("ajoneuvomallia"), ".</font><br>";
    flush();

    $laskuri = 0;

    if (mysql_num_rows($result) > 0) {
      // Progressbar
      require_once 'inc/ProgressBar.class.php';
      $bar = new ProgressBar();
      $bar->initialize(mysql_num_rows($result)); // print the empty bar

      include 'inc/pupeExcel.inc';

      $worksheet    = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi    = 0;
      $excelsarake = 0;

      $worksheet->writeString($excelrivi, $excelsarake, "autoid", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "ajoneuvo", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "voimansiirto", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "kw", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "hp", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "cc", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "sylinterit", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "moottori", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "moottorityyppi", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "venttiilit", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "autoalkukk", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "autoalkuvuosi", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "autoloppukk", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "autoloppuvuosi", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "tuoteno", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "extra", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "extra2", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "extra3", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "solu", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "ketjuid", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "tuotemerkki", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "nimitys", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "kuvaus", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "tahtituote", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "status", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "hinnastoon", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "epakurantti25pvm", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "epakurantti50pvm", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "epakurantti75pvm", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "epakurantti100pvm", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "kuvapankissa", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "auto_maara", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "saldo_normal", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "saldo_kaikki", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "myynti_12kk", $format_bold);
      $excelsarake++;
      $worksheet->writeString($excelrivi, $excelsarake, "toiminto", $format_bold);

      $excelsarake = 0;
      $excelrivi++;
    }

    while ($row = mysql_fetch_assoc($result)) {

      $laskuri++;
      $bar->increase();

      $query = "SELECT yhteensopivuus_tuote.*, tuote.*, korvaavat.id
                FROM yhteensopivuus_tuote USE INDEX (yhtio_tyyppi_atunnus_tuoteno)
                JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = yhteensopivuus_tuote.yhtio and tuote.tuoteno = yhteensopivuus_tuote.tuoteno)
                LEFT JOIN korvaavat USE INDEX (yhtio_tuoteno) ON (korvaavat.yhtio = yhteensopivuus_tuote.yhtio and korvaavat.tuoteno = yhteensopivuus_tuote.tuoteno)
                WHERE yhteensopivuus_tuote.yhtio = '{$kukarow['yhtio']}'
                and yhteensopivuus_tuote.atunnus = '{$row['typeno']}'
                and yhteensopivuus_tuote.tyyppi  = '{$tyyppi}'
                $lisa";
      $tuores = pupe_query($query, $link);

      // ei löytynyt tälle mallille yhtään tuotetta.. tehdään mallirivi LISÄÄ toiminnolla
      if (mysql_num_rows($tuores) == 0) {

        $toiminto     = "lisaa";
        $tuote       = array();
        $myyrow     = array();
        $tuotekuva     = "";
        $saldo_normi   = "";
        $saldo_kaikki  = "";

        $kplquery = "SELECT COUNT(*)
                     FROM yhteensopivuus_rekisteri USE INDEX (yhtio_autoid)
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND autoid  = '{$row['typeno']}'";
        $kplresult = pupe_query($kplquery, $link);
        $kplrow = mysql_fetch_array($kplresult);

        $worksheet->writeString($excelrivi, $excelsarake, "$row[typeno]");
        $excelsarake++;

        if ($row['pitkateksti'] == '') {
          $query = "SELECT C_TEXT
                    FROM T_{$row['tyyppi']}_COMPDESIG
                    WHERE C_FK                 = '{$row['C_PK']}'
                    AND C_COURESTR_COUCODELIST LIKE '%ZZ'";
          $en_res = pupe_query($query, $db_tecdoc);
          $en_row = mysql_fetch_assoc($en_res);

          $teksti = $row['merkki'].' '.$row['malliosa1'].$row['C_GENERATION'].' ';

          if ($row['malliosa2'] != '') $teksti .= "({$row['malliosa2']}) ";
          $teksti .= $en_row['C_TEXT'];

          $worksheet->writeString($excelrivi, $excelsarake, "$teksti");
        }
        else {

          $teksti = $row['merkki'].' '.$row['malliosa1'].$row['C_GENERATION'].' ';
          if ($row['malliosa2'] != '') $teksti .= "({$row['malliosa2']}) ";
          $teksti .= $row['pitkateksti'];

          $worksheet->writeString($excelrivi, $excelsarake, "$teksti");
        }

        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[voimansiirto]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[kw]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[hp]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[cc]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[sylinterit]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[moottori]");
        $excelsarake++;

        //moottorityyppi, esim dieselmoottori
        $query = "SELECT IFNULL(T_KEYVAL_NAME_FI.C_TEXT, T_KEYVAL_NAME_EN.C_TEXT) C_TEXT
                  FROM T_{$row['tyyppi']}
                  JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_{$row['tyyppi']}.C_ENGINETYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_{$row['tyyppi']}.C_ENGINETYPE_VALKEY)
                  JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_FI ON (T_KEYVAL_NAME_FI.C_FK = T_KEYVAL.C_PK AND T_KEYVAL_NAME_FI.C_LNG = 'FI')
                  LEFT JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_EN ON (T_KEYVAL_NAME_EN.C_FK = T_KEYVAL.C_PK AND T_KEYVAL_NAME_EN.C_LNG = 'ZZ')
                  WHERE T_{$row['tyyppi']}.C_{$row['tyyppi']}TYPENO = '".$row['C_'.$row['tyyppi'].'TYPENO']."'
                  AND T_{$row['tyyppi']}.C_LTTYPE_VALKEY = '{$row['C_LTTYPE_VALKEY']}'";
        $moottorityyppi_res = pupe_query($query, $db_tecdoc);
        $moottorityyppi_row = mysql_fetch_assoc($moottorityyppi_res);

        $worksheet->writeString($excelrivi, $excelsarake, "$moottorityyppi_row[C_TEXT]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[venttiilit]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[autoalkukk]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[autoalkuvuosi]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[autoloppukk]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$row[autoloppuvuosi]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$tuotekuva");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$kplrow[0]");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$saldo_normi");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$saldo_kaikki");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "");
        $excelsarake++;
        $worksheet->writeString($excelrivi, $excelsarake, "$toiminto");

        $excelsarake = 0;
        $excelrivi++;
      }
      else {
        // muuten rullataan kaikki tuotteet jotka sopii tähän automalliin ja laitetaan MUUTA toiminto
        while ($tuote  = mysql_fetch_assoc($tuores)) {

          $toiminto = "muuta";

          $kplquery = "SELECT count(*)
                       FROM yhteensopivuus_rekisteri USE INDEX (yhtio_autoid)
                       WHERE yhtio = '{$kukarow['yhtio']}'
                       AND autoid  = '{$row['typeno']}'";
          $kplresult = pupe_query($kplquery, $link);
          $kplrow = mysql_fetch_array($kplresult);

          $myyquery = "SELECT ifnull(sum(kpl), 0)
                       FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_laskutettuaika)
                       WHERE yhtio        = '{$kukarow['yhtio']}'
                       AND tuoteno        = '{$tuote['tuoteno']}'
                       AND tyyppi         = 'L'
                       AND laskutettuaika >= date_sub(now(), INTERVAL 12 MONTH)";
          $myyresult = pupe_query($myyquery, $link);
          $myyrow = mysql_fetch_array($myyresult);

          $filetype_query = "SELECT tunnus
                             FROM liitetiedostot USE INDEX (yhtio_liitos_liitostunnus)
                             WHERE yhtio         = '{$kukarow['yhtio']}'
                             AND liitos          = 'tuote'
                             AND liitostunnus    = '{$tuote['tunnus']}'
                             AND kayttotarkoitus = 'TK'";
          $filetype_result = pupe_query($filetype_query, $link);

          list(, , $saldo_normi) = saldo_myytavissa($tuote["tuoteno"]);
          list(, , $saldo_kaikki) = saldo_myytavissa($tuote["tuoteno"], "KAIKKI");

          if (mysql_num_rows($filetype_result) > 0) {
            $tuotekuva = "on";
          }
          else {
            $tuotekuva = "";
          }

          $worksheet->writeString($excelrivi, $excelsarake, "$row[typeno]");
          $excelsarake++;

          if ($row['pitkateksti'] == '') {
            $query = "SELECT C_TEXT
                      FROM T_{$row['tyyppi']}_COMPDESIG
                      WHERE C_FK                 = '{$row['C_PK']}'
                      AND C_COURESTR_COUCODELIST LIKE '%ZZ'";
            $en_res = pupe_query($query, $db_tecdoc);
            $en_row = mysql_fetch_assoc($en_res);

            $teksti = $row['merkki'].' '.$row['malliosa1'].$row['C_GENERATION'].' ';
            if ($row['malliosa2'] != '') $teksti .= "({$row['malliosa2']}) ";
            $teksti .= $en_row['C_TEXT'];

            $worksheet->writeString($excelrivi, $excelsarake, "$teksti");
          }
          else {

            $teksti = $row['merkki'].' '.$row['malliosa1'].$row['C_GENERATION'].' ';
            if ($row['malliosa2'] != '') $teksti .= "({$row['malliosa2']}) ";
            $teksti .= $row['pitkateksti'];

            $worksheet->writeString($excelrivi, $excelsarake, "$teksti");
          }
          $excelsarake++;

          $worksheet->writeString($excelrivi, $excelsarake, "$row[voimansiirto]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$row[kw]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$row[hp]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$row[cc]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$row[sylinterit]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$row[moottori]");
          $excelsarake++;

          //moottorityyppi, esim dieselmoottori
          $query = "SELECT IFNULL(T_KEYVAL_NAME_FI.C_TEXT, T_KEYVAL_NAME_EN.C_TEXT) C_TEXT
                    FROM T_{$row['tyyppi']}
                    JOIN T_KEYVAL ON (T_KEYVAL.C_KEYTABNO = T_{$row['tyyppi']}.C_ENGINETYPE_KEYTABNO AND T_KEYVAL.C_VALKEY = T_{$row['tyyppi']}.C_ENGINETYPE_VALKEY)
                    JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_FI ON (T_KEYVAL_NAME_FI.C_FK = T_KEYVAL.C_PK AND T_KEYVAL_NAME_FI.C_LNG = 'FI')
                    LEFT JOIN T_KEYVAL_NAME AS T_KEYVAL_NAME_EN ON (T_KEYVAL_NAME_EN.C_FK = T_KEYVAL.C_PK AND T_KEYVAL_NAME_EN.C_LNG = 'ZZ')
                    WHERE T_{$row['tyyppi']}.C_{$row['tyyppi']}TYPENO = '".$row['C_'.$row['tyyppi'].'TYPENO']."'
                    AND T_{$row['tyyppi']}.C_LTTYPE_VALKEY = '{$row['C_LTTYPE_VALKEY']}'";
          $moottorityyppi_res = pupe_query($query, $db_tecdoc);
          $moottorityyppi_row = mysql_fetch_assoc($moottorityyppi_res);

          $worksheet->writeString($excelrivi, $excelsarake, "$moottorityyppi_row[C_TEXT]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$row[venttiilit]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$row[autoalkukk]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$row[autoalkuvuosi]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$row[autoloppukk]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$row[autoloppuvuosi]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[tuoteno]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[extra]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[extra2]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[extra3]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[solu]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[id]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[tuotemerkki]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[nimitys]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, str_replace(array("\r\n", "\n", "\r"), " ", trim($tuote['kuvaus'])));
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[tahtituote]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[status]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[hinnastoon]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[epakurantti25pvm]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[epakurantti50pvm]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[epakurantti75pvm]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuote[epakurantti100pvm]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$tuotekuva");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$kplrow[0]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$saldo_normi");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$saldo_kaikki");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$myyrow[0]");
          $excelsarake++;
          $worksheet->writeString($excelrivi, $excelsarake, "$toiminto");

          $excelsarake = 0;
          $excelrivi++;
        }
      }
    }

    $excelnimi = $worksheet->close();

    echo "<br><br>";
    echo "<font class='message'>", t("Löydettiin"), " {$laskuri} ", t("tuotetta"), ".</font><br>";

    echo "<table>";
    echo "<tr><th>".t("Tallenna raportti (xlsx)").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Yhteensopivuus.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='{$excelnimi}'>";
    echo "<td class='back'><input type='submit' value='", t("Tallenna"), "'></td></tr></form>";
    echo "</table><br>";
  }
}

require "inc/footer.inc";
