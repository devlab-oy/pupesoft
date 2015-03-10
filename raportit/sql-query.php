<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta JA master kantaa *//
$useslave = 1;

//Tehdään tällanen replace jotta parametric.inc ei poista merkkejä
$sqlapu = $_POST["sqlhaku"];

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/", "", $_POST["kaunisnimi"]);
}

// Ei käytetä pakkausta
$compression = FALSE;

require "../inc/parametrit.inc";

$toim = isset($toim) ? $toim : "";

ini_set("memory_limit", "2G");

//Ja tässä laitetaan ne takas
$sqlhaku = $sqlapu;

$tee = isset($tee) ? $tee : "";

if ($tee == "lataa_tiedosto") {
  readfile("/tmp/" . $tmpfilenimi);
  exit;
}

if ($tee == "aja_tallennettu_query" and isset($valittu_query)) {
  $muistista = muistista("sql-query", $valittu_query, false, true);

  $haku["nimi"] = $valittu_query;
  $haku["kuvaus"] = $muistista["kuvaus"];
  $sqlhaku = $muistista["query"];

  $tee = "";
}

if ($toim == "SUPER") {
  $haku["nimi"] = isset($haku["nimi"]) ? trim($haku["nimi"]) : "";
  $haku["kuvaus"] = isset($haku["kuvaus"]) ? trim($haku["kuvaus"]) : "";

  if ($tee == "tallenna_haku" and empty($haku["nimi"])) {
    $error = t("Haulle täytyy antaa nimi");

    $tee = "";
  }

  if ($tee == "tallenna_haku") {
    $muistettava = array(
      "query" => $sqlhaku
    );

    if (muistiin("sql-query", $haku["nimi"], $muistettava, "", $haku["kuvaus"])) {
      $success = t("Haun tallennus onnistui");

      $haku["nimi"] = "";
      $haku["kuvaus"] = "";
    }

    $tee = "";
  }
}

