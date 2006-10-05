<?php
		
	//päivitetään kukarow[kesken] kun käyttäjä tekee uutta tilausta
	if ($tee == '') {
		//päivitetään kukarow[kesken] kun käyttäjä tekee uutta tilausta
		if ($tila == '' and !isset($jatka)) {
			$query = "	UPDATE kuka
						SET kesken=0
						WHERE session = '$session'";
			$result = mysql_query($query) or pupe_error($query);
			$kukarow['kesken'] = 0;
			$tilausnumero = 0;
		}

		//katsotaan että kukarow kesken ja $tilausnumero stemmaavat keskenään
		if ($tilausnumero != $kukarow["kesken"] and ($tilausnumero!='' or $kukarow["kesken"] != 0) and $aktivoinnista != 'true') {
			echo "<br><br><br>".t("VIRHE: Sinulla on useita tilauksia auki")."! ".t("Käy aktivoimassa tilaus uudestaan Tilaukset-ohjelmasta").".<br><br><br>";
			exit;
		}
	}
	
	if ($tila == '') {
		$tila = 'Muuta';
	}
	
	if ($tila == 'Muuta' and !isset($jatka)) {
		
		if ($toim == "SIIRTOLISTA") {
			echo "<font class='head'>".t("Siirtolistan otsikko")."</font><hr><br>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<font class='head'>".t("Valmistuksen otsikko")."</font><hr><br>";
		}
		
		if ($kukarow["kesken"] != 0) {
			$squery = "	SELECT *, hyvaksynnanmuutos as luokka
						FROM lasku
						WHERE tunnus = '$kukarow[kesken]'";
			$sresult = mysql_query($squery) or pupe_error($squery);
			$srow = mysql_fetch_array($sresult);
			
			// jos meillä on jo alatila ja tila ei muokkailla niitä!
			$alatila="";
			$ylatila="";
			if ($srow['alatila'] != '') $alatila=$srow['alatila'];
			if ($srow['tila'] != '')    $ylatila=$srow['tila'];
		}
	
		echo "<table>";
		
		if ($toim == "SIIRTOLISTA") {
			echo "<tr><th>".t("Valitse lähdevarasto").":</th>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<tr><th>".t("Käytä raaka-aineita varastosta").":</th>";
		}
		
		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]' order by nimitys";
		$vtresult = mysql_query($query) or pupe_error($query);

		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";
		echo "<input type='hidden' name='tee' value='OTSIK'>";
		echo "<input type='hidden' name='toim' value='$toim'>";	
		echo "<input type='hidden' name='ylatila' value='$ylatila'>";
		echo "<input type='hidden' name='alatila' value='$alatila'>";
		
		echo "<td><select name='varasto'>";
		
		if ($toim == "SIIRTOLISTA") {
			echo "<option value=''>".t("Siirrä kaikista")."</option>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<option value=''>".t("Käytä kaikista")."</option>";
		}
								
		while ($vrow = mysql_fetch_array($vtresult)) {
			$sel='';
			if ($vrow['tunnus']==$srow["varasto"]) $sel = 'selected';
			
			$varastomaa = '';
			if (strtoupper($vrow['maa']) != strtoupper($yhtiorow['maakoodi'])) {
				$varastomaa = strtoupper($vrow['maa']);
			}
			
			echo "<option value='$vrow[tunnus]' $sel>$vrow[maa] $vrow[nimitys]</option>";
		}
		echo "</select></td></tr>";
		
		if ($toim == "SIIRTOLISTA") {
			echo "<tr><th>".t("Valitse kohdevarasto").":</th>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<tr><th>".t("Valmisteiden kohdevarasto").":</th>";
		}
				
		echo "<td><select name='clearing'>";

		$query  = "	SELECT *
					FROM varastopaikat
					WHERE yhtio='$kukarow[yhtio]' order by nimitys";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares)) {

			$sel='';
			if ($varow['tunnus']==$srow["clearing"]) $sel = 'selected';
			
			$varastomaa = '';
			if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maakoodi'])) {
				$varastomaa = strtoupper($varow['maa']);
			}
			
			echo "<option value='$varow[tunnus]' $sel>$varow[maa] $varow[nimitys]</option>";			
		}

		echo "</select></td></tr>";

		if ($kukarow['kesken'] == 0) {
			$toimpp = $kerpp = date(j);
			$toimkk = $kerkk = date(n);
			$toimvv = $kervv = date(Y);
		}
		else {
			list($toimvv, $toimkk, $toimpp) = split('-', $srow["toimaika"]);
			list($kervv, $kerkk, $kerpp)    = split('-', $srow["kerayspvm"]);
			$kerpp = substr($kerpp,0,2);
			$toimpp = substr($toimpp,0,2);

		}

		echo "<tr><th>".t("Toivottu keräysajankohta").": </th><td valign='middle'>
				<input type='text' name='kerpp' value='$kerpp' size='3'>
				<input type='text' name='kerkk' value='$kerkk' size='3'>
				<input type='text' name='kervv' value='$kervv' size='6'>
				<input type='hidden' name='vkerayspvm' value='".substr($srow["kerayspvm"],0,10)."'></td></tr>";

		if ($toim == "SIIRTOLISTA") {
			echo "<tr><th>".t("Toivottu toimitusajankohta").": </th>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<tr><th>".t("Toivottu valmistusajankohta").": </th>";
		}
		
		echo "	<td valign='middle'>
				<input type='text' name='toimpp' value='$toimpp' size='3'>
				<input type='text' name='toimkk' value='$toimkk' size='3'>
				<input type='text' name='toimvv' value='$toimvv' size='6'>
				<input type='hidden' name='vtoimaika' value='".$srow["toimaika"]."'></td></tr>";

		echo "<tr>";
		
		if ($toim == "SIIRTOLISTA") {
			echo "<th>".t("Keräysprioriteetti")."</th><td>";
		}
		elseif ($toim == "VALMISTAVARASTOON") {
			echo "<th>".t("Valmistusprioriteetti")."</th><td>";		
		}
		
		$query = "	SELECT selite, selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'ASIAKASLUOKKA'
					ORDER BY jarjestys, selite";
		$tresult = mysql_query($query) or pupe_error($query);

		echo "<select name='luokka'><option value=''>".t("Oletus")."</option>";

		while($row = mysql_fetch_array($tresult)) {
			$sel = "";

			if ($row[0] == $srow["luokka"]) $sel = 'selected';


			echo "<option value='$row[0]' $sel>$row[1]</option>";
		}
		echo "</select>";


		echo "</td></tr>";
		
		echo "<tr><th>".t("Viite").":</th><td colspan='3'>
				<input type='text' size='53' name='viesti' value='$srow[viesti]'></td>";
		echo "</tr>";
		echo "<tr><th>".t("Kommentit").":</th><td colspan='3'><textarea name='comments' rows='2' cols='60'>$srow[comments]</textarea></td></tr>";
		
		
		echo "</table>";
			
		echo "<br><input type='submit' name='jatka' value='".t("Jatka")."'></form>";
	}



	if (isset($jatka)) {

		if ($kukarow["kesken"] == 0) {
			$query = "INSERT into ";
			$postquery = ",laatija = '$kukarow[kuka]', luontiaika=NOW()";
		}
		else {
			// Pidetään huolta tilausrivien toimituspäivistä ja kerayspaivasta
			$query = "	UPDATE tilausrivi
						SET kerayspvm = '".$kervv."-".$kerkk."-".$kerpp."'
						WHERE otunnus = '$kukarow[kesken]' and kerayspvm='$vkerayspvm' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE tilausrivi
						SET toimaika = '".$toimvv."-".$toimkk."-".$toimpp."'
						WHERE otunnus = '$kukarow[kesken]' and toimaika='$vtoimaika' and yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			$query = "UPDATE ";
			$postquery = " WHERE tunnus = '$kukarow[kesken]'";
		}
		
		$crlf = array("\r","\n"); // poistetaan rivinvaihdot kommentista
		$comments = str_replace($crlf, " ", $comments);

		$aquery = "	SELECT *
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus='$clearing'";
		$vtresult = mysql_query($aquery) or pupe_error($aquery);
		$vtrow = mysql_fetch_array($vtresult);
		
		
		$query .= "	lasku SET
					clearing		= '$clearing',					
					nimi 			= '$vtrow[nimitys]',	
					toimaika 		= '".$toimvv."-".$toimkk."-".$toimpp."',
					kerayspvm 		= '".$kervv."-".$kerkk."-".$kerpp."',						
					comments 		= '$comments',						
					viesti 			= '$viesti',						
					yhtio 			= '$kukarow[yhtio]',
					alatila 		= '$alatila',						
					varasto 		= '$varasto',											
					hyvaksynnanmuutos = '$luokka'";
					
		if ($toim == "VALMISTAVARASTOON") {
			$query .= ",tilaustyyppi = 'W', tila='V', ytunnus='$vtrow[nimitys]', liitostunnus='99999999999'";
			
			$tee = "";
		}
		if ($toim == "SIIRTOLISTA") {
			$query .= ",tilaustyyppi = 'G', tila='G', ytunnus='SIIRTO', liitostunnus='99999999999'";		
			
			$tee = "";
		}				
				
		$query .= $postquery;
		$result = mysql_query($query) or pupe_error($query);

		if ($kukarow["kesken"] == 0) {
			$id = mysql_insert_id();

			$query = "	UPDATE kuka
						SET kesken = '$id'
						WHERE yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
			$result = mysql_query($query) or pupe_error($query);
			
			$kukarow["kesken"] = $id;
			$tilausnumero = $id;
		}
	}
?>