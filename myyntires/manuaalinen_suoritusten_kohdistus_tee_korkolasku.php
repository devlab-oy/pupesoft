<?php
// estetään sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}

$query_korko = "select viikorkopros * $suoritussumma * (if(to_days('$maksupvm')-to_days(erpcm) > 0, to_days('$maksupvm')-to_days(erpcm), 0))/36500 korkosumma from lasku WHERE tunnus='$ltunnus'";
//echo "<font class='head'>$query_korko</font>";

$result_korko = mysql_query($query_korko) or die ("Kysely ei onnistu $query_korko <br>" . mysql_error());
$korko_row = mysql_fetch_array($result_korko);
$korkosumma = $korko_row['korkosumma'];
?>
