<?php

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Kohdista viitesuoritus korkoihin")."</font><hr>";

echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
  <!--

  function toggleAll(toggleBox) {

    var currForm = toggleBox.form;
    var isChecked = toggleBox.checked;
    var nimi = toggleBox.name;

    for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
      if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
        currForm.elements[elementIdx].checked = isChecked;
      }
    }
  }

  //-->
  </script>";

if (isset($tilino)) {

  // tutkaillaan tiliä
  $query = "SELECT *
            FROM tili
            WHERE yhtio = '$kukarow[yhtio]'
            AND tilino  = '$tilino'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Kirjanpidon tiliä ei löydy")."!</font> $tilino<br><br>";
    unset($viite);
    unset($tilino);
    unset($valitut);
  }
  else {
    $tilirow = mysql_fetch_assoc($result);
  }
}

if (isset($tapa) and $tapa == 'paalle') {
  if (isset($viite)) {
    // tutkaillaan suoritusta
    $query = "SELECT suoritus.*
              FROM suoritus
              JOIN yriti ON (suoritus.yhtio = yriti.yhtio
                AND suoritus.tilino  = yriti.tilino
                AND yriti.factoring != '')
              WHERE suoritus.yhtio   = '$kukarow[yhtio]'
              AND suoritus.kohdpvm   = '0000-00-00'
              AND suoritus.ltunnus   > 0
              AND suoritus.summa    != 0
              AND suoritus.viite     LIKE '$viite%'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      echo "<font class='error'>".t("Sopivia suorituksia ei löydy")."!</font><br><br>";
      unset($viite);
      unset($tilino);
    }
    else {
      echo "<form method='post' autocomplete='off'>";
      echo "<input name='tilino' type='hidden' value='$tilino'>";
      echo "<input name='tapa'   type='hidden' value='$tapa'>";

      echo "<table>";
      echo "<tr>";
      echo "<th>".t("Viite")."</th>";
      echo "<th>".t("Asiakas")."</th>";
      echo "<th>".t("Summa")."</th>";
      echo "<th>".t("Valitse")."</th>";
      echo "</tr>";

      while ($suoritusrow = mysql_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$suoritusrow["viite"]}</td>";
        echo "<td>{$suoritusrow["nimi_maksaja"]}</td>";
        echo "<td>{$suoritusrow["summa"]}</td>";
        echo "<td><input name='valitut[]' type='checkbox' value='{$suoritusrow["tunnus"]}' CHECKED></td>";
        echo "</tr>";
      }

      echo "</table><br>";

      echo "<input type='checkbox' name='val' onclick='toggleAll(this);' CHECKED> Ruksaa kaikki<br><br>";
      echo "<input type='submit' value='".t("Kohdista")."'>";
      echo "</form>";
    }

  }

  if (isset($tilino) and is_array($valitut)) {

    echo "Kohdistan!<br>";

    foreach ($valitut as $valittu) {
      $query = "SELECT *
                from suoritus
                where yhtio = '$kukarow[yhtio]'
                and kohdpvm = '0000-00-00'
                and ltunnus > 0
                and tunnus  = '$valittu'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        echo "<font class='error'>".t("Suoritus on kadonnut tai se on käytetty")."!</font><br><br>";
      }
      else {
        $suoritusrow=mysql_fetch_assoc($result);
        // päivitetään suoritus
        $query = "UPDATE suoritus
                  SET kohdpvm = now()
                  WHERE yhtio = '$kukarow[yhtio]'
                  AND tunnus  = '$suoritusrow[tunnus]'";
        $result = pupe_query($query);

        if (mysql_affected_rows() == 0) {
          echo "<font class='error'>".t("Suorituksen päivitys epäonnistui")."! $tunnus</font><br>";
        }

        // tehdään kirjanpitomuutokset
        $query = "UPDATE tiliointi SET
                  tilino      = '$tilino',
                  selite      = '".t("Kohdistettiin korkoihin")."'
                  WHERE yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$suoritusrow[ltunnus]'";
        $result = pupe_query($query);

        if (mysql_affected_rows() == 0) {
          echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehdä! Korjaa kirjanpito käsin")."!</font><br>";
        }
      }
    }
    unset($viite);
    unset($tapa);
  }
}
if (isset($tapa) and $tapa == 'pois') {
  if (isset($viite)) {
    $query = "SELECT suoritus.*, tiliointi.summa
              from suoritus, yriti, tiliointi
              where suoritus.yhtio = '$kukarow[yhtio]'
              and suoritus.kohdpvm > '0000-00-00'
              and suoritus.ltunnus > 0
              and suoritus.summa   = 0
              and suoritus.viite   like '$viite%'
              and suoritus.yhtio   = yriti.yhtio
              and suoritus.tilino  = yriti.tilino
              and yriti.factoring != ''
              and tiliointi.yhtio  = suoritus.yhtio
              and tiliointi.selite = '".t("Kohdistettiin korkoihin")."'
              and tiliointi.tunnus = suoritus.ltunnus";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      echo "<font class='error'>".t("Sopivia korkovientejä ei löydy")."!</font><br><br>";
      unset($viite);
      unset($tilino);
    }
    else {
      echo "<form method='post' autocomplete='off'>";
      echo "<input name='tilino' type='hidden' value='$yhtiorow[factoringsaamiset]'>";
      echo "<input name='tapa'   type='hidden' value='$tapa'>";
      echo "<table>";
      echo "<tr>";
      echo "<th>".t("Viite")."</th>";
      echo "<th>".t("Asiakas")."</th>";
      echo "<th>".t("Summa")."</th>";
      echo "<th>".t("Valitse")."</th>";
      echo "</tr>";

      while ($suoritusrow = mysql_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$suoritusrow["viite"]}</td>";
        echo "<td>{$suoritusrow["nimi_maksaja"]}</td>";
        echo "<td>{$suoritusrow["summa"]}</td>";
        echo "<td><input name='valitut[]' type='checkbox' value='{$suoritusrow["tunnus"]}' CHECKED></td>";
        echo "</tr>";
      }
      echo "</table><br><input type='submit' value='".t("Peru korkoviennit")."'>";
      echo "</form>";
    }
  }

  if (isset($tilino) and is_array($valitut)) {
    echo "Kohdistan!<br>";

    foreach ($valitut as $valittu) {
      $query = "SELECT *
                FROM suoritus
                WHERE yhtio = '$kukarow[yhtio]'
                AND kohdpvm > '0000-00-00'
                and ltunnus > 0
                AND tunnus  = '$valittu'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        echo "<font class='error'>".t("Suoritus on kadonnut tai se ei ole enää käytetty")."!</font><br><br>";
      }
      else {
        $suoritusrow = mysql_fetch_assoc($result);

        // Etsitään kirjanpitotapahtuma
        $query = "SELECT summa
                  from tiliointi
                  where yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$suoritusrow[ltunnus]'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) == 0) {
          echo "<font class='error'>".t("Tiliöinti on kadonnut")."!</font><br><br>";
        }
        else {
          $tiliointirow = mysql_fetch_assoc($result);

          $query = "SELECT pankki_tili
                    from factoring
                    where yhtio     = '$kukarow[yhtio]'
                    and pankki_tili = '$suoritusrow[tilino]'";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 0) {
            $tilino = $yhtiorow['myyntisaamiset'];
          }
          else {
            $tilino = $yhtiorow['factoringsaamiset'];
          }

          // päivitetään suoritus
          $query = "UPDATE suoritus
                    SET kohdpvm = '0000-00-00',
                    summa       = -1 * $tiliointirow[summa]
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tunnus  = '$suoritusrow[tunnus]'";
          $result = pupe_query($query);

          if (mysql_affected_rows() == 0) {
            echo "<font class='error'>".t("Suorituksen päivitys epäonnistui")."! $tunnus</font><br>";
          }

          // tehdään kirjanpitomuutokset
          $query = "UPDATE tiliointi
                    SET tilino = '$tilino',
                    selite      = '".t("Korjattu suoritus")."'
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tunnus  = '$suoritusrow[ltunnus]'";
          $result = pupe_query($query);

          if (mysql_affected_rows() == 0) {
            echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehdä! Korjaa kirjanpito käsin")."!</font><br>";
          }
        }
      }
    }

    unset($viite);
    unset($tapa);
  }
}

