<?php
	require ("inc/parametrit.inc");
	
	echo "<font class='head'>".t("Uuden yrityksen ohjattu perustaminen").":</font><hr>";
	
	$error = 0;
	
	if ($tila == 'ulkonako') {	
	// Tee yritys täällä
		if ($yhtio == '') {
			echo "<font class='error'>Yritykselle on annettava tunnus</font><br>";
			$error = 1;
		}
		
		if ($nimi == '') {
			echo "<font class='error'>Yritykselle on annettava nimi</font><br>";
			$error = 1;
		}
		
		$query = "SELECT nimi from yhtio where yhtio='$yhtio'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) > 0) {
			$uusiyhtiorow=mysql_fetch_array($result);
			echo "<font class='error'>Tunnus $yhtio on jo käytössä ($uusiyhtiorow[nimi])</font><br>";
			$error = 1;
		}
		
		if ($error == 0) {	
			$query = "INSERT into yhtio SET yhtio='$yhtio', nimi='$nimi'";
			$result = mysql_query($query) or pupe_error($query); 
		}
		else  {
			unset($tila);
		}
	}
	
	if ($tila == 'perusta') {
		if ($fromyhtio == '') {
			echo "<font class='error'>Valitse jokin yritys</font><br>";
			$error = 1;
		}
		
		$query = "SELECT css from yhtion_parametrit where yhtio='$fromyhtio'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>Kopioitava yritys ei löydy</font><br>";
			$error = 1;
		}
		
		if ($error == 0) {
			$uusiyhtiorow=mysql_fetch_array($result);
			$query = "INSERT into yhtion_parametrit SET css='$uusiyhtiorow[css]', yhtio='$yhtio'";
			$result = mysql_query($query) or pupe_error($query); 
		}
		else  {
			$tila = 'perusta';
		}
	}

	if ($tila == 'menut') {
		if ($fromyhtio == '') {
			echo "<font class='error'>Valitse jokin yritys</font><br>";
			$error = 1;
		}
		
		if ($error == 0) {
			$query = "INSERT into oikeu (sovellus,nimi,alanimi,paivitys,lukittu,nimitys,jarjestys,jarjestys2,yhtio) 
			SELECT sovellus,nimi,alanimi,paivitys,lukittu,nimitys,jarjestys,jarjestys2,'$yhtio' FROM oikeu WHERE yhtio='$fromyhtio' and profiili='' and kuka=''";
			$result = mysql_query($query) or pupe_error($query); 
		}
		else  {
			$tila = 'perusta';
		}
	}
	
	if ($tila == 'profiilit') {
	// Tee profiilit täällä
		foreach($profiilit as $prof) {
			$query = "	SELECT *
						FROM oikeu
						WHERE yhtio='$fromyhtio' and kuka='$prof' and profiili='$prof'";
			$pres = mysql_query($query) or pupe_error($query);	
								
			while ($trow = mysql_fetch_array($pres)) {
				$query = "	INSERT into oikeu 
								SET
								kuka		= '$trow[kuka]', 
								sovellus	= '$trow[sovellus]', 
								nimi		= '$trow[nimi]', 
								alanimi 	= '$trow[alanimi]', 
								paivitys	= '$trow[paivitys]',
								nimitys		= '$trow[nimitys]', 
								jarjestys 	= '$trow[jarjestys]',
								jarjestys2	= '$trow[jarjestys2]',
								profiili	= '$trow[profiili]',
								yhtio		= '$yhtio'";			
				$rresult = mysql_query($query) or pupe_error($query);
			}												
		}
	}
	
	if ($tila == 'kayttaja') {
		if (count($profiilit) == 0) {
			echo "<font class='error'>Ainakin yksi profiili on valittava</font><br>";
			$error = 1;
		}
		if ($error == 0) {
			//Tehdään käyttäjä
			$query = "INSERT into kuka SET
				yhtio = '$yhtio',
				salasana = '" . md5(trim($salasana)) . "',
				kuka  = '$kuka'
			";
			$result = mysql_query($query) or pupe_error($query);

			//Oikeudet
			foreach($profiilit as $prof) {

				$query = "	SELECT *
							FROM oikeu
							WHERE yhtio='$yhtio' and kuka='$prof' and profiili='$prof'";
				$pres = mysql_query($query) or pupe_error($query);

				while ($trow = mysql_fetch_array($pres)) {
					//joudumme tarkistamaan ettei tätä oikeutta ole jo tällä käyttäjällä.
					//voi olla jossain toisessa profiilissa
					$query = "	SELECT yhtio
								FROM oikeu
								WHERE kuka		= '$kuka'
								and sovellus	= '$trow[sovellus]'
								and nimi		= '$trow[nimi]'
								and alanimi 	= '$trow[alanimi]'
								and paivitys	= '$trow[paivitys]'
								and nimitys		= '$trow[nimitys]'
								and jarjestys 	= '$trow[jarjestys]'
								and jarjestys2	= '$trow[jarjestys2]'
								and yhtio		= '$yhtio'";
					$tarkesult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($tarkesult) == 0) {
						$query = "	INSERT into oikeu
									SET
									kuka		= '$kuka',
									sovellus	= '$trow[sovellus]',
									nimi		= '$trow[nimi]',
									alanimi 	= '$trow[alanimi]',
									paivitys	= '$trow[paivitys]',
									nimitys		= '$trow[nimitys]',
									jarjestys 	= '$trow[jarjestys]',
									jarjestys2	= '$trow[jarjestys2]',
									yhtio		= '$yhtio'";
						$rresult = mysql_query($query) or pupe_error($query);

					}
				}
			}
			unset($tila);
			unset($yhtio);
			unset($nimi);
		}
		else {
			$tila = 'profiilit';
		}
	}


