<?php

require "inc/parametrit.inc";

echo "<font class='head'>Poista vertailusta koko tuoteryhm‰</font><hr>";

if ($try != "") {

  // haetaan kaikki tuotteet tuoteryhm‰st‰
  $query = "select tuoteno from tuote where yhtio='$kukarow[yhtio]' and try='$try' order by tuoteno";
  $resul = mysql_query($query) or die($query);

  echo "<font class='message'>Tuoteryhm‰ss‰ on ".mysql_num_rows($resul)." tuotetta.</font><br>";
  flush();

  $query = "lock table vertailu write, vertailu_hinnat write, vertailu_korvaavat write";
  $itres = mysql_query($query) or die($query);

  // poistetaan eka korvaavat ja hinnat
  $hinnat = 0;
  $korvat = 0;

  echo "<font class='message'>Poistetaan eka korvaavat ja hinnat.</font><br>";
  flush();

  // k‰yd‰‰n l‰pi tuotteet
  while ($kala = mysql_fetch_array($resul)) {

    // haetaan tuotteen vertailut
    $query = "select * from vertailu where arwidson = '$kala[tuoteno]'";
    $res   = mysql_query($query) or die($query);

    while ($rivi = mysql_fetch_array($res)) {

      for ($i=1; $i<mysql_num_fields($res)-5; $i++) {

        if (trim($rivi[$i]) != "") {

          $query = "select * from vertailu_korvaavat where tukkuri='".mysql_field_name($res, $i)."' and tuote1='$rivi[$i]'";
          $resve = mysql_query($query) or die($query);

          while ($korvarivi = mysql_fetch_array($resve)) {
            $query = "delete from vertailu_hinnat where tukkuri='".mysql_field_name($res, $i)."' and tuote='$korvarivi[tuote1]'";
            $resvv = mysql_query($query) or die($query);
            $hinnat += mysql_affected_rows();

            $query = "delete from vertailu_hinnat where tukkuri='".mysql_field_name($res, $i)."' and tuote='$korvarivi[tuote2]'";
            $resvv = mysql_query($query) or die($query);
            $hinnat += mysql_affected_rows();
          }

          $query = "select * from vertailu_korvaavat where tukkuri='".mysql_field_name($res, $i)."' and tuote2='$rivi[$i]'";
          $resve = mysql_query($query) or die($query);

          while ($korvarivi = mysql_fetch_array($resve)) {
            $query = "delete from vertailu_hinnat where tukkuri='".mysql_field_name($res, $i)."' and tuote='$korvarivi[tuote1]'";
            $resvv = mysql_query($query) or die($query);
            $hinnat += mysql_affected_rows();

            $query = "delete from vertailu_hinnat where tukkuri='".mysql_field_name($res, $i)."' and tuote='$korvarivi[tuote2]'";
            $resvv = mysql_query($query) or die($query);
            $hinnat += mysql_affected_rows();
          }

          $query = "delete from vertailu_korvaavat where tukkuri='".mysql_field_name($res, $i)."' and tuote1='$rivi[$i]'";
          $resve = mysql_query($query) or die($query);
          $korvat += mysql_affected_rows();

          $query = "delete from vertailu_korvaavat where tukkuri='".mysql_field_name($res, $i)."' and tuote2='$rivi[$i]'";
          $resve = mysql_query($query) or die($query);
          $korvat += mysql_affected_rows();

          $query = "delete from vertailu_hinnat where tukkuri='".mysql_field_name($res, $i)."' and tuote='$rivi[$i]'";
          $resvv = mysql_query($query) or die($query);
          $hinnat += mysql_affected_rows();
        }
      }

    } // end while rivi

  } // end while kala

  echo "<font class='message'>Poistettiin $hinnat hintaa ja $korvat korvaavaa.</font><br>";
  echo "<font class='message'>Poistetaan itse vertailurivit.</font><br>";
  flush();

  // k‰yd‰‰n tuotteet uudestaan l‰pi, ja dellaillaan itte rivit
  mysql_data_seek($resul, 0);

  $muut = 0;

  while ($kala = mysql_fetch_array($resul)) {

    // haetaan tuotteen vertailut
    $query = "select * from vertailu where arwidson = '$kala[tuoteno]'";
    $res   = mysql_query($query) or die($query);

    while ($rivi = mysql_fetch_array($res)) {

      for ($i=1; $i<mysql_num_fields($res)-5; $i++) {

        if (trim($rivi[$i]) != "") {
          $query = "delete from vertailu where ".mysql_field_name($res, $i)."='$rivi[$i]'";
          $resve = mysql_query($query) or die($query);
          $muut += mysql_affected_rows();
        }
      }

    } // end while rivi

    $query = "delete from vertailu where arwidson='$kala[tuoteno]'";
    $itres = mysql_query($query) or die($query);
    $muut += mysql_affected_rows();

  } // end while kala

  echo "<font class='message'>Poistettiin $muut vertailurivi‰.</font><br>";
  echo "<font class='message'>Optimoidaan tiedokanta.</font><br>";
  flush();

  $query = "optimize table vertailu, vertailu_hinnat, vertailu_korvaavat";
  $itres = mysql_query($query) or die($query);

  echo "<font class='message'>Valmista...</font>";

  $query = "unlock tables";
  $itres = mysql_query($query) or die($query);

}
else {

  echo "<form method='post' name='sendfile'>";

  // tehd‰‰n avainsana query
  $res = t_avainsana("TRY");

  echo "<font class='message'>Ole tarkkana, ettei lipsahda. T‰‰ dellailaa kysym‰tt‰.<br><br>";

  print "<select name='try'>";
  print "<option value=''>Valitse tuoteryhm‰</option>";

  while ($rivi=mysql_fetch_array($res)) {
    $selected='';
    if ($try==$rivi["selite"]) $selected=' SELECTED';
    echo "<option value='$rivi[selite]'$selected>$rivi[selite] - $rivi[selitetark]</option>";
  }

  print "</select>";

  echo "<input type='submit' value='Poista'></form>";
}

require "inc/footer.inc"
