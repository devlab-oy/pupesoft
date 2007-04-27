<?php
	require ("inc/parametrit.inc");
	
	echo "<font class='head'>".t("Uuden yrityksen ohjattu perustaminen").":</font><hr>";
	
	$error = 0;
	
	if ($tila == 'ulkonako') {	
	// Tee yritys täällä
		if ($yhtio == '') {
			echo "<font class='error'>".t("Yritykselle on annettava tunnus")."</font><br>";
			$error = 1;
		}
		
		if ($nimi == '') {
			echo "<font class='error'>".t("Yritykselle on annettava nimi")."</font><br>";
			$error = 1;
		}
		
		$query = "SELECT nimi from yhtio where yhtio='$yhtio'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) > 0) {
			$uusiyhtiorow=mysql_fetch_array($result);
			echo "<font class='error'>".t("Tunnus $yhtio on jo käytössä (".$uusiyhtiorow['nimi'].")")."</font><br>";
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
		if ($fromyhtio != '') {
		
			$query = "SELECT css from yhtion_parametrit where yhtio='$fromyhtio'";
			$result = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Kopioitava yritys ei löydy")."</font><br>";
				$error = 1;
			}
			
			if ($error == 0) {
				$uusiyhtiorow=mysql_fetch_array($result);
				$query = "INSERT into yhtion_parametrit SET css='$uusiyhtiorow[css]', yhtio='$yhtio'";
				$result = mysql_query($query) or pupe_error($query); 
			}
		}
		else {
				$query = "INSERT into yhtion_parametrit SET yhtio='$yhtio'";
				$result = mysql_query($query) or pupe_error($query);
		} 
			
	}

	if ($tila == 'menut') {
		if ($fromyhtio != '') {
			$query = "INSERT into oikeu (sovellus,nimi,alanimi,paivitys,lukittu,nimitys,jarjestys,jarjestys2,yhtio) 
			SELECT sovellus,nimi,alanimi,paivitys,lukittu,nimitys,jarjestys,jarjestys2,'$yhtio' FROM oikeu WHERE yhtio='$fromyhtio' and profiili='' and kuka=''";
			$result = mysql_query($query) or pupe_error($query); 
		}
	}
	
	if ($tila == 'profiilit') {
		if (is_array($profiilit)) {
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
	}
	
	if ($tila == 'kayttaja') {
		$query = "	SELECT kuka FROM oikeu WHERE yhtio='$yhtio'";
		$pres = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($pres) > 0) {
			if (count($profiilit) == 0) {
				echo "<font class='error'>Ainakin yksi profiili on valittava</font><br>";
				$error = 1;
			}	
		}
		
		if ($error == 0) {
			//Tehdään käyttäjä
			$profile = '';
			if (is_array($profiilit)) {
				if (count($profiilit) > 0) {
					foreach($profiilit as $prof) {
						$profile .= $prof.",";
					}
					$profile = substr($profile,0,-1);
				}
			}
			
			$query = "INSERT into kuka SET
				yhtio = '$yhtio',
				nimi = '$nimi',
				salasana = '" . md5(trim($salasana)) . "',
				kuka  = '$kuka',
				profiilit = '$profile'
			";
			$result = mysql_query($query) or pupe_error($query);

			//Oikeudet
			if (is_array($profiilit)) {
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
			}
		}
		else {
			$tila = 'profiilit';
		}
	}
	
	if ($tila == 'tili') {
		if ($fromyhtio != '') {
			$query = "SELECT * FROM tili where yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar))
			{
				$query = "insert into tili (nimi, sisainen_taso, tilino, ulkoinen_taso, yhtio, oletusalv) values ('$row[nimi]','$row[sisainen_taso]','$row[tilino]','$row[ulkoinen_taso]','$yhtio', '$row[oletusalv]')";
				$upres = mysql_query($query) or pupe_error($query);
			}
			
			$query = "SELECT * FROM taso where yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar))
			{
				$query = "insert into taso (tyyppi, laji, taso, nimi, yhtio) values ('$row[tyyppi]','$row[laji]','$row[taso]','$row[nimi]','$yhtio')";
				$upres = mysql_query($query) or pupe_error($query);
			}
			unset($tila);
			unset($yhtio);
			unset($nimi);
		}
	}
	
	if ($tila == 'avainsana') {
		if (is_array($avainsanat)){
			foreach($avainsanat as $avain) {
				$query = "	SELECT *
							FROM avainsana
							WHERE yhtio='$fromyhtio' and laji='$avain'";
				$pres = mysql_query($query) or pupe_error($query);	
									
				while ($trow = mysql_fetch_array($pres)) {
					$query = "	INSERT into avainsana 
									SET
									jarjestys		= '$trow[jarjestys]', 
									laatija			= '$kukarow[laatija]', 
									laji			= '$trow[laji]', 
									luontiaika 		=  now(), 
									selite			= '$trow[selite]',
									selitetark		= '$trow[selitetark]', 
									selitetark_2	= '$trow[selitetark_2]',
									selitetark_3	= '$trow[selitetark_3]',
									yhtio			= '$yhtio'";
					$rresult = mysql_query($query) or pupe_error($query);
				}
			}
		}
		unset($tila);
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
		<option value=''>".t("Ei kopioida")."</option>";

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
		<option value=''>".t("Ei kopioida")."</option>";

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
		<tr><th>".t("Nimi").":</th><td><input type='text' name = 'nimi' value='$nimi'></td></tr>
		<tr><th>".t("Salasana")."</th><td><input type='text' name = 'salasana' value='$salasana'></td></tr>
		<tr><th>".t("Profiilit")."</th><td></td></tr>";

		while ($profiilirow=mysql_fetch_array($result)) {
			echo "<th>$profiilirow[profiili]</th><td><input type='checkbox' name = 'profiilit[]' value='$profiilirow[profiili]'></td></tr>";
		}

		echo "<tr><th></th><td><input type='submit' value='".t('Perusta')."'></td></tr></table></form>";
	}

	if ($tila == 'kayttaja') {
		// tilit ja tasot
		$query = "SELECT distinct tili.yhtio, yhtio.nimi FROM tili, yhtio WHERE tili.yhtio=yhtio.yhtio";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='tili'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan tilikartta?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Ei kopioida")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi]</option>";
		}

		echo "</select></td></tr>";
		echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
	}
	
	if ($tila == 'tili') {
		// avainsanat
		$query = "SELECT yhtio, nimi FROM yhtio WHERE yhtio != '$yhtio'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF' method='post'><input type='hidden' name = 'tila' value='avainsana'>
		<input type='hidden' name = 'yhtio' value='$yhtio'>
		<table>
		<tr><th>".t("Miltä yritykseltä kopioidaan avainsanat?").":</th><td><select name='fromyhtio'>
		<option value=''>".t("Ei kopioida")."</option>";

		while ($uusiyhtiorow=mysql_fetch_array($result)) {
			echo "<option value='$uusiyhtiorow[yhtio]'>$uusiyhtiorow[nimi] ($uusiyhtiorow[yhtio])</option>";
		}

		echo "</select></td></tr>";
		echo "<tr>".t("Mitkä avainsanatyypit kopioidaan")."</td><td></td></tr>";
		echo "<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='T'>".t("Toimitustapa")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='Y'>".t("Yksikko")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='C'>".t("CHN tietue")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASLUOKKA'>".t("Asiakasluokka")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASOSASTO'>".t("Asiakasosasto")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASIAKASRYHMA'>".t("Asiakasryhma")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='S'>".t("Tuotteen status")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='KM'>".t("Kuljetusmuoto")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='KT'>".t("Kauppatapahtuman luonne")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='pakkaus'>".t("Pakkaus")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='TV'>".t("Tilausvahvistus")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='TRY'>".t("Tuoteryhmä")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='OSASTO'>".t("Tuoteosasto")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='KALETAPA'>".t("CRM yhteydenottotapa")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='TUOTEULK'>".t("Tuotteiden avainsanojen laji")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='ALV'>".t("ALV%")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='LAHETETYYPPI'>".t("Lähetetyyppi")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='TULLI'>".t("Poistumistoimipaikka")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='KASSA'>".t("Kassa")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='HENKILO_OSASTO'>".t("Henkilöosasto")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='TOIMEHTO'>".t("Toimitusehto")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='PIIRI'>".t("Asiakkaan piiri")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='TOIMEHTO'>".t("Toimitusehto")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='RAHTIKIRJA'>".t("Rahtikirjatyyppi")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='MYSQLALIAS'>".t("Tietokantasarakkeen nimialias")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='LASKUKUVAUS'>".t("Maksuposition kuvaus")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='KERAYSLISTA'>".t("Keräyslista")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='PAKKAUSKUVAUS'>".t("Pakkauskuvauksen tarkenne")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='ALVULK'>".t("Ulkomaan ALV%")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='KUKAASEMA'>".t("Käytäjän asema")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='SEURANTA'>".t("Tilauksen seurantaluokka")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='TRIVITYYPPI'>".t("Tilausrivin tyyppi")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='OSASTO_SE'>".t("Ruotsinkielinen tuoteosasto")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='TRY_SE'>".t("Ruotsinkielinen tuoteryhmä")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='TOIMITUSTAPA_SE'>".t("Ruotsinkielinen toimitustapa")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='MEHTOTXT_SE'>".t("Ruotsinkielinen maksuehdon teksti")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='MEHTOKATXT_SE'>".t("Ruotsinkielinen maksuehdon kassateksti")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASOSASTO_SE'>".t("Ruotsinkielinen asiakasosasto")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASRYHMA_SE'>".t("Ruotsinkielinen asiakasryhma")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='LAHETETYYPPI_SE'>".t("Ruotsinkielinen lähetetyyppi")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='TV_SE'>".t("Ruotsinkielinen tilausvahvistus")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='ASLUOKKA_SE'>".t("Ruotsinkielinen asiakasluokka")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='KM_SE'>".t("Ruotsinkielinen kuljetusmuoto")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='KT_SE'>".t("Ruotsinkielinen kauppatapahtuman luonne")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='RAHTIKIRJA_SE'>".t("Ruotsinkielinen rahtikirjatyyppi")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='Y_SE'>".t("Ruotsinkielinen yksikkö")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='S_SE'>".t("Ruotsinkielinen tuotteen status")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='PAKKAUS_SE'>".t("Ruotsinkielinen pakkaus")."</td></tr>
				<tr><td><INPUT type='checkbox'  name='avainsanat[]'  value='SARJANUMERON_LI'>".t("Sarjanumeron lisätieto")."</td></tr>";

		echo "<tr><th></th><td><input type='submit' value='".t('Kopioi')."'></td></tr></table></form>";
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
