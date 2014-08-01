<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Poista er��ntyneet hinnastohinnat, asiakashinnat ja asiakasalennukset").":</font><hr>";


echo "<br><form method='post'>";
echo "<input type='hidden' name='tee' value='AJA'>";
echo "<table>";
echo "<tr>
  <th>".t("Poista er��ntyneet ja duplikaatit hinnastohinnat, asiakashinnat ja asiakasalennukset")."</th>
  </tr>\n";
echo "</table>";
echo "<br><input type='submit' value='".t("Poista")."'>";
echo "</form>";

if ($tee == "AJA") {

  $asiakashinta = 0;
  $hinnasto     = 0;
  $asiakasale   = 0;

  //############################################################################################
  //HINNAT:
  //############################################################################################

  // 1. k�ytt�j�n sy�tt�m� hinta/nettohinta
  // Ei siivottavaa

  // 2A. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero nettohinta (asiakkaan tuotteen hinta) laskun valuutassa
  // 2B. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero nettohinta (asiakkaan tuotteen hinta) yhti�n valuutassa
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakashinta
            WHERE yhtio  = '$kukarow[yhtio]'
            and asiakas  > 0
            and tuoteno != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY asiakas, tuoteno, valkoodi, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 2AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakashinta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakashinta
            WHERE yhtio  = '$kukarow[yhtio]'
            and ytunnus != ''
            and tuoteno != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY ytunnus, tuoteno, valkoodi, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 2AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakashinta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  // 3A. asiakas.tunnus/asiakas.ytunnus tuote.aleryhm� nettohinta (asiakkaan tuotealeryhm�n hinta) laskun valuutassa
  // 3B. asiakas.tunnus/asiakas.ytunnus tuote.aleryhm� nettohinta (asiakkaan tuotealeryhm�n hinta) yhti�n valuutassa
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakashinta
            WHERE yhtio  = '$kukarow[yhtio]'
            and asiakas  > 0
            and ryhma   != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY asiakas, ryhma, valkoodi, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 3AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakashinta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakashinta
            WHERE yhtio  = '$kukarow[yhtio]'
            and ytunnus != ''
            and ryhma   != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY ytunnus, ryhma, valkoodi, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 3AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakashinta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  // 4A. asiakas.segmentti tuote.tuoteno nettohinta (asiakassegmentin tuotteen hinta) laskun valuutassa
  // 4B. asiakas.segmentti tuote.tuoteno nettohinta (asiakassegmentin tuotteen hinta) yhti�n valuutassa
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc, tunnus desc) tunnukset
            FROM asiakashinta
            WHERE yhtio            = '$kukarow[yhtio]'
            and asiakas_segmentti != ''
            and tuoteno           != ''
            and ytunnus            = ''
            and asiakas            = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY asiakas_segmentti, tuoteno, valkoodi, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 4AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakashinta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  // 5A. asiakas.ryhm� tuote.tuoteno nettohinta (asiakasaleryhm�n tuotteen hinta) laskun valuutassa
  // 5B. asiakas.ryhm� tuote.tuoteno nettohinta (asiakasaleryhm�n tuotteen hinta) yhti�n valuutassa
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakashinta
            WHERE yhtio        = '$kukarow[yhtio]'
            and asiakas_ryhma != ''
            and tuoteno       != ''
            and ytunnus        = ''
            and asiakas        = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY asiakas_ryhma, tuoteno, valkoodi, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 5AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakashinta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  // 6A. asiakas.piiri tuote.tuoteno nettohinta (asiakaspiirin tuotteen hinta) laskun valuutassa
  // 6B. asiakas.piiri tuote.tuoteno nettohinta (asiakaspiirin tuotteen hinta) yhti�n valuutassa
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc, tunnus desc) tunnukset
            FROM asiakashinta
            WHERE yhtio  = '$kukarow[yhtio]'
            and piiri   != ''
            and tuoteno != ''
            and ytunnus  = ''
            and asiakas  = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY piiri, tuoteno, valkoodi, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 6AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakashinta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  // 7A. asiakas.segmentti tuote.aleryhma nettohinta (asiakassegmentin tuotealeryhm�n hinta) laskun valuutassa
  // 7B. asiakas.segmentti tuote.aleryhma nettohinta (asiakassegmentin tuotealeryhm�n hinta) yhti�n valuutassa
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc, tunnus desc) tunnukset
            FROM asiakashinta
            WHERE yhtio            = '$kukarow[yhtio]'
            and asiakas_segmentti != ''
            and ryhma             != ''
            and ytunnus            = ''
            and asiakas            = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY asiakas_segmentti, ryhma, valkoodi, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 7AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakashinta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  // 8A. asiakas.ryhm� tuote.aleryhm� nettohinta (asiakasaleryhm�n tuotealeryhm�n hinta) laskun valuutassa
  // 8B. asiakas.ryhm� tuote.aleryhm� nettohinta (asiakasaleryhm�n tuotealeryhm�n hinta) yhti�n valuutassa
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakashinta
            WHERE yhtio        = '$kukarow[yhtio]'
            and asiakas_ryhma != ''
            and ryhma         != ''
            and ytunnus        = ''
            and asiakas        = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY asiakas_ryhma, ryhma, valkoodi, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 8AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakashinta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  // 9A. asiakas.piiri tuote.aleryhma nettohinta (asiakaspiirin tuotealeryhm�n hinta) laskun valuutassa
  // 9B. asiakas.piiri tuote.aleryhma nettohinta (asiakaspiirin tuotealeryhm�n hinta) yhti�n valuutassa
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), hinta asc, tunnus desc) tunnukset
            FROM asiakashinta
            WHERE yhtio  = '$kukarow[yhtio]'
            and piiri   != ''
            and ryhma   != ''
            and ytunnus  = ''
            and asiakas  = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY piiri, ryhma, valkoodi, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 9A $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakashinta
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  // 10. asiakas.tunnus/asiakas.ytunnus tuote.aleryhm� negatiivinen-aleprosentti (asiakkaan katemyyntihinta netto)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio        = '$kukarow[yhtio]'
            and asiakas        > 0
            and asiakas_ryhma  = ''
            and ryhma         != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and (alennus < 0 or left(alennus,1) = '-')
            GROUP BY asiakas, ryhma, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 10 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakashinta += count($tunnukset);
  }

  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio        = '$kukarow[yhtio]'
            and ytunnus       != ''
            and asiakas_ryhma  = ''
            and ryhma         != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and (alennus < 0 or left(alennus,1) = '-')
            GROUP BY ytunnus, ryhma, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 10 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 11. asiakas.segmentti tuote.aleryhm� negatiivinen-aleprosentti (asiakassegmentin katemyyntihinta netto)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio            = '$kukarow[yhtio]'
            and asiakas_segmentti != ''
            and ryhma             != ''
            and ytunnus            = ''
            and asiakas            = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and (alennus < 0 or left(alennus,1) = '-')
            GROUP BY asiakas_segmentti, ryhma, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 11 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 12. asiakas.ryhm� tuote.aleryhm� negatiivinen-aleprosentti (asiakkaan katemyyntihinta netto)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio        = '$kukarow[yhtio]'
            and asiakas_ryhma != ''
            and ryhma         != ''
            and ytunnus        = ''
            and asiakas        = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and (alennus < 0 or left(alennus,1) = '-')
            GROUP BY asiakas_ryhma, ryhma, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 12 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 13. asiakas.piiri tuote.aleryhm� negatiivinen-aleprosentti (asiakaspiirin katemyyntihinta netto)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999),alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio  = '$kukarow[yhtio]'
            and piiri   != ''
            and ryhma   != ''
            and ytunnus  = ''
            and asiakas  = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and (alennus < 0 or left(alennus,1) = '-')
            GROUP BY piiri, ryhma, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 13 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 14. asiakas.tunnus/asiakas.ytunnus tuote.aleryhm� aleprosentti == 999.99 (asiakkaan myym�l�hinta)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio  = '$kukarow[yhtio]'
            and asiakas  > 0
            and ryhma   != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus  = 999.99
            GROUP BY asiakas, ryhma, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 14 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio  = '$kukarow[yhtio]'
            and ytunnus != ''
            and ryhma   != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus  = 999.99
            GROUP BY ytunnus, ryhma, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 14 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 15A. hinnasto.hinta tuotteen nettohinta hinnastosta laskun valuutassa
  // 15B. hinnasto.hinta tuotteen nettohinta hinnastosta yhti�n valuutassa
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM hinnasto
            WHERE yhtio  = '$kukarow[yhtio]'
            and tuoteno != ''
            and laji     in ('N', 'E')
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY laji, tuoteno, valkoodi, maa, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 15AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM hinnasto
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $hinnasto += count($tunnukset);
  }

  // 16. tuote.nettohinta (tuotteen nettohinta)
  // Ei siivottavaa

  // 17A. hinnasto.hinta tuotteen bruttohinta hinnastosta laskun valuutassa
  // 17B. hinnasto.hinta tuotteen bruttohinta hinnastosta yhti�n valuutassa
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), tunnus desc) tunnukset
            FROM hinnasto
            WHERE yhtio  = '$kukarow[yhtio]'
            and tuoteno != ''
            and laji     = ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            GROUP BY tuoteno, valkoodi, maa, minkpl
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "HINTA: 17AB $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM hinnasto
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $hinnasto += count($tunnukset);
  }

  // 18. tuote.myyntihinta (tuotteen bruttohinta) yhti�n valuutassa
  // Ei siivottavaa


  //############################################################################################
  //ALENNUKSET:
  //############################################################################################

  // 1. k�ytt�j�n sy�tt�m� EURO-m��r�inen alennus
  // Ei siivottavaa

  // 2. k�ytt�j�n sy�tt�m� EURO-m��r�inen alennus
  // Ei siivottavaa

  // 3. k�ytt�j�n sy�tt�m� alennus
  // Ei siivottavaa

  // 4. k�ytt�j�n sy�tt�m� alennus
  // Ei siivottavaa

  // 5. k�ytt�j�n sy�tt�m� katejuttu
  // Ei siivottavaa

  // 5. asiakas.tunnus/asiakas.ytunnus tuote.tuotenumero aleprosentti (asiakkaan tuotteen alennus)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio  = '$kukarow[yhtio]'
            and asiakas  > 0
            and tuoteno != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus  >= 0
            and alennus  <= 100
            GROUP BY alennuslaji, asiakas, tuoteno, minkpl, monikerta
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "ALE: 5 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio  = '$kukarow[yhtio]'
            and ytunnus != ''
            and tuoteno != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus  >= 0
            and alennus  <= 100
            GROUP BY alennuslaji, ytunnus, tuoteno, minkpl, monikerta
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "ALE: 5 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 6. asiakas.tunnus/asiakas.ytunnus tuote.aleryhm� aleprosentti (asiakkaan tuotealeryhm�n alennus)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio  = '$kukarow[yhtio]'
            and asiakas  > 0
            and ryhma   != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus  >= 0
            and alennus  <= 100
            GROUP BY alennuslaji, asiakas, ryhma, minkpl, monikerta
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "ALE: 6 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio  = '$kukarow[yhtio]'
            and ytunnus != ''
            and ryhma   != ''
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus  >= 0
            and alennus  <= 100
            GROUP BY alennuslaji, ytunnus, ryhma, minkpl, monikerta
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "ALE: 6 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 7. asiakas.segmentti tuote.tuoteno aleprosentti (asiakassegmentin tuotteen alennus)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio            = '$kukarow[yhtio]'
            and asiakas_segmentti != ''
            and tuoteno           != ''
            and ytunnus            = ''
            and asiakas            = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus            >= 0
            and alennus            <= 100
            GROUP BY alennuslaji, asiakas_segmentti, tuoteno, minkpl, monikerta
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "ALE: 7 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 8. asiakas.ryhm� tuote.tuoteno aleprosentti (asiakasryhm�n tuotteen alennus)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio        = '$kukarow[yhtio]'
            and asiakas_ryhma != ''
            and tuoteno       != ''
            and ytunnus        = ''
            and asiakas        = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus        >= 0
            and alennus        <= 100
            GROUP BY alennuslaji, asiakas_ryhma, tuoteno, minkpl, monikerta
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "ALE: 8 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 9. asiakas.piiri tuote.tuoteno aleprosentti (asiakaspiirin tuotteen alennus)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio  = '$kukarow[yhtio]'
            and piiri   != ''
            and tuoteno != ''
            and ytunnus  = ''
            and asiakas  = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus  >= 0
            and alennus  <= 100
            GROUP BY alennuslaji, piiri, tuoteno, minkpl, monikerta
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "ALE: 9 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 10. asiakas.segmentti tuote.aleryhm� aleprosentti (asiakassegmentin tuotealeryhm�n alennus)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio            = '$kukarow[yhtio]'
            and asiakas_segmentti != ''
            and ryhma             != ''
            and ytunnus            = ''
            and asiakas            = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus            >= 0
            and alennus            <= 100
            GROUP BY alennuslaji, asiakas_segmentti, ryhma, minkpl, monikerta
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "ALE: 10 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 11. asiakas.ryhm� tuote.aleryhm� aleprosentti (asiakasryhm�n tuotealeryhm�n alennus)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio        = '$kukarow[yhtio]'
            and asiakas_ryhma != ''
            and ryhma         != ''
            and ytunnus        = ''
            and asiakas        = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus        >= 0
            and alennus        <= 100
            GROUP BY alennuslaji, asiakas_ryhma, ryhma, minkpl, monikerta
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "ALE: 11 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 12. asiakas.piiri tuote.aleryhm� aleprosentti (asiakaspiirin tuotealeryhm�n alennus)
  $query = "SELECT group_concat(tunnus ORDER BY IFNULL(TO_DAYS(current_date)-TO_DAYS(alkupvm),9999999999999), alennus desc, tunnus desc) tunnukset
            FROM asiakasalennus
            WHERE yhtio  = '$kukarow[yhtio]'
            and piiri   != ''
            and ryhma   != ''
            and ytunnus  = ''
            and asiakas  = 0
            and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
            and alennus  >= 0
            and alennus  <= 100
            GROUP BY alennuslaji, piiri, ryhma, minkpl, monikerta
            HAVING count(*) > 1";
  $hresult = pupe_query($query);

  while ($row = mysql_fetch_assoc($hresult)) {
    //echo "ALE: 12 $row[tunnukset]<br>";

    $tunnukset = explode(",", $row["tunnukset"]);
    array_shift($tunnukset);
    if (is_array($tunnukset)) $tunnukset = implode(",", $tunnukset);

    $query = "DELETE FROM asiakasalennus
              WHERE yhtio = '$kukarow[yhtio]'
              AND tunnus  in ({$tunnukset})";
    $result = pupe_query($query);

    $asiakasale += count($tunnukset);
  }

  // 13. tuote.aleryhm� aleprosentti (tuotealeryhm�n perusalennus) (Vain ykk�stason alennus voidaan tallentaa t�h�n)
  // Ei siivottavaa


  //############################################################################################
  //POISTETAAN ER��NTYNEET:
  //############################################################################################

  $query = "DELETE FROM asiakashinta
            WHERE yhtio  = '$kukarow[yhtio]'
            AND loppupvm > '0000-00-00'
            AND loppupvm < current_date";
  $result = pupe_query($query);

  $asiakashinta += mysql_affected_rows();

  $query = "DELETE FROM hinnasto
            WHERE yhtio  = '$kukarow[yhtio]'
            AND loppupvm > '0000-00-00'
            AND loppupvm < current_date";
  $result = pupe_query($query);

  $hinnasto += mysql_affected_rows();

  $query = "DELETE FROM asiakasalennus
            WHERE yhtio  = '$kukarow[yhtio]'
            AND loppupvm > '0000-00-00'
            AND loppupvm < current_date";
  $result = pupe_query($query);

  $asiakasale += mysql_affected_rows();

  echo "<br><br>";
  echo t("Poistettiin %s er��ntynytt� tai duplikaatti hinnastohintaa", "", $hinnasto).".<br>";
  echo t("Poistettiin %s er��ntynytt� tai duplikaatti asiakasalennusta", "", $asiakasale).".<br>";
  echo t("Poistettiin %s er��ntynytt� tai duplikaatti asiakashintaa", "", $asiakashinta).".<br>";
}

require "inc/footer.inc";