if ($tee == "") {
  echo "<font class='head'>".t("SQL-raportti").":</font><hr>";

  $muisti_query = "SELECT *
                   FROM muisti
                   WHERE yhtio = 'Mesta'
                   AND haku = 'sql-query'";

  $muisti_result = pupe_query($muisti_query);

  if (mysql_num_rows($muisti_result) > 0) {
    $valittu_query = isset($valittu_query) ? $valittu_query : "";

    echo "<form method='post'>";
    echo "<input type='hidden' name='tee' value='aja_tallennettu_query'>";
    echo "<table>";
    echo "<tr>";
    echo "<th>" . t("Aja tallennettu haku") . "</th>";
    echo "<td>";
    echo "<select name='valittu_query' onchange='submit()'>";

    while ($muisti_row = mysql_fetch_assoc($muisti_result)) {
      $sel = $muisti_row["nimi"] == $valittu_query ? "selected" : "";

      echo "<option value='{$muisti_row["nimi"]}' {$sel}>{$muisti_row["nimi"]}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";
    echo "</form>";
    echo "<br>";
  }

  // käsitellään syötetty arvo nätiksi...
  $sqlhaku = stripslashes(strtolower(trim($sqlhaku)));

  // laitetaan aina kuudes merkki spaceks.. safetymeasure ni ei voi olla ku select
  if (substr($sqlhaku, 6, 1) != " ") {
    $sqlhaku = substr($sqlhaku, 0, 6)." ".substr($sqlhaku, 6);
  }

  echo "<form name='sql' method='post' autocomplete='off'>";
  echo "<table>";
  echo "<tr><th>".t("Syötä SQL kysely")."</th></tr>";
  echo "<tr><td><textarea cols='100' rows='15' rows='15' name='sqlhaku' style='font-family:\"Courier New\",Courier'>$sqlhaku</textarea></td></tr>";
  echo "<tr><td class='back'><input type='submit' value='".t("Suorita")."'></td></tr>";
  echo "</table>";
  echo "</form>";

  // eka sana pitää olla select... safe enough kai.
  if (substr($sqlhaku, 0, strpos($sqlhaku, " ")) != 'select') {
    echo "<font class='error'>".t("Ainoastaan SELECT lauseet sallittu")."!</font><br>";
    $sqlhaku = "";
  }

  if ($sqlhaku != '') {

    $result = pupe_query($sqlhaku);

    if (mysql_num_rows($result) > 0) {

      require 'inc/ProgressBar.class.php';

      include 'inc/pupeExcel.inc';

      $worksheet    = new pupeExcel();
      $format_bold = array("bold" => TRUE);
      $excelrivi    = 0;
      $sarakemaara = mysql_num_fields($result);

      for ($i=0; $i < $sarakemaara; $i++) $worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result, $i))), $format_bold);
      $excelrivi++;

      $bar = new ProgressBar();
      $bar->initialize(mysql_num_rows($result));

      while ($row = mysql_fetch_row($result)) {

        $bar->increase();

        for ($i=0; $i < $sarakemaara; $i++) {
          if (mysql_field_type($result, $i) == 'real') {
            $worksheet->writeNumber($excelrivi, $i, sprintf("%.02f", $row[$i]));
          }
          else {
            $worksheet->writeString($excelrivi, $i, $row[$i]);
          }
        }
        $excelrivi++;
      }

      $excelnimi = $worksheet->close();

      echo "<br><br><table>";
      echo "<tr><th>".t("Tallenna tulos").":</th><td class='back'>";
      echo "<form method='post' class='multisubmit'>";
      echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
      echo "<input type='hidden' name='kaunisnimi' value='SQLhaku.xlsx'>";
      echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
      echo "<input type='submit' value='".t("Tallenna")."'></form></td></tr>";
      echo "</table><br>";

      if ($toim == "SUPER") {
        if (isset($error) and !empty($error)) {
          echo "<span class='error'>{$error}</span>";
        }

        if (isset($success) and !empty($success)) {
          echo "<span class='ok'>{$success}</span>";
        }

        echo "<form method='post'>";
        echo "<input type='hidden' name='tee' value='tallenna_haku'>";
        echo "<input type='hidden' name='sqlhaku' value='{$sqlhaku}'>";
        echo "<table>";
        echo "<tr>";
        echo "<th><label for='haku_nimi'>" . t("Nimi") . "</label></th>";
        echo "<td>";
        echo "<input type='textbox' id='haku_nimi' name='haku[nimi]' style='width:98%;'
                     value='{$haku["nimi"]}' required>";
        echo "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<th><label for='haku_kuvaus'>" . t("Haun kuvaus") . "</label></th>";
        echo "<td>";
        echo "<textarea id='haku_kuvaus' name='haku[kuvaus]' rows='10'
                        cols='40'>{$haku["kuvaus"]}</textarea>";
        echo "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td class='back'>";
        echo "<input id='tallenna_haku' type='submit' value='" . t("Tallenna haku") . "'>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</form>";
      }

      echo "<font class='message'>".t("Haun tulos")." ".mysql_num_rows($result)." ".t("riviä").".</font><br>";

      mysql_data_seek($result, 0);

      echo "<pre>";

      for ($i = 0; $i < $sarakemaara; $i++) {
        echo mysql_field_name($result, $i)."\t";
      }
      echo "\n";

      while ($row = mysql_fetch_array($result)) {

        for ($i=0; $i<$sarakemaara; $i++) {

          // desimaaliluvuissa muutetaan pisteet pilkuiks...
          if (mysql_field_type($result, $i) == 'real') {
            echo str_replace(".", ",", $row[$i])."\t";
          }
          else {
            echo str_replace(array("\n", "\r", "<br>"), " ", $row[$i])."\t";
          }
        }
        echo "\n";
      }
      echo "</pre>";
    }

    // kursorinohjausta
    $formi  = "sql";
    $kentta = "sqlhaku";
  }

  require "../inc/footer.inc";
}
