<?php
	
	// käsittämätön juttu, mutta ei voi muuta
	if ($_POST["voipcall"] != "") $_GET["voipcall"]  = "";

	require ("../inc/parametrit.inc");

	if ($voipcall == "call" and $o != "" and $d != "") {
		ob_start();
		$retval = @readfile($VOIPURL."&o=$o&d=$d");
		$retval = ob_get_contents();
		ob_end_clean();
		if ($retval != "OK") echo "<font class='error'>Soitto $o -&gt; $d epäonnistui!</font><br><br>";
	}
	
	$otsikko   = 'Asiakaslista';
	$kentat    = "tunnus::if(toim_postitp!='',toim_postitp,postitp)::postino::ytunnus::yhtio::nimi";
	$jarjestys = 'selaus, nimi';

	echo "<font class='head'>".t("$otsikko")."</font><hr>";
	
	$array = split("::", $kentat);
	$count = count($array);
	for ($i=0; $i<=$count; $i++) {
		if (strlen($haku[$i]) > 0) {
			$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
			$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
		}
	}
	if (strlen($ojarj) > 0) {
		$jarjestys = $ojarj;
	}
	
	if ($asos != '') {
		$lisa .= " and osasto='$asos' ";
	}
	
	if ($aspiiri != '') {
		$lisa .= " and piiri='$aspiiri' ";
	}
	
	if ($asryhma != '') {
		$lisa .= " and ryhma='$asryhma' ";
	}
	
	if ($asmyyja != '') {
		$lisa .= " and myyjanro='$asmyyja' ";
	}
	
	if (trim($konserni) != '') {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = mysql_query($query) or pupe_error($query);
		$konsernit = "";
		
		while ($row = mysql_fetch_array($result)) {	
			$konsernit .= " '".$row["yhtio"]."' ,";
		}		
		$konsernit = " yhtio in (".substr($konsernit, 0, -1).") ";			
	}
	else {
		$konsernit = " yhtio = '$kukarow[yhtio]' ";
	}
	
	
	if ($tee == "lahetalista") {		
		$query = "	SELECT tunnus, postitp, ytunnus, yhtio, nimi, nimitark, osoite, postino, postitp, maa, toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, toim_maa,
					puhelin, fax, email, osasto, piiri, ryhma, fakta, toimitustapa, yhtio
					FROM asiakas 
					WHERE $konsernit 
					$lisa";
	}
	else {
		$query = "	SELECT tunnus, if(toim_postitp!='',toim_postitp,postitp) postitp, if(toim_postino!=00000,toim_postino,postino) postino, ytunnus, yhtio, nimi, puhelin
					FROM asiakas 
					WHERE $konsernit 
					$lisa";
	}			
				
	if ($lisa == "") {
		$limit = " LIMIT 200 ";
	}
				
	$query .= "$ryhma ORDER BY $jarjestys $limit";
	$result = mysql_query($query) or pupe_error($query);

	
	if ($tee == 'laheta' or $tee == 'lahetalista') {
		
		if ($tee == "lahetalista") {
			$liite = "paikka\tytunnus\tnimi\tnimitark\tosoite\tpostino\tpostitp\tmaa\ttoim_nimi\ttoim_nimitark\ttoim_osoite\ttoim_postino\ttoim_postitp\ttoim_maa\tpuhelin\tfax\temail\tosasto\tpiiri\tryhma\tfakta\ttoimitustapa\r\n";
		}
		else {
			$liite = "postitp\tpostino\tytunnus\tyhtio\tnimi\tpvm\tkampanjat\tpvm käyty\tkm\tlähtö\tpaluu\tpvraha\tkommentit\r\n";
		}
		while ($trow=mysql_fetch_array ($result)) {
			for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
				$liite .= $trow[$i]."\t";
			}
			$liite .= "\r\n";
		}
		
		
		
		$bound = uniqid(time()."_") ;
		
		$header  = "From: <$yhtiorow[postittaja_email]>\r\n";
		$header .= "MIME-Version: 1.0\r\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;
		
		$content = "--$bound\r\n" ;
		
		$content .= "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n";
		$content .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
		$content .= "\r\n\r\n"; 
		
		$content .= "--$bound\r\n" ;
					
		$content .= "Content-Type: application/vnd.ms-excel\r\n" ;
		$content .= "Content-Transfer-Encoding: base64\r\n" ;
		$content .= "Content-Disposition: attachment; filename=\"viikkosuunnitelma.xls\"\r\n\r\n";
		$content .= chunk_split(base64_encode($liite));
		$content .= "\r\n";
					
		$to = $kukarow['eposti'];
		
		if ($tee == "lahetalista") {
			mail($to, "Asiakkaiden tiedot", $content, $header, "-f $yhtiorow[postittaja_email]");
			echo "<br><br><font class='message'>".t("Asiakkaiden tiedot sähköpostiisi")."!</font><br><br><br>";
		}
		else {
			mail($to, "Viikkosunnitelmapohja", $content, $header, "-f $yhtiorow[postittaja_email]");
			echo "<br><br><font class='message'>".t("Suunnitelmapohja lähetetty sähköpostiisi")."!</font><br><br><br>";
		}
		
		
		mysql_data_seek($result,0);
		
	}

	echo "<li><a href='$PHP_SELF?tee=laheta&asos=$asos&asryhma=$asryhma&aspiiri=$aspiiri&konserni=$konserni".$ulisa."'>".t("Lähetä viikkosuunnitelmapohja sähköpostiisi")."</a><br>";
	echo "<li><a href='$PHP_SELF?tee=lahetalista&asos=$asos&asryhma=$asryhma&aspiiri=$aspiiri&konserni=$konserni".$ulisa."'>".t("Lähetä asiakaslista sähköpostiisi")."</a><br>";
	
	echo "<br><table>
			<form action='$PHP_SELF' method='post'>
			<input type='hidden' name='voipcall' value='kala'>";
	
	
	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','ASOSASTO_')."
				WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='ASIAKASOSASTO' order by avainsana.selite+0";
	$asosresult = mysql_query($query) or pupe_error($query);
	
	echo "<tr><th>".t("Valitse asiakkaan osasto").":</th><td><select name='asos' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki osastot")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asos == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] $asosrow[selitetark]</option>";
	}
	echo "</select></td>";
	
	
	$chk = "";
	if (trim($konserni) != '') {
		$chk = "CHECKED";
	}
	
	echo "<th>".t("Näytä konsernin kaikki asiakkaat").":</th><td><input type='checkbox' name='konserni' $chk onclick='submit();'></td>";
	echo "</tr>\n\n";
	
	$query = "	SELECT distinct piiri
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and piiri != '' order by piiri+0";
	$asosresult = mysql_query($query) or pupe_error($query);
	
	echo "<tr><th>".t("Valitse asiakkaan piiri").":</th><td><select name='aspiiri' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki piirit")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($aspiiri == $asosrow["piiri"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[piiri]' $sel2>$asosrow[piiri]</option>";
	}
	echo "</select></td>";
	
	$query = "	SELECT distinct avainsana.selite, ".avain('select')."
				FROM avainsana
				".avain('join','ASRYHMA_')."
				WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='ASIAKASRYHMA' order by avainsana.selite+0";
	$asosresult = mysql_query($query) or pupe_error($query);
	
	echo "<th>".t("Valitse asiakkaan ryhmä").":</th><td><select name='asryhma' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki ryhmät")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asryhma == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] $asosrow[selitetark]</option>";
	}
	
	echo "</select></td></tr>\n\n";
					
					
	$query = "	SELECT distinct myyjanro
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' and myyjanro!=0  order by myyjanro";
	$asosresult = mysql_query($query) or pupe_error($query);
	
	echo "<tr>";
	echo "<th>".t("Valitse asiakkaan myyjä").":</th><td><select name='asmyyja' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki myyjät")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asmyyja == $asosrow["myyjanro"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[myyjanro]' $sel2>$asosrow[myyjanro]</option>";
	}
	
	echo "</select></td></tr>\n\n";				
					
					
	echo "</table><br><table>";
	
	
	echo "<tr>";
	
	
	for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
		echo "<th><a href='$PHP_SELF?asos=$asos&asryhma=$asryhma&aspiiri=$aspiiri&konserni=$konserni&ojarj=".mysql_field_name($result,$i).$ulisa."'>" . t(mysql_field_name($result,$i)) . "</a>";

		if 	(mysql_field_len($result,$i)>10) $size='20';
		elseif	(mysql_field_len($result,$i)<5)  $size='5';
		else	$size='10';

		echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
		echo "</th>";
	}

	echo "<td class='back'>&nbsp;&nbsp;<input type='Submit' value='".t("Etsi")."'></td></form></tr>\n\n";

	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr>";
		for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
			if ($i == 1) {
				if (trim($trow[1]) == '') $trow[1] = "".t("*tyhjä*")."";
				echo "<td><a href='asiakasmemo.php?ytunnus=$trow[ytunnus]'>$trow[1]</a></td>";
			}
			elseif(mysql_field_name($result,$i) == 'ytunnus') {
				echo "<td><a href='../yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=crm/asiakaslista.php'>$trow[$i]</a></td>";
			}
			else {
				echo "<td>$trow[$i]</td>";
			}
		}
		
		echo "<td class='back'>";		

		if ($trow["puhelin"] != "" and $kukarow["puhno"] != "" and isset($VOIPURL)) {
			$d = ereg_replace("[^0-9]", "", $trow["puhelin"]);  // dest
			$o = ereg_replace("[^0-9]", "", $kukarow["puhno"]); // orig
			echo "<a href='$PHP_SELF?asos=$asos&asryhma=$asryhma&aspiiri=$aspiiri&konserni=$konserni&ojarj=$ulisa&o=$o&d=$d&voipcall=call'>Soita $o -&gt; $d</a>";
		}

		echo "</td>";		
		echo "</tr>\n\n";
	}
	echo "</table>";

	require ("../inc/footer.inc");
?>