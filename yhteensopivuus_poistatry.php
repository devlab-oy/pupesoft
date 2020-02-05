<?php

require "inc/parametrit.inc";

if ($toim == "MP") {
  $tyyppi = "MP";
  $ttaulu = "yhteensopivuus_mp";
  $ttaulu_lisa = " and tyyppi='$tyyppi' ";
}
elseif ($toim == "MO") {
  $tyyppi = "MO";
  $ttaulu = "yhteensopivuus_mp";
  $ttaulu_lisa = " and tyyppi='$tyyppi' ";
}
elseif ($toim == "MK") {
  $tyyppi = "MK";
  $ttaulu = "yhteensopivuus_mp";
  $ttaulu_lisa = " and tyyppi='$tyyppi' ";
}
elseif ($toim == "MX") {
  $tyyppi = "MX";
  $ttaulu = "yhteensopivuus_mp";
  $ttaulu_lisa = " and tyyppi='$tyyppi' ";
}
elseif ($toim == "AT") {
  $tyyppi = "AT";
  $ttaulu = "yhteensopivuus_mp";
  $ttaulu_lisa = " and tyyppi='$tyyppi' ";
}
else {
  $tyyppi = "HA";
  $ttaulu = "yhteensopivuus_auto";
  $ttaulu_lisa = " ";
}

echo "<font class='head'>Poista selaimesta koko tuoteryhm‰</font><hr>";

if ($try != "") {

  // haetaan kaikki tuotteet tuoteryhm‰st‰
  $query = "select tuoteno from tuote where yhtio='$kukarow[yhtio]' and try='$try' order by tuoteno";
  $resul = mysql_query($query) or pupe_error($query);

  echo "<font class='message'>Tuoteryhm‰ss‰ on ".mysql_num_rows($resul)." tuotetta.</font><br>";
  flush();

  $query = "lock table yhteensopivuus_tuote write";
  $itres = mysql_query($query) or pupe_error($query);

  $tuotteet = 0;

  // k‰yd‰‰n l‰pi tuotteet
  while ($kala = mysql_fetch_array($resul)) {

    // poistetaan tuote
    $query = "delete from yhteensopivuus_tuote where yhtio='$kukarow[yhtio]' and tuoteno='$kala[tuoteno]' and tyyppi='$tyyppi'";
    $res   = mysql_query($query) or pupe_error($query);

    if (mysql_affected_rows() <> 0) {
      $tuotteet++;
    }

  }

  $query = "unlock tables";
  $itres = mysql_query($query) or pupe_error($query);

  echo "<font class='message'>Poistettiin $tuotteet tuotetta.</font><br>";

}
else {

  echo "<form method='post'>";
  echo "<input type='hidden' name='toim' value='$toim'>";

  // tehd‰‰n avainsana query
  $res = t_avainsana("TRY");

  echo "<font class='message'>Ole tarkkana, ettei lipsahda. Tuotteet poistetaan kysym‰tt‰.<br><br>";

  print "<select name='try'>";
  print "<option value=''>Valitse tuoteryhm‰</option>";

  while ($rivi = mysql_fetch_array($res)) {
    echo "<option value='$rivi[selite]' $selected>$rivi[selite] - $rivi[selitetark]</option>";
  }

  print "</select>";

  echo "<input type='submit' value='Poista'></form>";
}

require "inc/footer.inc"
