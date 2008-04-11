<?php

	// jos ollaan saatu komentoriviltä parametri
	// komentoriviltä pitää tulla parametrinä yhtio
	if (trim($argv[1]) != '') {

		if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

		// otetaan tietokanta connect
		require ("inc/connect.inc");
		require ("inc/functions.inc");

		// hmm.. jännää
		$kukarow['yhtio'] = $argv[1];

		$query    = "select * from yhtio where yhtio='$kukarow[yhtio]'";
		$yhtiores = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($yhtiores) == 1) {
			$yhtiorow = mysql_fetch_array($yhtiores);
			$aja = 'run';
			
			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio='$yhtiorow[yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_array($result);

				// lisätään kaikki yhtiorow arrayseen
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}
			
		}
		else {
			die ("Yhtiö $kukarow[yhtio] ei löydy!");
		}
	}
	else {
		require ("inc/parametrit.inc");

		echo "<font class='head'>Iltasiivo</font><hr>";

		if ($aja != "run") {
			echo "<br><form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='aja' value='run'>";
			echo "<input type='submit' value='Aja iltasiivo!'>";
			echo "</form>";
		}

		echo "<pre>";
	}

	if ($aja == "run") {

		$iltasiivo = "";
		$laskuri   = 0;

		// poistetaan kaikki tuotteen_toimittajat liitokset joiden toimittaja on poistettu
		$query = "	select toimi.tunnus, tuotteen_toimittajat.tunnus toimtunnus
					from tuotteen_toimittajat
					left join toimi on toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus
					where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					having toimi.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$query = "delete from tuotteen_toimittajat where tunnus = '$row[toimtunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri ++;
		}

		if ($laskuri > 0) $iltasiivo .= "Poistettiin $laskuri poistetun toimittajan tuoteliitosta.\n";
		$laskuri = 0;

		// poistetaan kaikki tuotteen_toimittajat liitokset joiden tuote on poistettu
		$query = "	select tuote.tunnus, tuotteen_toimittajat.tunnus toimtunnus
					from tuotteen_toimittajat
					left join tuote on tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
					where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					having tuote.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$query = "delete from tuotteen_toimittajat where tunnus = '$row[toimtunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri ++;
		}

		if ($laskuri > 0) $iltasiivo .= "Poistettiin $laskuri poistetun tuotteen tuoteliitosta.\n";
		$laskuri = 0;
		$laskuri2 = 0;
		// poistetaan kaikki JT-otsikot jolla ei ole enää rivejä ja extranet tilaukset joilla ei ole rivejä ja tietenkin myös ennakkootsikot joilla ei ole rivejä.
		$query = "	select tilausrivi.tunnus, lasku.tunnus laskutunnus, lasku.tila, lasku.tunnusnippu
					from lasku
					left join tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					where lasku.yhtio = '$kukarow[yhtio]' and
					lasku.tila in ('N','E')
					having tilausrivi.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa iltasiivo.php (1)")."<br>";
			
			//	Jos kyseessä on tunnusnippupaketti, halutaan säilyttää linkki tästä tehtyihin tilauksiin, tilaus merkataan vain toimitetuksi
			if($row["tunnusnippu"] > 0) {
				$query = "update lasku set tila = 'L', alatila='X' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
				$deler = mysql_query($query) or die($query);
				$laskuri2 ++;
			}
			else {
				$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa iltasiivo.php (1)")."<br>";

				$query = "update lasku set alatila='$row[tila]', tila='D',  comments = '$komm' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
				$deler = mysql_query($query) or die($query);
				$laskuri ++;
			}
			
			//poistetaan TIETENKIN kukarow[kesken] ettei voi syöttää extranetissä rivejä tälle
			$query = "update kuka set kesken = '' where yhtio = '$kukarow[yhtio]' and kesken = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);
			
		}

		if ($laskuri > 0) $iltasiivo .= "Poistettiin $laskuri rivitöntä tilausta.\n";
		if ($laskuri2 > 0) $iltasiivo .= "Merkattiin toimitetuksi $laskuri2 rivitöntä tilausta.\n";
		
		// tässä tehdään isittömistä perheistä ei-perheitä ja myös perheistä joissa ei ole lapsia eli nollataan perheid
		$lask = 0;
		$lask2 = 0;

		$query =	"SELECT perheid, count(*) koko
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid != '0'
					GROUP BY perheid";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($result)) {
			$query =	"SELECT perheid
						FROM tilausrivi 
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and tunnus = '$row[perheid]'";
			$result2 = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($result2) == 0) {
				$lask++;
				$query = "UPDATE tilausrivi SET perheid = 0 WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid = '$row[perheid]' order by tunnus";
				$upresult = mysql_query($query) or pupe_error($query);
			}
			else {
				if ($row['koko'] == 1) {
					$lask2++;
					$query = "UPDATE tilausrivi SET perheid = 0 WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid = '$row[perheid]' order by tunnus";
					$upresult = mysql_query($query) or pupe_error($query);
				}
			}
		}
		
		if ($lask+$lask2 > 0) {
			$iltasiivo .= "Tuhotiin $lask orpoa perhettä, ja $lask2 lapsetonta isää (eli perheid nollattiin)\n";
		}
		
		$lasktuote = 0;
		$laskpois = 0;
		$poistetaankpl = 0;

		$query = 	"SELECT tuoteno, liitostunnus, count(tunnus) countti
					FROM tuotteen_toimittajat
					WHERE yhtio = '$kukarow[yhtio]'
					GROUP BY 1,2
					HAVING countti > 1";
		$result = mysql_query($query) or pupe_error($query);
		while ($row = mysql_fetch_array($result)) {
			$lasktuote++;
			$poistetaankpl = $row['countti']-1;

			$poisquery =	"DELETE FROM tuotteen_toimittajat 
							WHERE yhtio = '$kukarow[yhtio]' 
							AND tuoteno = '$row[tuoteno]' 
							AND liitostunnus = '$row[liitostunnus]'
							ORDER BY tunnus DESC 
							LIMIT $poistetaankpl";
			$poisresult = mysql_query($poisquery) or pupe_error($poisquery);
			$laskpois += mysql_affected_rows();
		}

		if ($lasktuote > 0) {
			$iltasiivo .= "Poistettiin $lasktuote tuotteelta yhteensä $laskpois duplikaatti tuotteen_toimittajaa\n";
		}
		
		// Korjataan rikkinäiset käyttöoikeudet (jos samassa sovelluksessa on duplikaatti linkkejä)
		$query = "	SELECT kuka, sovellus, nimi, alanimi, group_concat(tunnus) tunnukset,  count(*) kpl 
					FROM oikeu 
					WHERE yhtio = '$kukarow[yhtio]'
					and kuka   != ''
					group by 1,2,3,4
					having kpl > 1";
		$result = mysql_query($query) or pupe_error($query);
		
		while ($row = mysql_fetch_array($result)) {
			$query = "DELETE FROM oikeu WHERE yhtio = '$kukarow[yhtio]' and tunnus in ($row[tunnukset]) LIMIT ".($row["kpl"]-1);
			$delresult = mysql_query($query) or pupe_error($query);
			
			$iltasiivo .= "Poistettiin käyttäjältä $row[kuka] duplikaatti käyttöoikeus: $row[sovellus] $row[nimi] $row[alanimi]\n";
		}
		
		if ($iltasiivo != "") {
			
			echo "Iltasiivo ".date("d.m.Y")." - $yhtiorow[nimi]\n\n";
			echo $iltasiivo;
			echo "\n";
			
			if($iltasiivo_email == 1) {
				$header 	= "From: <$yhtiorow[postittaja_email]>\n";
				$header 	.= "MIME-Version: 1.0\n" ;
				$subject 	= "Iltasiivo ".date("d.m.Y")." - $yhtiorow[nimi]";
				
				mail($yhtiorow["admin_email"], "Iltasiivo yhtiolle '{$yhtiorow["yhtio"]}'", $iltasiivo, $header, " -f $yhtiorow[postittaja_email]");
			}
		}
	}

	if (trim($argv[1]) == '') {
		echo "</pre>";
		require('inc/footer.inc');
	}

?>
