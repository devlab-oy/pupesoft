<?php

// DataTables päälle
$pupe_DataTables = 'pullopalautus';

if (@include "../inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

if (!isset($tee)) $tee = "";
if (!isset($viivakoodi)) $viivakoodi = "";

$viivakoodi = str_replace("+", "-", $viivakoodi);

echo "<font class='head'>", t("Pullopalautus"), "</font><hr>";

if ($tee == 'NAYTATILAUS') {
  echo "<font class='head'>", t("Tilausnro"), ": {$tunnus}</font><hr>";
  require "naytatilaus.inc";
  require "inc/footer.inc";
  exit;
}

if ($tee == "HYVITA") {
  if ($viivakoodi != "") {
    $query = "SELECT * FROM sarjanumeroseuranta WHERE yhtio = '{$kukarow['yhtio']}' AND sarjanumero = '{$viivakoodi}'";
    $result = pupe_query($query);
    if (mysql_num_rows($result) > 0) {
      $row = mysql_fetch_assoc($result);

      if ($row['ostorivitunnus'] == "0") {
        $query = "SELECT l.liitostunnus, l.nimi, r.nimitys, r.hinta, DATEDIFF(NOW(), l.toimaika) paivia
                  FROM sarjanumeroseuranta sns
                  INNER JOIN tilausrivi r
                    ON (r.yhtio = sns.yhtio AND r.tunnus = sns.myyntirivitunnus)
                  INNER JOIN lasku l
                    ON (l.yhtio = sns.yhtio AND l.tunnus = r.otunnus)
                  WHERE sns.yhtio = '{$kukarow['yhtio']}'
                    AND sns.sarjanumero = '{$viivakoodi}'";
        $lisatietohaku = pupe_query($query);
        if (mysql_num_rows($lisatietohaku) != 1) {
          die("Tarvittavia tietokannan rivejä ei löytynyt.");
        }

        $lisatiedot = mysql_fetch_assoc($lisatietohaku);

        if ($lisatiedot['hinta'] == 0) {
          $asiakasid = $lisatiedot['liitostunnus'];

          $query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$row['tuoteno']}'";
          $result = pupe_query($query);
          $trow = mysql_fetch_assoc($result);
          $laskutustuoteno = t_tuotteen_avainsanat($trow, "lisatieto_pantinlaskutustuote");

          if ($laskutustuoteno != "" and $laskutustuoteno != "lisatieto_pantinlaskutustuote") {
            $query = "SELECT tunnus
                      FROM lasku
                      WHERE yhtio = '{$kukarow['yhtio']}'
                        AND liitostunnus = '{$asiakasid}'
                        AND tilaustyyppi = 'P'
                        AND tila = 'N'";
            $laskutunnus = executescalar($query);

            $query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '{$kukarow['kuka']}'";
            pupe_query($query);
            $kukarow['kesken'] = "0";

            echo "{$lisatiedot['nimitys']} {$lisatiedot['paivia']} " . t("päivä" . ($lisatiedot['paivia'] != 1 ? "ä" : "")) . " " . t("asiakaalla") . ": {$lisatiedot['nimi']}<br />";

            if ($laskutunnus != "") {
              echo t("Pullo {$viivakoodi} lisättiin palautustilaukselle") . ": {$laskutunnus}<br>";
            }
            else {
              require "luo_myyntitilausotsikko.inc";
              $laskutunnus = luo_myyntitilausotsikko("RIVISYOTTO", $asiakasid, '', '', '', '', '', 'P', '', 'o');

              echo t("Pullon {$viivakoodi} palautuksesta tehtiin uusi palautustilaus") . ": {$laskutunnus}<br>";
            }

            $kukarow['kesken'] = $laskutunnus;

            $query = "SELECT * FROM lasku WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = {$laskutunnus}";
            $result = pupe_query($query);
            $laskurow = mysql_fetch_assoc($result);

            $parametrit = array(
              'kpl'      => -1,
              'laskurow' => $laskurow,
              'trow'     => $trow,
              'tuoteno'  => $trow['tuoteno'],
              'kommentti' => "# {$row['sarjanumero']}",
              'ale1'     => 100,
            );
            $rivit = lisaa_rivi($parametrit);

            $query = "UPDATE sarjanumeroseuranta SET ostorivitunnus = {$rivit[1][0]} WHERE yhtio = '{$kukarow['yhtio']}' AND tunnus = {$row['tunnus']}";
            pupe_query($query);

            if ($row['panttirivitunnus'] != null and $lisatiedot['paivia'] <= 545) {
              # Hyvitetään pantti, jos se on laskutettu eikä palautuksesta ole yli 1,5 vuotta

              $query = "SELECT * FROM tuote WHERE yhtio = '{$kukarow['yhtio']}' AND tuoteno = '{$laskutustuoteno}'";
              $result = pupe_query($query);
              $hrow = mysql_fetch_assoc($result);

              $parametrit = array(
                'kpl'      => -1,
                'laskurow' => $laskurow,
                'trow'     => $hrow,
                'kommentti' => "# {$row['sarjanumero']}",
                'tuoteno'  => $hrow['tuoteno'],
              );
              $rivit = lisaa_rivi($parametrit);

              echo "&nbsp;&nbsp;- Lasku sisältää hyvitysrivin asiakkaalle jo maksetusta pantista.<br/>";
            }

            $query = "UPDATE kuka SET kesken = 0 WHERE yhtio = '{$kukarow['yhtio']}' AND kuka = '{$kukarow['kuka']}'";
            pupe_query($query);
            $kukarow['kesken'] = "0";

            $viivakoodi = "";
          }
          else {
            echo "<font class='error'>" . t("Tuotteen tiedoista puuttuu tuotenumero, jolla pantin laskutus tehdään.") . "</font><br>";
          }
        }
        else {
          echo "<font class='error'>" . t("Sarjanumerolla oleva pullo on merkitty noudossa myydyksi.") . " $viivakoodi</font><br>";
        }
      }
      else {
        echo "<font class='error'>" . t("Sarjanumerolla oleva pullo on jo merkitty palauteksi.") . " $viivakoodi</font><br>";
      }
    }
    else {
      echo "<font class='error'>" . t("Annettua sarjanumeroa ei löydy.") . " $viivakoodi</font><br>";
    }
  }
  else {
    echo "<font class='error'>" . t("Viivakoodi on annettava") . "</font><br>";
  }

  echo "<br>";
}

