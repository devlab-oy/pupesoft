<?php

$pupe_DataTables = "asiakaslista";

require "inc/parametrit.inc";

if (!isset($tee)) $tee = '';

echo "<font class='head'>", t("Kopioi asiakkaan alennuksia"), "</font><hr>";

if ($tee == "write") {

  $uusiytunnus = mysql_real_escape_string(trim($uusiytunnus));
  $uusitunnus = mysql_real_escape_string(trim($uusitunnus));

  if ($uusiytunnus != '' and $uusitunnus != '') {
    echo "<font class='error'>", t("Valitse joko ytunnus tai asiakastunnus"), "!</font><br /><br />";
    $tee = 'edit';
  }
  else {

    $query = "SELECT tunnus
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND ((ytunnus = '{$uusiytunnus}' AND ytunnus != '') OR (tunnus = '{$uusitunnus}'))";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      $query = "(SELECT DISTINCT if(asiakasalennus.asiakas = 0, '', asiakasalennus.asiakas) asiakas, asiakasalennus.ytunnus, asiakasalennus.tuoteno, asiakasalennus.ryhma, asiakasalennus.alennus, asiakasalennus.alennuslaji, asiakasalennus.minkpl, asiakasalennus.alkupvm, asiakasalennus.loppupvm
                     FROM asiakas
                     JOIN asiakasalennus USE INDEX (yhtio_asiakas_ryhma) ON (asiakasalennus.yhtio = asiakasalennus.yhtio
                       AND asiakasalennus.asiakas  = asiakas.tunnus
                       and asiakasalennus.asiakas != '')
                     WHERE asiakas.yhtio           = '{$kukarow['yhtio']}'
                     AND asiakas.tunnus            = '{$tunnus}'
                     AND asiakas.laji             != 'P')

                     UNION

                     (SELECT DISTINCT if(asiakasalennus.asiakas = 0, '', asiakasalennus.asiakas) asiakas, asiakasalennus.ytunnus, asiakasalennus.tuoteno, asiakasalennus.ryhma, asiakasalennus.alennus, asiakasalennus.alennuslaji, asiakasalennus.minkpl, asiakasalennus.alkupvm, asiakasalennus.loppupvm
                     FROM asiakas
                     JOIN asiakasalennus USE INDEX (yhtio_ytunnus_ryhma) ON (asiakasalennus.yhtio = asiakasalennus.yhtio
                       AND asiakasalennus.ytunnus  = asiakas.ytunnus
                       AND asiakasalennus.ytunnus != '')
                     WHERE asiakas.yhtio           = '{$kukarow['yhtio']}'
                     AND asiakas.tunnus            = '{$tunnus}'
                     AND asiakas.laji             != 'P')

                     ORDER BY 2";
      $result = pupe_query($query);

      while ($trow = mysql_fetch_assoc($result)) {
        $query = "INSERT INTO asiakasalennus SET
                  yhtio       = '{$kukarow['yhtio']}',
                  ytunnus     = '{$uusiytunnus}',
                  asiakas     = '{$uusitunnus}',
                  ryhma       = '{$trow['ryhma']}',
                  tuoteno     = '{$trow['tuoteno']}',
                  alennus     = '{$trow['alennus']}',
                  alennuslaji = '{$trow['alennuslaji']}',
                  minkpl      = '{$trow['minkpl']}',
                  alkupvm     = '{$trow['alkupvm']}',
                  loppupvm    = '{$trow['loppupvm']}',
                  laatija     = '{$kukarow['kuka']}',
                  luontiaika  = now()";
        $insresult = pupe_query($query);
      }

      echo "<font class='message'>", t("Asiakasalennukset kopioitu"), ".</font><br /><br />";
      $tee = "";
    }
    else {
      echo "<font class='error'>", t("Asiakasta ei löydy"), "!</font><br /><br />";
      $tee = 'edit';
    }
  }
}

