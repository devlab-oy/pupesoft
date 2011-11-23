<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>Hae tuotteen tulliprossa (veroperustetieto):</font><hr>";


echo "<form method='post' action='$PHP_SELF'>
	<br><br>
	<table>
	<tr><th>".t("Syötä tuotenumero").":</th>
		<td><input type='text' name='tuoteno' value = '$tuoteno'></td>
		<td class='back'><input type='submit' value='".t("Hae")."'></td>
	</tr>
	</table>
	</form><br><br><br><br>";

echo t("Näytä kaikki tuotteet").":<hr>";
echo "<form method='post' action='$PHP_SELF'>
	<table>
	<tr><th>".t("Simuloitu tuotteiden lähetysmaa").":</th>";

$query = "	SELECT distinct koodi, nimi
			FROM maat
			WHERE nimi != ''
			ORDER BY koodi";
$vresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='maa'>";
echo "<option value = ''>".t("Tuotteen toimittajan oletus")."</option>";

while ($vrow = mysql_fetch_assoc($vresult)) {
	$sel="";

	if ($maa == $vrow["koodi"]) {
		$sel = "selected";
	}
	echo "<option value = '$vrow[koodi]' $sel>".t($vrow["nimi"])."</option>";
}

echo "</select>";
echo "</td></tr><tr><th>".t("Toimittaja").":</th>";

$query = "	SELECT distinct toimi.tunnus, toimi.nimi, toimi.nimitark
			FROM tuotteen_toimittajat
			JOIN toimi ON (toimi.yhtio=tuotteen_toimittajat.yhtio and toimi.tunnus=tuotteen_toimittajat.liitostunnus and toimi.tyyppi != 'P')
			WHERE tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
			ORDER BY toimi.nimi, toimi.nimitark";
$vresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='toimittaja'>";
echo "<option value = ''>".t("Näytä kaikki")."</option>";

while ($vrow = mysql_fetch_assoc($vresult)) {
	$sel="";

	if ($toimittaja == $vrow["tunnus"]) {
		$sel = "selected";
	}
	echo "<option value = '$vrow[tunnus]' $sel>".trim("$vrow[nimi] $vrow[nimitark]")."</option>";
}

echo "</select>";
echo "</td>
	<td class='back'><input type='submit' name='KAIKKI' value='".t("Näytä")."'></td>
	</tr>
	</table>
	</form><br><br>";


if ($tuoteno != '') {

	$query = "	SELECT tuote.tuoteno, tuote.tullinimike1, tuote.tullinimike2, tuotteen_toimittajat.alkuperamaa
				FROM tuote
				JOIN tuotteen_toimittajat using (yhtio,tuoteno)
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				and tuote.tuoteno = '$tuoteno'";
	$result = mysql_query($query) or pupe_error($query);
	$tuorow = mysql_fetch_array($result);

	echo "Tuotteen tiedot: $tuoteno, Tullinimike1: $tuorow[tullinimike1], Tullinimike2: $tuorow[tullinimike2], Alkuperämaa: $tuorow[alkuperamaa], Tulliprosentti: ";

	$tulliprossa = "";
	$laskurow["maa_lahetys"] = $tuorow["alkuperamaa"];

	ob_start();

	require("taric_veroperusteet.inc");

	$kala = ob_get_contents();

	ob_end_clean();

	if ($kala != "") {
		echo $kala;
	}
	else {
		echo $tulliprossa;
	}

	echo "<br>";
}

if (isset($KAIKKI)) {

	$lisa = "";

	if ($maa != "") {
		echo "$maa";
	}

	if ($toimittaja != "") {
		$lisa = " and tuotteen_toimittajat.liitostunnus = '$toimittaja' ";
	}

	$query = "	SELECT tuote.tuoteno, tuote.tullinimike1, tuote.tullinimike2, tuotteen_toimittajat.alkuperamaa
				FROM tuote
				JOIN tuotteen_toimittajat using (yhtio,tuoteno)
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				and tuote.status not in ('P','X')
				and tuotteen_toimittajat.alkuperamaa not in ('FI', '')
				and tuote.tullinimike1 not in ('', 0)
				$lisa
				ORDER BY tuotteen_toimittajat.alkuperamaa, tuote.tuoteno";
	$result = mysql_query($query) or pupe_error($query);

	echo "<pre>";

	$lask = 1;

	while ($tuorow = mysql_fetch_assoc($result)) {

		echo sprintf('%08d', $lask)."\t";
		echo sprintf('%-60.60s', $tuorow["tuoteno"])."\t";
		echo sprintf('%8.8s',   $tuorow["tullinimike1"])."\t";
		echo sprintf('%4.4s',   $tuorow["tullinimike2"])."\t";


		$tulliprossa = "";

		if ($maa != "") {
			$laskurow["maa_lahetys"] = $maa;
		}
		else {
			$laskurow["maa_lahetys"] = $tuorow["alkuperamaa"];
		}

		echo sprintf('%2.2s',   $laskurow["maa_lahetys"])."\t";

		ob_start();

		require("taric_veroperusteet.inc");

		$kala = ob_get_contents();

		ob_end_clean();

		if ($kala != "") {
			echo str_replace("<br>", "", $kala)."\n";
		}
		else {
			echo sprintf('%02.3f',  $tulliprossa)."\n";
		}

		$lask++;
	}

	echo "</pre>";

}

require ("inc/footer.inc");

?>