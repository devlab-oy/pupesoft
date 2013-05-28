<?php

	// käsittämätön juttu, mutta ei voi muuta
	if ($_POST["voipcall"] != "") $_GET["voipcall"]  = "";

	require ("../inc/parametrit.inc");

	if (!isset($konserni)) 	$konserni = '';
	if (!isset($tee)) 		$tee = '';
	if (!isset($oper)) 		$oper = '';

	if ($voipcall == "call" and $o != "" and $d != "") {
		ob_start();
		$retval = @readfile($VOIPURL."&o=$o&d=$d");
		$retval = ob_get_contents();
		ob_end_clean();
		if ($retval != "OK") echo "<font class='error'>Soitto $o -&gt; $d epäonnistui!</font><br><br>";
	}

	echo "<font class='head'>".t("Asiakaslista")."</font><hr>";

	echo "<form method='post'>
			<input type='hidden' name='voipcall' value='kala'>";

	$monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA", "ASIAKASPIIRI", "ASIAKASMYYJA", "ASIAKASTILA", "<br>DYNAAMINEN_ASIAKAS");
	$monivalintalaatikot_normaali = array();
	$noautosubmit = TRUE;

	require ("tilauskasittely/monivalintalaatikot.inc");

	$chk = "";

	if (trim($konserni) != '') {
		$chk = "CHECKED";
	}

	echo "<br>".t("Näytä konsernin kaikki asiakkaat").": <input type='checkbox' name='konserni' $chk onclick='submit();'><br>";

	if ($yhtiorow['viikkosuunnitelma'] == '') {
		$kentat = "asiakas.tunnus::asiakas.nimi::asiakas.asiakasnro::asiakas.ytunnus::if (asiakas.toim_postitp!='',asiakas.toim_postitp,asiakas.postitp)::asiakas.postino::asiakas.yhtio::asiakas.myyjanro::asiakas.email";
	}
	else {
		$kentat = "asiakas.tunnus::asiakas.nimi::asiakas.myyjanro::asiakas.ytunnus::asiakas.asiakasnro::if (asiakas.toim_postitp!='',asiakas.toim_postitp,asiakas.postitp)::asiakas.yhtio";
	}

	$jarjestys = 'selaus, nimi';

	$array = explode("::", $kentat);
	$count = count($array);

	for ($i = 0; $i <= $count; $i++) {
		if (isset($haku[$i]) and strlen($haku[$i]) > 0) {
			if ($array[$i] == "asiakas.nimi") {
				$lisa .= " and (asiakas.nimi like '%".$haku[$i]."%' or asiakas.toim_nimi like '%".$haku[$i]."%')";
				$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
			}
			else {
				$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
				$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
			}
		}
	}

	if (strlen($ojarj) > 0) {
		$jarjestys = $ojarj;
	}

	$lisa .= " and asiakas.laji != 'P' ";

	if (trim($konserni) != '') {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = pupe_query($query);
		$konsernit = "";

		while ($row = mysql_fetch_array($result)) {
			$konsernit .= " '".$row["yhtio"]."' ,";
		}
		$konsernit = " asiakas.yhtio in (".substr($konsernit, 0, -1).") ";
	}
	else {
		$konsernit = " asiakas.yhtio = '$kukarow[yhtio]' ";
	}

	if ($yhtiorow['viikkosuunnitelma'] == '') {
		if ($tee == "lahetalista") {
			$query = "	SELECT asiakas.tunnus, asiakas.nimi, asiakas.postitp, asiakas.ytunnus, asiakas.yhtio, asiakas.asiakasnro, asiakas.nimitark,
						asiakas.osoite, asiakas.postino, asiakas.postitp, asiakas.maa, asiakas.toim_nimi, asiakas.toim_nimitark, asiakas.toim_osoite,
						asiakas.toim_postino, asiakas.toim_postitp, asiakas.toim_maa, asiakas.puhelin, asiakas.fax, asiakas.myyjanro, asiakas.email,
						asiakas.osasto, asiakas.piiri, asiakas.ryhma, asiakas.fakta, asiakas.toimitustapa, asiakas.yhtio
						FROM asiakas
						WHERE $konsernit
						$lisa";
			$tiednimi = "asiakaslista.xls";
		}
		else {
			$query = "	SELECT asiakas.tunnus, if (asiakas.nimi != asiakas.toim_nimi, CONCAT(asiakas.nimi, '<br />', asiakas.toim_nimi), asiakas.nimi) nimi,
						asiakas.asiakasnro, asiakas.ytunnus,  if (asiakas.toim_postitp != '', asiakas.toim_postitp, asiakas.postitp) postitp,
						if (asiakas.toim_postino != 00000, asiakas.toim_postino, asiakas.postino) postino,
						asiakas.yhtio, asiakas.myyjanro, asiakas.email, asiakas.puhelin $selectlisa
						FROM asiakas
						WHERE $konsernit
						$lisa";
			$tiednimi = "viikkosuunnitelma.xls";
		}
	}
	else {
		$query = "	SELECT asiakas.tunnus, asiakas.nimi, (SELECT concat_ws(' ', kuka.myyja, kuka.nimi) FROM kuka WHERE kuka.yhtio = '$kukarow[yhtio]' AND kuka.myyja = asiakas.myyjanro AND kuka.myyja > 0 LIMIT 1) myyja,
					asiakas.ytunnus, asiakas.asiakasnro, if (asiakas.toim_postitp != '', asiakas.toim_postitp, asiakas.postitp) postitp,
					asiakas.puhelin, asiakas.yhtio
					FROM asiakas
					WHERE $konsernit
					$lisa";
	}
	if ($lisa == "" and ($tee != 'laheta' or $tee != 'lahetalista')) {
		$limit = " LIMIT 200 ";
	}
	else {
		$limit = " ";
	}

	$query .= "$ryhma ORDER BY $jarjestys $limit";
	$result = pupe_query($query);

	if ($oper == t("Vaihda listan kaikkien asiakkaiden tila")) {
		// Käydään lista läpi kertaalleen
		while ($trow = mysql_fetch_array ($result)) {
			$query_update = "	UPDATE asiakas
								SET tila = '$astila_vaihto'
								WHERE tunnus = '$trow[tunnus]'
								AND yhtio = '$yhtiorow[yhtio]'";
			$result_update = pupe_query($query_update);
		}
		$result = pupe_query($query);
	}

	if ($tee == 'laheta' or $tee == 'lahetalista') {

		if ($tee == "lahetalista") {
			if (@include('Spreadsheet/Excel/Writer.php')) {
				//keksitään failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;

				if (isset($workbook)) {
					$excelsarake = 0;

					for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
						if (isset($workbook)) {
							$worksheet->write($excelrivi, $excelsarake, t(mysql_field_name($result,$i)) , $format_bold);
							$excelsarake++;
						}
					}

					$excelsarake = 0;
					$excelrivi++;
				}
			}
		}
		else {
			if (@include('Spreadsheet/Excel/Writer.php')) {
				//keksitään failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;

				if (isset($workbook)) {
					$excelsarake = 0;

					for ($i=1; $i<mysql_num_fields($result); $i++) {
						//$liite .= $trow[$i]."\t";
						if (isset($workbook)) {
							$worksheet->write($excelrivi, $excelsarake, t(mysql_field_name($result,$i)) , $format_bold);
							$excelsarake++;
						}
					}

					$worksheet->write($excelrivi, $excelsarake, t("pvm") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("kampanjat") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("pvm käyty") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("km") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("lähtö") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("paluu") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("pvraha") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("kommentti") , $format_bold);

					$excelsarake = 0;
					$excelrivi++;
				}
			}
		}

		while ($trow = mysql_fetch_array($result)) {
			$excelsarake = 0;
			for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
				if (isset($workbook)) {
					$worksheet->writeString($excelrivi, $excelsarake, $trow[$i], $format_bold);
					$excelsarake++;
				}
			}
			$excelrivi++;
		}

		if (isset($workbook)) {
			// We need to explicitly close the workbook
			$workbook->close();
		}

		$liite = "/tmp/".$excelnimi;

		$bound = uniqid(time()."_") ;

		$header  = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content = "--$bound\n" ;

		$content .= "Content-Type: application/excel; name=\"".basename($liite)."\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: inline; filename=\"".basename($tiednimi)."\"\n\n";

		$handle  = fopen($liite, "r");
		$sisalto = fread($handle, filesize($liite));
		fclose($handle);

		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\n" ;

		if ($tee == "lahetalista") {
			mail($kukarow['eposti'], mb_encode_mimeheader("Asiakkaiden tiedot", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
			echo "<br><br><font class='message'>".t("Asiakkaiden tiedot sähköpostiisi")."!</font><br><br><br>";
		}
		else {
			mail($kukarow['eposti'], mb_encode_mimeheader("Viikkosunnitelmapohja", "ISO-8859-1", "Q"), $content, $header, "-f $yhtiorow[postittaja_email]");
			echo "<br><br><font class='message'>".t("Suunnitelmapohja lähetetty sähköpostiisi")."!</font><br><br><br>";
		}

		mysql_data_seek($result,0);
	}

	echo "<br><table>";
	echo "<tr>";

	for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
		echo "<th><a href='$PHP_SELF?konserni=$konserni&ojarj=".mysql_field_name($result,$i).$ulisa."'>" . t(mysql_field_name($result,$i)) . "</a>";

		if 	(mysql_field_len($result,$i)>10) $size='20';
		elseif	(mysql_field_len($result,$i)<5)  $size='5';
		else	$size='10';

		if (!isset($haku[$i])) $haku[$i] = '';

		echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
		echo "</th>";
	}

	echo "<td class='back'>&nbsp;&nbsp;<input type='Submit' value='".t("Etsi")."'></td></tr>\n\n";

	$kalalask = 1;

	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr class='aktiivi'>";

		for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
			if ($i == 1) {
				if (trim($trow[1]) == '') $trow[1] = "".t("*tyhjä*")."";
				echo "<td><a name='1_$kalalask' href='".$palvelin2."crm/asiakasmemo.php?ytunnus=$trow[ytunnus]&asiakasid=$trow[tunnus]&lopetus=".$palvelin2."crm/asiakaslista.php////konserni=$konserni//ojarj=$ojarj".str_replace("&", "//", $ulisa)."///1_$kalalask'>$trow[1]</a></td>";
			}
			elseif (mysql_field_name($result,$i) == 'ytunnus') {
				echo "<td><a name='2_$kalalask' href='".$palvelin2."yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=".$palvelin2."crm/asiakaslista.php////konserni=$konserni//ojarj=$ojarj".str_replace("&", "//", $ulisa)."///2_$kalalask'>$trow[$i]</a></td>";
			}
			else {
				echo "<td>$trow[$i]</td>";
			}
		}

		echo "<td class='back'>";

		if ($trow["puhelin"] != "" and $kukarow["puhno"] != "" and isset($VOIPURL)) {
			$d = ereg_replace("[^0-9]", "", $trow["puhelin"]);  // dest
			$o = ereg_replace("[^0-9]", "", $kukarow["puhno"]); // orig
			echo "<a href='$PHP_SELF?konserni=$konserni&ojarj=$ojarj&o=$o&d=$d&voipcall=call".$ulisa."'>Soita $o -&gt; $d</a>";
		}

		echo "</td>";
		echo "</tr>\n\n";

		$kalalask++;
	}
	echo "</table>";

	if ($yhtiorow['viikkosuunnitelma'] == '') {
		echo "<br><br>";
		echo "<li><a href='$PHP_SELF?tee=laheta&konserni=$konserni".$ulisa."'>".t("Lähetä viikkosuunnitelmapohja sähköpostiisi")."</a><br>";
		echo "<li><a href='$PHP_SELF?tee=lahetalista&konserni=$konserni".$ulisa."'>".t("Lähetä asiakaslista sähköpostiisi")."</a><br>";
	}

	$asosresult = t_avainsana("ASIAKASTILA");

	if (mysql_num_rows($asosresult) > 0) {
		echo "<br/>";
		echo t("Vaihda asiakkaiden tila").": <select name='astila_vaihto'>";

		while ($asosrow = mysql_fetch_array($asosresult)) {
			$sel2 = '';
			if ($astila == $asosrow["selite"]) {
				$sel2 = "selected";
			}
			echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
		}

		echo "</select></td></tr>\n\n";
		echo "<input type=\"submit\" name=\"oper\" value=\"".t("Vaihda listan kaikkien asiakkaiden tila")."\">";
	}

	echo "</form>";

	require ("inc/footer.inc");

?>