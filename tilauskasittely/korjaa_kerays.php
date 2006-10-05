<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Korjaa keräys").":</font><hr>";

	if($tee=='KORJAA') {
	
		$query = "	UPDATE tilausrivi
					SET keratty='',
					kerattyaika=''
					WHERE otunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		
		$query  = "	update lasku 
					set alatila='A' 
					where yhtio='$kukarow[yhtio]' 
					and tunnus='$tunnus' 
					and tila='L' and alatila='C'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_affected_rows() != 1) {
			pupe_error("".t("Keräämättömäksi merkkaaminen ei onnistu")."! $query");
			exit;	
		}
		
		$tee = '';
	}

	// meillä ei ole valittua tilausta
	if ($tee == '') {
		$formi="find";
		$kentta="etsi";

		// tehdään etsi valinta
		echo "<form action='$PHP_SELF' name='find' method='post'>".t("Etsi tilausta").": <input type='text' name='etsi'><input type='Submit' value='".t("Etsi")."'></form>";

		$haku='';
		if (is_string($etsi))  $haku="and nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku="and tunnus='$etsi'";
		
		$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));	
		$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
		$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	
		$kkl = date("m");
		$vvl = date("Y");
		$ppl = date("d");
		
		$query = "	select distinct otunnus 
					from tilausrivi, lasku 
					where tilausrivi.yhtio='$kukarow[yhtio]' 
					and var!='J' 
					and kerattyaika>='$vva-$kka-$ppa 00:00:00'
					and kerattyaika<='$vvl-$kkl-$ppl 23:59:59'
					and lasku.yhtio='$kukarow[yhtio]' 
					and lasku.tunnus=tilausrivi.otunnus 
					and lasku.tila='L' 
					and lasku.alatila='C'";
		$tilre = mysql_query($query) or pupe_error($query);

		while ($tilrow = mysql_fetch_array($tilre))
		{
			// etsitään sopivia tilauksia
			$query = "	SELECT tunnus 'tilaus', concat_ws(' ', nimi, nimitark) asiakas, date_format(luontiaika, '%Y-%m-%d') laadittu, laatija
						FROM lasku
						WHERE tunnus='$tilrow[0]' 
						and tila='L' $haku 
						and yhtio='$kukarow[yhtio]' 
						and alatila='C' 
						ORDER by laadittu desc";
			$result = mysql_query($query) or pupe_error($query);

			//piirretään taulukko...
			if (mysql_num_rows($result)!=0)
			{
				while ($row = mysql_fetch_array($result))
				{
					if ($boob=='') // piirretään vaan kerran taulukko-otsikot
					{
						$boob='kala';
						echo "<table>";
						echo "<tr>";
						for ($i=0; $i<mysql_num_fields($result); $i++)
							echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
						echo "</tr>";
					}

					echo "<tr>";

					for ($i=0; $i<mysql_num_fields($result); $i++)
						echo "<td>$row[$i]</td>";

					echo "	<form method='post' action='$PHP_SELF'><td class='back'>
							<input type='hidden' name='tee' value='KORJAA'>	
						  	<input type='hidden' name='tunnus' value='$row[0]'>
						  	<input type='submit' name='tila' value='".t("Korjaa")."'></td></tr></form>";
				}
			}
		}

		if ($boob!='')
			echo "</table>";
		else
			echo "<font class='message'>".t("Yhtään korjattavaa tilausta ei löytynyt")."...</font>";
	}
	
	require ("../inc/footer.inc");
?>
