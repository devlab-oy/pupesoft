<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Alkusynkronointi")."</font><hr>";

if (!isset($tee)) $tee = "";

if ($tee == "SYNK") {

	//	Onko mahdollista synkronoida?
	if(substr($table, 0, 9) == "avainsana") {
		if(strpos($yhtiorow["synkronoi"], substr($table, 0, 9)) === false) {
			echo "VIRHE: Pyydettyä taulua $table ei voida synkronoida, sitä ei ole määritelty!";
			exit;
		}

		$table = substr($table, 0, 9);

		$abulisa = ereg("(avainsana\|*([\|a-zA-Z_\-]*)),*", $yhtiorow["synkronoi"], $regs);
		$la = explode("|",$regs[2]);

		$lajit  = " and laji in (";

		foreach($la as  $l) {
			$lajit .= "'$l',";
		}
		$lajit = substr($lajit, 0, -1);
		$lajit .= ")";
	}
	else {
		if(strpos($yhtiorow["synkronoi"], $table) === false or $table == "") {
			echo "VIRHE: Pyydettyä taulua $table ei voida synkronoida, sitä ei ole määritelty!";
			exit;
		}
		$lajit = "";
	}

	require_once("inc/pakolliset_sarakkeet.inc");

	list($pakolliset, $kielletyt, $wherelliset, , , ) = pakolliset_sarakkeet($table);

	if(count($wherelliset) == 0 and count($pakolliset) == 0) {
		echo "VIRHE: Pyydettyä taulua $table ei voida synkronoida, sitä ei ole määritelty!<br>";
		exit;
	}
	else {
		//	Tehdään kysely
		$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
					FROM yhtio
					JOIN yhtion_parametrit ON (yhtion_parametrit.yhtio = yhtio.yhtio)
					WHERE konserni = '$yhtiorow[konserni]'
					AND (synkronoi = '$table' or synkronoi like '$table,%' or synkronoi like '%,$table,%' or synkronoi like '%,$table' or synkronoi like '%,$table|%' or synkronoi like '$table|%')";
		$kohderes = mysql_query($query) or pupe_error($query);
		$kohderow = mysql_fetch_array($kohderes);

		if (strlen($kohderow["yhtiot"]) == 0) {
			echo "VIRHE: Pyydettyä taulua $table ei voida synkronoida, yhtiö ei löydy!<br>$query";
			exit;
		}

		$group = " group by ";

		$indeksi = array_merge($wherelliset, $pakolliset);
		$indeksi = array_unique($indeksi);

		foreach ($indeksi as $pakollinen) {
			$group .= strtolower($pakollinen).",";
		}

		$group = substr($group, 0, -1);

		$query = "	SELECT group_concat(tunnus) tunnukset
					FROM $table
					WHERE yhtio in ($kohderow[yhtiot])
					$lajit
					$group";
		$abures = mysql_query($query) or pupe_error($query);

		while ($aburow = mysql_fetch_array($abures)) {
			$query = "	SELECT *
						FROM $table
						WHERE tunnus in ($aburow[tunnukset])
						ORDER BY if(muutospvm = '0000-00-00 00:00:00', luontiaika, muutospvm) DESC
						LIMIT 1";
			$abures1 = mysql_query($query) or pupe_error($query);

			while ($aburow1 = mysql_fetch_assoc($abures1)) {
				synkronoi($aburow1["yhtio"], $table, $aburow1["tunnus"], $aburow1, "F");
			}
		}
	}

	$tee = "";
}

if ($tee == "") {
	$synkattavat = explode(',', $yhtiorow["synkronoi"]);

	 echo "<form method='post' action='$PHP_SELF'>
			<input type='hidden' name='tee' value='SYNK'>";

	echo "<select name='table'>";

	foreach($synkattavat as $synk) {
		echo "<option value='$synk'>$synk</option>";
	}

	echo "</select><br><br>";
	echo "<input type='submit' value='".t("Synkronoi")."'></form><br><br>";
}



require ("inc/footer.inc");

?>