//// Käyttöliittymä

	if (isset($tila)) {
		$query = "SELECT nimi from yhtio where yhtio='$yhtio'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>Perustettava yritys on kadonnut!</font><br>";
			exit;
		}
		$uusiyhtiorow=mysql_fetch_array($result);
		
		echo "<table>
		<tr><td>$yhtio</td><td>$uusiyhtiorow[nimi]</td></tr>
		</table><br><br>";
	}
		
	if ($tila == 'ulkonako') {
		// yritysvalinta
		$query = "SELECT yhtio, nimi FROM yhtio WHERE yhtio != '$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='perusta'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan ulkonäkö?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Valitse yhtiö")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Valitse')."'></td></tr></table></form>";
	}
	
	if ($tila == 'perusta') {
		// yritysvalinta
		$query = "SELECT yhtio, nimi FROM yhtio WHERE yhtio != '$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='menut'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan menut?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Valitse yhtiö")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";
	}
	
	if ($tila == 'menut') {
		// profiilit
		$query = "SELECT distinct profiili FROM oikeu WHERE yhtio = '$fromyhtio' and profiili != ''";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='profiilit'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<input type='hidden' name = 'fromyhtio' value='$fromyhtio'>
		<table>
		<tr><th>".t("Mitkä profiilit kopioidaan?").":</th><td></td></tr>";

		while ($profiilirow=mysql_fetch_array($result)) {
			echo "<tr><td>$profiilirow[profiili]</td><td><input type='checkbox' name = 'profiilit[]' value='$profiilirow[profiili]' checked></td></tr>";
		}

		echo "<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";
	}
	
	if ($tila == 'profiilit') {
		// käyttäjät
		$query = "SELECT distinct profiili FROM oikeu WHERE yhtio = '$yhtio' and profiili != ''";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='kayttaja'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Anna käyttäjätunnus").":</th><td><input type='text' name = 'kuka' value='$kuka'></td></tr>
		<tr><th>".t("Salasana")."</th><td><input type='text' name = 'salasana' value='$salasana'></td></tr>
		<tr><th>".t("Profiilit")."</th><td></td></tr>";

		while ($profiilirow=mysql_fetch_array($result)) {
			echo "<th>$profiilirow[profiili]</th><td><input type='checkbox' name = 'profiilit[]' value='$profiilirow[profiili]'></td></tr>";
		}

		echo "<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";
	}
		
	
	if (!isset($tila)) {
		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='ulkonako'><table>
		<tr><th>Anna uuden yrityksen tunnus</th><td><input type='text' name = 'yhtio' value='$yhtio' size='10' maxlength='5'></td></tr>
		<tr><th>Anna uuden yrityksen nimi</th><td><input type='text' name = 'nimi' value='$nimi'></td></tr>
		<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr>
		</table></form>";
	}
	require("inc/footer.inc");
?>		