if (!isset($tapa)) {
  echo "<form method='post' autocomplete='off'>";
  echo "<table>";
  echo "<tr><th>".t("Suorituksia siirretään korkoihin")."</th>";
  echo "<td><input type='radio' name='tapa' value='paalle' checked></td></tr>";
  echo "<tr><th>".t("Suorituksia siirretään koroista normaaleiksi suorituksiksi")."</th>";
  echo "<td><input type='radio' name='tapa' value='pois'></td></tr>";
  echo "<tr><td class='back'><input name='subnappi' type='submit' value='".t("Valitse")."'></td></tr>";
  echo "</table>";
  echo "</form>";
}
else {
  if (!isset($viite)) {
    if ($tapa == 'paalle') {
      echo "<form name='eikat' method='post' autocomplete='off'>";
      echo "<input type='hidden' name='tapa' value='$tapa'><table>";
      echo "<tr><th>".t("Anna viitteen alku suorituuksista, jotka haluat käsiteltäviksi")."</th>";
      echo "<td><input type='text' name='viite' value = '50009'></td></tr>";
      echo "<tr><th>".t("Mille tilille nämä varat tiliöidään")."</th>";
      echo "<td><input type='text' name='tilino'></td></tr>";
      echo "<tr><td class='back'><input name='subnappi' type='submit' value='".t("Hae suoritukset")."'></td></tr>";
      echo "</table>";
      echo "</form>";
    }
    else {
      echo "<form name='eikat' method='post' autocomplete='off'>";
      echo "<input type='hidden' name='tapa' value='$tapa'><table>";
      echo "<tr><th>".t("Anna viitteen alku korkovienneistä, jotka haluat käsiteltäviksi")."</th>";
      echo "<td><input type='text' name='viite'></td></tr>";
      echo "<tr><td class='back'><input name='subnappi' type='submit' value='".t("Hae korkoviennit")."'></td></tr>";
      echo "</table>";
      echo "</form>";
    }
  }
}

// kursorinohjausta
$formi = "eikat";
$kentta = "viite";

require "inc/footer.inc";
