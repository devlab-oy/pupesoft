<?php
	require ("inc/functions.inc");
	require ("inc/parametrit.inc");
	
	echo "<font class='head'>Tiliotteiden siirto levylle</font><hr><br>";

	if ($tee == 'all') { // haetaan kaikkien toimittajien kaikki laskujen kuvat
		$lisa='';
		if ($kausi != '') $lisa = "and DATE_FORMAT(alku, '%Y-%m') = '$kausi'";
		
		$query = "SELECT distinct aineisto
					  FROM tiliotedata
					  WHERE yhtio='$kukarow[yhtio]' and tyyppi = '1' $lisa";
		$result = mysql_query ($query)
				or die ("Kysely ei onnistu $query<br>".mysql_error());
		echo "Siirrettäviä tiliotteita on ". mysql_num_rows($result) . " kpl<br>";

		$query = "SELECT *
					  FROM tiliotedata
					  WHERE yhtio='$kukarow[yhtio]' and tyyppi = '1' $lisa
					  ORDER BY aineisto, tunnus";
		$result = mysql_query ($query)
				or die ("Kysely ei onnistu $query<br>".mysql_error());
		
		$aineisto='';
		while ($tilioterow=mysql_fetch_array($result)) {
			if ($aineisto!=$tilioterow['aineisto']) {
				$sisalto = $otsikko . $sisalto;
				if ($sisalto != '') {
					$sisalto .= "</table>";
					$tyofile="/tmp/" . $tiliotenro ."-". $alkupvm ."-". $loppupvm ."-". $aineisto . ".html";
					file_put_contents($tyofile, $sisalto);
					$sisalto='';
				}
				$aineisto=$tilioterow['aineisto'];
			}
			$tietue = $tilioterow['tieto'];
			require "inc/tiliote-plain.inc";
		}
		$sisalto = $otsikko . $sisalto . "</table>";
		if ($sisalto != '') {
			$tyofile="/tmp/" . $tiliotenro ."-". $alkupvm ."-". $loppupvm ."-". $aineisto . ".html";
			file_put_contents($tyofile, $sisalto);
		}
		echo "Done!";
		require "inc/footer.inc";
	}

	echo "<br><br><form action = '$PHP_SELF' method='post'>
			Siirrä tiliotteet kaudelta
			<input type='hidden' name='tunnus' value='$trow[ytunnus]'>
			<input type='hidden' name='tee' value='all'>
			<input type='text' name='kausi' value=''> esim 2006-08
			<input type='Submit' value='Siirrä'>
			</form>";
?>