if ($tee == "edit") {

  $query = "(SELECT DISTINCT if(asiakasalennus.asiakas = 0, '', asiakasalennus.asiakas) asiakas, asiakasalennus.ytunnus, asiakasalennus.tuoteno, asiakasalennus.ryhma, asiakasalennus.alennus, asiakasalennus.alennuslaji, if(asiakasalennus.minkpl != 0, asiakasalennus.minkpl, '') minkpl, if(asiakasalennus.alkupvm != '0000-00-00', asiakasalennus.alkupvm, '') alkupvm, if(asiakasalennus.loppupvm != '0000-00-00', asiakasalennus.loppupvm, '') loppupvm
                 FROM asiakas
                 JOIN asiakasalennus USE INDEX (yhtio_asiakas_ryhma) ON (asiakasalennus.yhtio = asiakasalennus.yhtio
                   AND asiakasalennus.asiakas  = asiakas.tunnus
                   and asiakasalennus.asiakas != '')
                 WHERE asiakas.yhtio           = '{$kukarow['yhtio']}'
                 AND asiakas.tunnus            = '{$tunnus}'
                 AND asiakas.laji             != 'P')

                 UNION

                 (SELECT DISTINCT if(asiakasalennus.asiakas = 0, '', asiakasalennus.asiakas) asiakas, asiakasalennus.ytunnus, asiakasalennus.tuoteno, asiakasalennus.ryhma, asiakasalennus.alennus, asiakasalennus.alennuslaji, if(asiakasalennus.minkpl != 0, asiakasalennus.minkpl, '') minkpl, if(asiakasalennus.alkupvm != '0000-00-00', asiakasalennus.alkupvm, '') alkupvm, if(asiakasalennus.loppupvm != '0000-00-00', asiakasalennus.loppupvm, '') loppupvm
                 FROM asiakas
                 JOIN asiakasalennus USE INDEX (yhtio_ytunnus_ryhma) ON (asiakasalennus.yhtio = asiakasalennus.yhtio
                   AND asiakasalennus.ytunnus  = asiakas.ytunnus
                   AND asiakasalennus.ytunnus != '')
                 WHERE asiakas.yhtio           = '{$kukarow['yhtio']}'
                 AND asiakas.tunnus            = '{$tunnus}'
                 AND asiakas.laji             != 'P')

                 ORDER BY 1, 2, 3, 4";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {

    echo "<form method='post'>";
    echo "<input type='hidden' name='tee' value='write'>";
    echo "<input type='hidden' name='tunnus' value='{$tunnus}'>";

    echo "<table>";
    echo "<tr>";
    echo "<th>", t("Syötä ytunnus"), "</th>";
    echo "<td><input type = 'text' size='15' name = 'uusiytunnus'></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<th>", t("tai"), " ", t("Syötä Asiakastunnus"), "</th>";
    echo "<td><input type = 'text' size='15' name = 'uusitunnus'></td>";
    echo "</tr>";
    echo "</table><br>";

    echo "<table>";
    echo "<tr>";
    echo "<th>", t("Asiakas"), "</th>";
    echo "<th>", t("Ytunnus"), "</th>";
    echo "<th>", t("Alennusryhma"), "</th>";
    echo "<th>", t("Tuotenumero"), "</th>";
    echo "<th>", t("Alennusprosentti"), "</th>";
    echo "<th>", t("Alennuslaji"), "</th>";
    echo "<th>", t("Minkpl"), "</th>";
    echo "<th>", t("Alkupvm"), "</th>";
    echo "<th>", t("Loppupvm"), "</th>";
    echo "</tr>";

    while ($row = mysql_fetch_assoc($result)) {
      echo "<tr class='aktiivi'>";
      echo "<td>{$row['asiakas']}</td>";
      echo "<td>{$row['ytunnus']}</td>";
      echo "<td>{$row['ryhma']}</td>";
      echo "<td>{$row['tuoteno']}</td>";
      echo "<td>{$row['alennus']}</td>";
      echo "<td>", t("Ale"), "{$row['alennuslaji']}</td>";
      echo "<td>{$row['minkpl']}</td>";
      echo "<td>{$row['alkupvm']}</td>";
      echo "<td>{$row['loppupvm']}</td>";
      echo "</tr>";
    }

    echo "</table><br />";

    echo "<input type='submit' value='", t("Kopioi"), "'>";
    echo "</form>";

  }
  else {
    echo "<br /><br />", t("Tällä asiakaalla ei ole yhtään asiakasalennusta"), "!<br /><br />";
    $tee = '';
  }
}

