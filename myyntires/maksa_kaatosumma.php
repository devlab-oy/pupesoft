<?php

// T‰m‰ on kyselytila!

include("inc/parametrit.inc");
require_once("inc/tilinumero.inc");

if($skip!=1) {
  echo "<br><font class='head'>Ylim‰‰r‰isen suorituksen palautus</font><hr>";
}
$query = "SELECT ytunnus,
		 left(concat_ws(' ', nimi, nimitark),30) asiakas1,
		 left(concat_ws(' ', osoite),20) asiakas2,
		 left(concat_ws(' ', postino, postitp),20) asiakas3
            FROM asiakas WHERE yhtio='$kukarow[yhtio]' AND tunnus='$tunnus'";
$result = mysql_query($query) or pupe_error($query);

if ($kalarow=mysql_fetch_object ($result)) {
  $ytunnus = $kalarow->ytunnus;
} else {

  echo "Kysely ei onnistu $query<br>";
  exit;
}

$tival = "<select name='maksajanpankkitilitunnus'>\n";
$tival .= "        <option value=''>Valitse</option>\n";
$query = "SELECT tunnus, nimi, tilino FROM yriti";
$query .= " WHERE yhtio = '$kukarow[yhtio]'";

$result = mysql_query($query) or pupe_error($query);
while ($row = mysql_fetch_array($result)) {
  $tival .= "        <option ";
  if($row[0]==$maksajanpankkitilitunnus)
    $tival .= "selected ";
  $tival .= "value='$row[0]'>$row[1] ";
  $tival .= tilinumero_print($row[2]) ."</option>\n";
}
$tival .= "      </select>\n";

echo "<form name=\"$formi\" action=\"maksa_kaatosumma2.php\" method=\"POST\">";
echo "<input type = hidden name=\"tila\" value=\"maksa\">";
echo "<table><tr><td class='back'>";
echo "<tr><td>Asiakas</td><td><input type=\"text\"  name=\"asiakas1\" value=\"$kalarow->asiakas1\"></td></tr>";
echo "<tr><td>&nbsp;</td><td><input type=\"text\"  name=\"asiakas2\" value=\"$kalarow->asiakas2\"></td></tr>";
echo "<tr><td>&nbsp;</td><td><input type=\"text\"  name=\"asiakas3\" value=\"$kalarow->asiakas3\"></td></tr>";
echo "<tr><td>Viesti</td><td><input type=\"text\" size=70 name=\"viesti\" value=\"Ylim‰‰r‰isen suorituksen palautus\"></td></tr>";
echo "<tr><td>ytunnus</td><td>$ytunnus</td></tr>";
//kaatotilin saldo
$query = "
SELECT SUM(summa) summa
FROM suoritus
WHERE yhtio='$kukarow[yhtio]' AND ltunnus<>0 AND asiakas_tunnus=$tunnus";

//echo "<font class='head'>kaato: $query</font>";

$result = mysql_query($query) or pupe_error($query);

$kaato = mysql_fetch_object($result);
$kaatosumma=$kaato->summa;

echo "<tr><td>Kaatotilin summa</td><td>$kaatosumma e</td></tr>";
echo "<input type = hidden name=\"asiakastunnus\" value=\"$tunnus\">";


echo "<tr><td>Saajan tilinumero</td><td><input maxlength=15 type=\"text\" name=\"saajantilino\" value=\"$saajantilino\"></td></tr>";
echo "<tr><td>Maksajan tilinumero</td><td>$tival</td></tr>";
echo "<tr><td colspan=2><input type='submit' value='Maksa'></submit></td></tr>";

echo "</table></form>";

include "inc/footer.inc";

?>