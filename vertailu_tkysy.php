<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "inc/parametrit.inc";

echo "<font class='head'>Vertaa kilpailijoita</font><hr>";

echo "<form method='post' name='tuote'>
    <table>

    <tr><th>Syötä tuotenumero:</th>
      <td><input name='tuoteno' type='text'></td>
      <td><input type='submit' value='Hae'></td>
    </tr>

    </table>
    </form>";

if ($tuoteno != "") {

  $query = "SELECT *
            FROM vertailu_korvaavat
            WHERE tuote1 = '$tuoteno'
            OR tuote2    = '$tuoteno'";
  $resve = mysql_query($query);

  if (mysql_num_rows($resve) != 0) {
    $tuoteno = "";
    while ($rowve = mysql_fetch_array($resve)) {
      $tuoteno .= "'$rowve[tuote1]','$rowve[tuote2]',";
    }
    $tuoteno = substr($tuoteno, 0, -1);
  }
  else {
    $tuoteno = "'$tuoteno'";
  }

  $query = "SELECT * FROM vertailu LIMIT 1";
  $resve = mysql_query($query);

  // tehdään where lauseke dynaamisesti. urgh!
  $where = "";
  for ($i=1; $i<mysql_num_fields($resve)-5; $i++) {
    $where .= mysql_field_name($resve, $i)." in ($tuoteno) or ";
  }

  $where = substr($where, 0, -3); // vika or pois

  $query = "SELECT *
            FROM vertailu
            WHERE $where";
  $res   = mysql_query($query);

  if (mysql_num_rows($res) != 0) {

    echo "<table>";
    echo "<tr>";
    for ($i=1; $i<mysql_num_fields($res)-5; $i++) {
      echo "<th>".mysql_field_name($res, $i)."</th>";
    }
    echo "</tr>";

    while ($rivi = mysql_fetch_array($res)) {

      echo "<tr>";
      for ($i=1; $i<mysql_num_fields($res)-5; $i++) {
        $query = "SELECT *
                  FROM vertailu_korvaavat
                  WHERE tukkuri = '".mysql_field_name($res, $i)."'
                  AND (tuote1 = '$rivi[$i]' or tuote2 = '$rivi[$i]')";
        $resve = mysql_query($query);

        echo "<td valign='top'>";

        // reset array
        $korvaavat = array();

        // jos löyty korvaavia
        if (mysql_num_rows($resve) != 0) {
          // laitetaan kaikki korvaavat arrayseen
          while ($rowve = mysql_fetch_array($resve)) {
            if (!in_array($rowve["tuote1"], $korvaavat)) $korvaavat[]=$rowve["tuote1"];
            if (!in_array($rowve["tuote2"], $korvaavat)) $korvaavat[]=$rowve["tuote2"];
          }
        }
        else {
          // muuten on vaan tää yks tuote
          $korvaavat[]=$rivi[$i];
        }

        // laitetaan tuotteet numerojärjestykseen
        sort($korvaavat);

        // käydään läpi kaikki tuotteet ja haetaan tuotteelle hinta
        foreach ($korvaavat as $tuoteno) {

          if (mysql_field_name($res, $i) == "arwidson") {
            $query = "SELECT myyntihinta hinta FROM tuote WHERE yhtio = 'artr' AND tuoteno = '$tuoteno'";
            $reshi = mysql_query($query);
          }
          else {
            $query = "SELECT * FROM vertailu_hinnat WHERE tukkuri = '".mysql_field_name($res, $i)."' and tuote = '$tuoteno'";
            $reshi = mysql_query($query);
          }

          if (mysql_num_rows($reshi) != 0) {
            $resro = mysql_fetch_array($reshi);
            $hinta = "($resro[hinta]&euro;)";
          }
          else {
            $hinta = "";
          }

          echo "<a href='?tuoteno=".urlencode($tuoteno)."'>$tuoteno</a> $hinta<br>";
        }

        echo "</td>";
      }
      echo "</tr>";
    }
    echo "</table>";
  }
  else {
    echo "<font class='message'>Yhtään tuotetta ei löytynyt!</font>";
  }
}

require "inc/footer.inc"
