<?php
	require ("../inc/parametrit.inc");

	$yhtio = '';
	$yhtiolisa = '';

	if ($yhtiorow['konsernivarasto'] != '' and $konserni_yhtiot != '') {
		$yhtio = $konserni_yhtiot;
		$yhtiolisa = "yhtio in ($yhtio)";

		if ($lasku_yhtio != '') {
			$kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);

			$yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
		}
	}
	else {
		$yhtiolisa = "yhtio = '$kukarow[yhtio]'";
	}


	echo "<font class='head'>".t("Korjaa keräys").":</font><hr>";

	if($tee=='KORJAA') {
	
		$query = "	UPDATE tilausrivi
					SET keratty='',
					kerattyaika=''
					WHERE otunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		
		$query  = "	UPDATE lasku 
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

		if ($yhtio != '' and $konserni_yhtiot != '') {
			$yhtio = $konserni_yhtiot;
		}
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
		
		$query = "	SELECT distinct otunnus 
					from tilausrivi, lasku 
					where tilausrivi.$yhtiolisa
					and var!='J' 
					and kerattyaika>='$vva-$kka-$ppa 00:00:00'
					and kerattyaika<='$vvl-$kkl-$ppl 23:59:59'
					and lasku.$yhtiolisa
					and lasku.tunnus=tilausrivi.otunnus 
					and lasku.tila='L' 
					and lasku.alatila='C'";
		$tilre = mysql_query($query) or pupe_error($query);

		while ($tilrow = mysql_fetch_array($tilre))
		{
			// etsitään sopivia tilauksia
			$query = "	SELECT lasku.yhtio yhtio, tunnus 'tilaus', concat_ws(' ', nimi, nimitark) asiakas, date_format(lasku.luontiaika, '%Y-%m-%d') laadittu, laatija
						FROM lasku
						WHERE tunnus='$tilrow[otunnus]' 
						and tila='L' $haku 
						and $yhtiolisa
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
						for ($i=0; $i<mysql_num_fields($result); $i++) {
							if (mysql_field_name($result, $i) == 'yhtio') {
								if ($yhtio != '') {
									echo "<th align='left'>",t("Yhtiö"),"</th>";
								}
							}
							else {
								echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
							}
						}
						echo "</tr>";
					}

					echo "<tr>";

					for ($i=0; $i<mysql_num_fields($result); $i++) {
						if (mysql_field_name($result, $i) == 'yhtio') {
							if ($yhtio != '') {
								echo "<td>$row[yhtio]</td>";
							}
						}
						else {
							echo "<td>$row[$i]</td>";
						}
					}

					echo "	<form method='post' action='$PHP_SELF'><td class='back'>
							<input type='hidden' name='tee' value='KORJAA'>	
							<input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
						  	<input type='hidden' name='tunnus' value='$row[tilaus]'>
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
