<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

//Tehdään tällanen replace jotta parametric.inc ei poista merkkejä
$sqlapu = $_POST["sqlhaku"];

require('../inc/parametrit.inc');
//Ja tässä laitetaan ne takas
$sqlhaku = $sqlapu;

echo "<font class='head'>".t("SQL-raportti").":</font><hr>";

// käsitellään syötetty arvo nätiksi...
$sqlhaku = stripslashes(strtolower(trim($sqlhaku)));

// laitetaan aina kuudes merkki spaceks.. safetymeasure ni ei voi olla ku select
if ($sqlhaku{6} != " ") {
	$sqlhaku = substr($sqlhaku,0,6)." ".substr($sqlhaku,6); 
}

echo "<form name='sql' action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<table>";
echo "<tr><th>".t("Syötä SQL kysely")."</th></tr>";
echo "<tr><td><textarea cols='70' rows='10' name='sqlhaku'>$sqlhaku</textarea></td></tr>";
echo "<tr><td class='back'><input type='submit' value='".t("Suorita")."'></td></tr>";
echo "</table>";
echo "</form>";

// eka sana pitää olla select... safe enough kai.
if (substr($sqlhaku, 0, strpos($sqlhaku, " ")) != 'select') {
	echo "<font class='error'>".t("Ainoastaan SELECT lauseet sallittu")."!</font><br>";
	$sqlhaku = "";
}

if ($sqlhaku != '') {

	$result = mysql_query($sqlhaku) or die ("<font class='error'>".mysql_error()."</font>");

	echo "<font class='message'>".t("Haun tulos")." ".mysql_num_rows($result)." ".t("riviä").".</font><br>";
	echo "<pre>";

	for ($i=0; $i<mysql_num_fields($result); $i++) {
		echo mysql_field_name($result,$i)."\t";
	}
	echo "\n";

	while ($row = mysql_fetch_array($result)) {

		for ($i=0; $i<mysql_num_fields($result); $i++) {

			// desimaaliluvuissa muutetaan pisteet pilkuiks...
			if (mysql_field_type($result, $i) == 'real') {
				echo str_replace(".",",", $row[$i])."\t";
			}
			else {
				echo "$row[$i]\t";
			}
		}
		echo "\n";
	}
	echo "</pre>";
}

// kursorinohjausta
$formi  = "sql";
$kentta = "sqlhaku";

require("../inc/footer.inc");

?>