pupe_DataTables(array(array($pupe_DataTables, 5, 5, false, false)));

echo "<table class='display dataTable' id='$pupe_DataTables'>";

echo "<thead>";
echo "<tr>";
echo "<th>", t("asiakas"), "</th>";
echo "<th>", t("tilausnumero"), "</th>";
echo "<th>", t("pvm"), "</th>";
echo "<th>", t("Pulloja"), "</th>";
echo "<th style='visibility: hidden'></th>";
echo "</tr>";

echo "<tr>";
echo "<td><input type='text' class='search_field' name='search_asiakas'></td>";
echo "<td><input type='text' class='search_field' name='search_tilausnumero'></td>";
echo "<td><input type='text' class='search_field' name='search_pvm'></td>";
echo "<td><input type='text' class='search_field' name='search_riveja'></td>";
echo "<td style='visibility: hidden'></td>";
echo "</tr>";

echo "</thead>";
echo "<tbody>";

$query = "SELECT l.tunnus, l.nimi, l.tila, l.alatila, l.luontiaika pvm, laskut.riveja
          FROM (
            SELECT l.yhtio, l.tunnus, COUNT(*) riveja
            FROM lasku l
            INNER JOIN tilausrivi r
              ON (r.yhtio = l.yhtio AND r.otunnus = l.tunnus)
            INNER JOIN tuote t
              ON (t.yhtio = l.yhtio AND t.tuoteno = r.tuoteno)
            WHERE l.yhtio = '{$kukarow['yhtio']}'
              AND l.tilaustyyppi = 'P'
              AND l.tila = 'N'
              AND t.pullopanttitarratulostus_kerayksessa = 'T'
            GROUP BY l.yhtio, l.tunnus
          ) laskut
          INNER JOIN lasku l
            ON (l.tunnus = laskut.tunnus AND l.yhtio = laskut.yhtio)";
$result = pupe_query($query);

while ($tulrow = mysql_fetch_assoc($result)) {
  echo "<tr>";

  echo "<td>{$tulrow['nimi']}</td>";
  echo "<td>" . pupe_DataTablesEchoSort($tulrow['tunnus']) . js_openUrlNewWindow("{$palvelin2}raportit/pullopantit.php?tee=NAYTATILAUS&tunnus={$tulrow['tunnus']}", $tulrow["tunnus"], NULL, 1000, 800) . "</td>";
  echo "<td>" . pupe_DataTablesEchoSort($tulrow['pvm']).tv1dateconv($tulrow['pvm']) . "</td>";
  echo "<td>{$tulrow['riveja']}</td>";

  echo "<td class='back'><form method='post' class='myyntiformi' id='myyntiformi_{$tulrow['tunnus']}' action='tilaus_myynti.php'>";
  echo "<input type='hidden' name='kaytiin_otsikolla' value='NOJOO!' />";
  echo "<input type='hidden' name='lopetus' value='{$palvelin2}tilauskasittely/pullonpalautus.php'>
        <input type='hidden' name='mista' value='muokkaatilaus'>
        <input type='hidden' name='toim' value='RIVISYOTTO'>
        <input type='hidden' name='orig_tila' value='{$row["tila"]}'>
        <input type='hidden' name='orig_alatila' value='{$row["alatila"]}'>
        <input type='hidden' class='tilausnumero' name='tilausnumero' value='$tulrow[tunnus]'>
        <input type='hidden' name='kaytiin_otsikolla' value='NOJOO!' />
        <input type='submit' class='' name='RIVISYOTTO' value='" . t("Rivisyöttöön") . "'>";
  echo "</form></td>";

  echo "</tr>";
}

echo "</tbody>";
echo "</table>";

echo "<form name=tarra method='post' autocomplete='off'>";
echo "<input type='hidden' id='tee' name='tee' value='HYVITA'>";
echo "<table>";

echo "<tr>";
echo "<th>", t("Viivakoodi"), ":</th>";
echo "<td><input type='text' id='viivakoodi' name='viivakoodi' value='{$viivakoodi}'></td>";
echo "<td class='back'><input type='submit' value='", t("Merkitse palautetuksi"), "'></td>";
echo "</tr>";

echo "</table>";
echo "</form>";

// kursorinohjausta
$formi  = "tarra";
$kentta = "viivakoodi";

require "inc/footer.inc";