if ($tee == '') {

  pupe_DataTables(array(array($pupe_DataTables, 7, 7)));

  $query = "(SELECT DISTINCT
                 asiakas.tunnus,
                 concat(asiakas.nimi, '<br>', asiakas.toim_nimi) nimi,
                 concat(asiakas.nimitark, '<br>', asiakas.toim_nimitark) nimitark,
                 concat(asiakas.postitp, '<br>', asiakas.toim_postitp) postitp,
                 concat(asiakas.ytunnus) ytunnus,
                 concat(asiakas.ovttunnus, '<br>', asiakas.toim_ovttunnus) ovttunnus,
                 asiakas.asiakasnro
                 FROM asiakas
                 JOIN asiakasalennus USE INDEX (yhtio_asiakas_ryhma) ON (asiakasalennus.yhtio = asiakasalennus.yhtio
                   AND asiakasalennus.asiakas  = asiakas.tunnus
                   AND asiakasalennus.asiakas != '')
                 WHERE asiakas.yhtio           = '{$kukarow['yhtio']}'
                 AND asiakas.laji             != 'P')

                 UNION

                 (SELECT DISTINCT
                 asiakas.tunnus,
                 concat(asiakas.nimi, '<br>', asiakas.toim_nimi) nimi,
                 concat(asiakas.nimitark, '<br>', asiakas.toim_nimitark) nimitark,
                 concat(asiakas.postitp, '<br>', asiakas.toim_postitp) postitp,
                 concat(asiakas.ytunnus) ytunnus,
                 concat(asiakas.ovttunnus, '<br>', asiakas.toim_ovttunnus) ovttunnus,
                 asiakas.asiakasnro
                 FROM asiakas
                 JOIN asiakasalennus USE INDEX (yhtio_ytunnus_ryhma) ON (asiakasalennus.yhtio = asiakasalennus.yhtio
                   AND asiakasalennus.ytunnus  = asiakas.ytunnus
                   AND asiakasalennus.ytunnus != '')
                 WHERE asiakas.yhtio           = '{$kukarow['yhtio']}'
                 AND asiakas.laji             != 'P')

                 ORDER BY 2";
  $result = pupe_query($query);

  echo "<table class='display dataTable' id='{$pupe_DataTables}'>";
  echo "<thead>";
  echo "<tr>";
  echo "<th>", t("Nimi"), "</th>";
  echo "<th>", t("Nimitark"), "</th>";
  echo "<th>", t("Postitp"), "</th>";
  echo "<th>", t("Ytunnus"), "</th>";
  echo "<th>", t("Ovttunnus"), "</th>";
  echo "<th>", t("Asiakasnro"), "</th>";
  echo "<th>", t("Asiakastunnus"), "</th>";
  echo "</tr>";
  echo "<tr>";
  echo "<td><input type='text' class='search_field' name='search_nimi'></td>";
  echo "<td><input type='text' class='search_field' name='search_nimitark'></td>";
  echo "<td><input type='text' class='search_field' name='search_postitp'></td>";
  echo "<td><input type='text' class='search_field' name='search_ytunnus'></td>";
  echo "<td><input type='text' class='search_field' name='search_ovttunnus'></td>";
  echo "<td><input type='text' class='search_field' name='search_asiakasnro'></td>";
  echo "<td><input type='text' class='search_field' name='search_tunnus'></td>";
  echo "</tr>";
  echo "</thead>";
  echo "<tbody>";

  while ($row = mysql_fetch_assoc($result)) {
    echo "<tr class='aktiivi'>";
    echo "<td><a href='?tunnus={$row['tunnus']}&tee=edit'>{$row['nimi']}</a></td>";
    echo "<td>{$row['nimitark']}</td>";
    echo "<td>{$row['postitp']}</td>";
    echo "<td>{$row['ytunnus']}</td>";
    echo "<td>{$row['ovttunnus']}</td>";
    echo "<td>{$row['asiakasnro']}</td>";
    echo "<td>{$row['tunnus']}</td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}

require "inc/footer.inc";
