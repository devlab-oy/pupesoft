<?php

if (isset ( $_POST ["tee"] )) {
	if ($_POST ["tee"] == 'lataa_tiedosto')
		$lataa_tiedosto = 1;
	if ($_POST ["kaunisnimi"] != '')
		$_POST ["kaunisnimi"] = str_replace ( "/", "", $_POST ["kaunisnimi"] );
}

require ("../inc/parametrit.inc");

if (isset ( $tee ) and $tee == "lataa_tiedosto") {
	readfile ( "/tmp/" . $tmpfilenimi );
	exit ();
}
elseif (isset ( $tee ) and $tee == "ftp") {

	$file = "/var/www/html/pupesoft/datain/findex.htm";
	$remote_file = "/htdocs/varasto/index.htm";
	#echo $file;
	#echo $remote_file;
	// set up basic connection
	$conn_id = ftp_connect("www.maisematukku.fi");

	// login with username and password
	$login_result = ftp_login($conn_id, "maisematukku", "f9jk34");
	echo "$login_result\n";
	ftp_pasv($conn_id, true);
	// upload a file
	if (ftp_put($conn_id, $remote_file, $file, FTP_BINARY)) {
	 echo "Lataus onnistui $file\n";
	} else {
	 echo "Latauksessa oli ongelmia: $file\n";
	}

	// close the connection
	ftp_close($conn_id);

	exit ();
}  else {
	echo "<font class='head'>" . t ( "Varasto ftp" ) . "</font><hr>";
	
	// k‰ytet‰‰n slavea
	$useslave = 1;
	require ("inc/connect.inc");
	
	if (count ( $_POST ) > 0 and isset ( $go )) {
		
		if (count ( $yhtiot ) == 0) {
			$yhtio = $kukarow ["yhtio"];
		} else {
			$yhtio = "";
			
			foreach ( $yhtiot as $apukala ) {
				$yhtio .= "'$apukala',";
			}
			
			$yhtio = substr ( $yhtio, 0, - 1 );
		}
		
		$lisa = "";
		$lisa2 = "";
		
		if (is_array ( $mul_osasto ) and count ( $mul_osasto ) > 0) {
			$sel_osasto = "('" . str_replace ( array ('PUPEKAIKKIMUUT', ',' ), array ('', '\',\'' ), implode ( ",", $mul_osasto ) ) . "')";
			$lisa .= " and tuote.osasto in $sel_osasto ";
		}
		
		if (is_array ( $mul_try ) and count ( $mul_try ) > 0) {
			$sel_tuoteryhma = "('" . str_replace ( array ('PUPEKAIKKIMUUT', ',' ), array ('', '\',\'' ), implode ( ",", $mul_try ) ) . "')";
			$lisa .= " and tuote.try in $sel_tuoteryhma ";
		}
		
		if ($nollapiilo != '') {
			$lisa2 = " HAVING (((saldo+varattu+tulossa+ostot+myynti+myynti_ed)!=0)) ";
		}
		if ($halyraja != '') {
			if ($raja == '') {
				$raja = '<=0';
			}
			if (is_numeric($raja)) {
				$raja = '<='.$raja;
			}
			$lisa2 = " HAVING ((((vapaa)$raja)) AND (((saldo+varattu+tulossa+ostot+myynti+myynti_ed)!=0)))";
		}
		if ($poispiilo != '') {
			if ($lisa2 == "") {
				$lisa2 = " HAVING (status not in ('P','X') or (saldo>0))";
			} else {
				$lisa2 .= " and (status not in ('P','X') or (saldo > 0))";
			}
		}
		
		if ($yhtioittain != '') {
			$yhtioittain1 = " yhtio, ";
			$yhtioittain2 = " ,4 ";
		} else {
			$yhtioittain1 = "";
			$yhtioittain2 = "";
		}
		
		$vvaa = $vva - '1';
		$vvll = $vvl - '1';
		
		$query = "	SELECT
							tuote.status,
							avainsana.selitetark osasto,
							tuote.tuoteno, 
							tuote.nimitys Nimi,			
								
								tuotteen_avainsanat.selite Fi,
								tuote.mallitarkenne Vyˆh,
								
							
							
							
							((ifnull(sum((SELECT sum(saldo) 
										FROM tuotepaikat 
										WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno)), 0))
										-(ifnull(sum((SELECT sum(if(tilausrivin_lisatiedot.osto_vai_hyvitys = '' or tilausrivin_lisatiedot.osto_vai_hyvitys is null, varattu, 0)) 
										FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_varattu)
										LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
										WHERE tilausrivi.yhtio=tuote.yhtio and tilausrivi.tyyppi = 'L' and tilausrivi.tuoteno=tuote.tuoteno and tilausrivi.varattu!=0)), 0))
										+(ifnull(	sum(
							(SELECT  sum(varattu) 
										FROM tilausrivi USE INDEX (yhtio_tyyppi_tuoteno_varattu) 
										WHERE tilausrivi.yhtio=tuote.yhtio and tilausrivi.tyyppi = 'O' 
										and tilausrivi.tuoteno=tuote.tuoteno and (tilausrivi.varattu>0)
										)), 0))
										) VAPAA
							FROM {OJ tuote AS tuote
							LEFT OUTER JOIN asiakashinta AS asiakashinta ON tuote.tuoteno = asiakashinta.tuoteno
							LEFT OUTER JOIN avainsana AS avainsana ON tuote.osasto = avainsana.selite 
							LEFT outer JOIN tuotteen_avainsanat    ON tuote.tuoteno=tuotteen_avainsanat.tuoteno}
							WHERE ((
							avainsana.laji = 'osasto'
							OR avainsana.laji IS NULL
							)
							AND (
							asiakashinta.asiakas_ryhma = '9'
							OR asiakashinta.asiakas_ryhma IS NULL
							
						
							) ) and
							
							tuote.yhtio in ($yhtio)
							$lisa
							and avainsana.selite=tuote.osasto and avainsana.laji='osasto'
							 and tuotteen_avainsanat.kieli = 'fi' 
							GROUP BY 1,2,3 $yhtioittain2
							HAVING (VAPAA>0)
							ORDER BY tuote.osasto, tuote.nimitys, tuote.yhtio";
		$result = mysql_query ( $query ) or pupe_error ( $query );
		
		$rivilimitti = 1000;
		#$riveja = mysql_num_rows($result);
		if (mysql_num_rows ( $result ) > $rivilimitti) {
			echo "<br><font class='error'>" . t ( "Hakutulos oli liian suuri" ) . "!</font><br>";
			echo "<font class='error'>" . t ( "Tallenna/avaa tulos exceliss‰" ) . "!</font><br><br>";
		}
		
		if (mysql_num_rows ( $result ) > 0) {
			if (@include ('Spreadsheet/Excel/Writer.php')) {
				//require_once "Spreadsheet/Excel/Writer.php";
				//void Format::setTextWrap ( )
				

				//keksit‰‰n failille joku varmasti uniikki nimi:
				list ( $usec, $sec ) = explode ( ' ', microtime () );
				mt_srand ( ( float ) $sec + (( float ) $usec * 100000) );
				$excelnimi = md5 ( uniqid ( mt_rand (), true ) ) . ".xls";
				
				$workbook = new Spreadsheet_Excel_Writer ( '/tmp/' . $excelnimi );
				$workbook->setVersion ( 8 );
				$worksheet = & $workbook->addWorksheet ( 'Sheet 1' );
				
				$format_bold = & $workbook->addFormat ();
				$format_bold->setBold ();
				
				$excelrivi = 0;
			}
			
			echo "<table>";
			echo "<tr>
						<th>" . t ( "Kausi nyt" ) . "</th>
						<td>$ppa</td>
						<td>$kka</td>
						<td>$vva</td>
						<th>-</th>
						<td>$ppl</td>
						<td>$kkl</td>
						<td>$vvl</td>
						</tr>\n";
			echo "<tr>
						<th>" . t ( "Kausi ed" ) . "</th>
						<td>$ppa</td>
						<td>$kka</td>
						<td>$vvaa</td>
						<th>-</th>
						<td>$ppl</td>
						<td>$kkl</td>
						<td>$vvll</td>
						</tr>\n";
			echo "</table><br>";
			#$osastonimi=mysql_result($result,1,10);
			#echo $osastonimi."<br>";
			
			$f = fopen("/var/www/html/pupesoft/datain/findex.htm", "w"); 
			if (mysql_num_rows ( $result ) <= $rivilimitti)
				echo "<table><tr>";
				fwrite($f, '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">
<HTML>
<HEAD>	
	<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=iso-8859-1">
	<TITLE></TITLE>
	<STYLE>
		<!-- 
		BODY,DIV,TABLE,THEAD,TBODY,TFOOT,TR,TH,TD,P { font-family:"Arial"; font-size:x-small }
		 -->
	</STYLE>	
</HEAD>
<BODY TEXT="#000000">
<script type="text/javascript" language="JavaScript" src="find2.js"></script>
<TABLE CELLSPACING="0" COLS="6" BORDER="2">
	<COLGROUP SPAN="2" WIDTH="30"></COLGROUP>
	<COLGROUP WIDTH="181"></COLGROUP>
	<COLGROUP WIDTH="142"></COLGROUP>
	<COLGROUP SPAN="2" WIDTH="30"></COLGROUP>'); 
			
	// echotaan kenttien nimet
			for($i = 1; $i < (mysql_num_fields ( $result )); $i ++) {
				#$nimiots=mysql_field_name($result,$i);
				

				if (mysql_num_rows ( $result ) <= $rivilimitti)
					echo "<th>" . t ( mysql_field_name ( $result, $i ) ) . "</th>";
					fwrite($f, "<th>" . t ( mysql_field_name ( $result, $i ) ) . "</th>"); 
			}
			
			if (isset ( $workbook )) {
				for($i = 1; $i < mysql_num_fields ( $result ); $i ++)
				#$otsikko=mysql_field_name ( $result, $i );
					#if ($otsikko == "Nimi"){$worksheet->write ( $excelrivi, ($i - 1), ucfirst ( t ( mysql_field_name ( $result, $i ) ) ), $format_bold );}
					$worksheet->write ( $excelrivi, ($i - 1), ucfirst ( mysql_field_name ( $result, $i ) ) , $format_bold );
				$excelrivi ++;
			}
			
			if (mysql_num_rows ( $result ) <= $rivilimitti)
				echo "</tr>\n";
				fwrite($f, "</tr>\n"); 
			
			if (mysql_num_rows ( $result ) > $rivilimitti) {
				
				require_once ('inc/ProgressBar.class.php');
				$bar = new ProgressBar ();
				$elements = mysql_num_rows ( $result ); // total number of elements to process
				$bar->initialize ( $elements ); // print the empty bar
			}
			
			while ( $row = mysql_fetch_array ( $result ) ) {
				$rivilla = (mysql_num_rows ( $result ));
				
				if (mysql_num_rows ( $result ) > $rivilimitti)
					$bar->increase ();
				
				if (mysql_num_rows ( $result ) <= $rivilimitti)
					echo "<tr>";
				fwrite($f, "</tr>"); 
				
				if ($osastonimi != $row ['osasto']) {
					$osastonimi = $row ['osasto'];
					echo "<tr><td>" . $osastonimi . "</td></tr>\n";
				fwrite($f, "<tr><td>" . $osastonimi . "</td></tr>\n"); 
				}
				
				// echotaan kenttien sis‰lt‰
				$kenttalkm = mysql_num_fields ( $result );
				for($i = 1; $i < mysql_num_fields ( $result ); $i ++) {
					$kenttanimi = mysql_field_name ( $result, $i );
					
					
					// jos kyseessa on tuoteosasto, haetaan sen nimi
					if (mysql_field_name ( $result, $i ) == "osasto") {
						$osre = t_avainsana ( "OSASTO", "", "and avainsana.selite  = '$row[$i]'", $yhtio );
						$osrow = mysql_fetch_array ( $osre );
						
						if ($osrow ['selitetark'] != "" and $osrow ['selite'] != $osrow ['selitetark']) {
							$row [$i] = $row [$i] . " " . $osrow ['selitetark'];
						}
					}
					
					// jos kyseessa on tuoteryhm‰, haetaan sen nimi
					if (mysql_field_name ( $result, $i ) == "tuoteryhm‰") {
						$osre = t_avainsana ( "TRY", "", "and avainsana.selite  = '$row[$i]'", $yhtio );
						$osrow = mysql_fetch_array ( $osre );
						
						if ($osrow ['selitetark'] != "" and $osrow ['selite'] != $osrow ['selitetark']) {
							$row [$i] = $row [$i] . " " . $osrow ['selitetark'];
						}
					}
					
					// hoidetaan pisteet piluiksi!!
					if (is_numeric ( $row [$i] ) and $row [$i] == 0) {
						if (mysql_num_rows ( $result ) <= $rivilimitti)
							echo "<td></td>";
							fwrite($f, "<td></td>");

						
						if (isset ( $workbook )) {
							$worksheet->writeString ( $excelrivi, ($i - 1), "" );
						} 									#or mysql_field_type ( $result, $i ) == 'int'									
					} elseif (is_numeric ( $row [$i] ) and (mysql_field_type ( $result, $i ) == 'real' )) {
						if (mysql_num_rows ( $result ) <= $rivilimitti)
							echo "<td valign='top' align='right'>" . sprintf ( "%d", $row [$i] ) . "</td>";
							fwrite($f, "<td valign='top' align='right'>" . sprintf ( "%d", $row [$i] ) . "</td>");
						if (isset ( $workbook )) {
							$worksheet->writeNumber ( $excelrivi, ($i - 1), sprintf ( "%.02f", $row [$i] ) );
						}
					} else {
						if (mysql_num_rows ( $result ) <= $rivilimitti)
							echo "<td valign='top'>$row[$i]</td>";
							fwrite($f, "<td valign='top'>$row[$i]</td>");
						
						if (isset ( $workbook )) {
							$worksheet->writeString ( $excelrivi, ($i - 1), strip_tags ( str_replace ( "<br>", " / ", $row [$i] ) ) );
						}
					}
				
				}
				//$osastonimi=$row['selitetark'];
				

				if (mysql_num_rows ( $result ) <= $rivilimitti)
					echo "</tr>\n";
					fwrite($f, "</tr>\n");

				$excelrivi ++;
				// echo v‰lill‰ otsikoita
				if (($excelrivi % 25) == 0) {
					echo "<tr>";
					fwrite($f, "</tr>");
					for($oi = 1; $oi < mysql_num_fields ( $result ); $oi ++) {
						if (mysql_num_rows ( $result ) <= $rivilimitti)
							echo "<th>" . t ( mysql_field_name ( $result, $oi ) ) . "</th>";
							fwrite($f, "<th>" . t ( mysql_field_name ( $result, $oi ) ) . "</th>");
					}
					echo "</tr>\n";
					fwrite($f, "</tr>\n");
					if (isset ( $workbook )) {
						for($oi = 1; $oi < mysql_num_fields ( $result ); $oi ++)
							$worksheet->write ( $excelrivi, ($oi - 1), ucfirst ( t ( mysql_field_name ( $result, $oi ) ) ), $format_bold );
						$excelrivi ++;
					}
				}
			
			}
			
			if (mysql_num_rows ( $result ) <= $rivilimitti)
				echo "</table>";
				fwrite($f, "</table>");
			
			echo "<br>";
			fwrite($f, "<br>");
			fclose($f);
			chmod($f, 0777);
			


			
			if (isset ( $workbook )) {
				// We need to explicitly close the workbook
				$workbook->close ();
				
				echo "<table>";
				echo "<tr><th>" . t ( "Tallenna tulos" ) . ":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='Varastotilasto.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td class='back'><input type='submit' value='" . t ( "Tallenna" ) . "'></td></tr></form>";
				echo "</table><br>";
				echo "<table>";
				echo "<tr><th>" . t ( "L‰het‰ tulos nettiin" ) . ":</th>";
				echo "<form method='post' action='varastotilastonet_ftp.php'>";
				echo "<input type='hidden' name='tee' value='ftp'>";
				echo "<input type='hidden' name='kaunisnimi' value='filename.htm'>";
				echo "<input type='hidden' name='tmpfilenimi' value='filename.htm'>";
				echo "<td class='back'><input type='submit' value='" . t ( "L‰het‰" ) . "'></td></tr></form>";
				echo "</table><br>";
			}
		}
		echo "<br><br><hr>";
	
	}
	
	if ($lopetus == "") {
		//K‰ytt‰liittym‰
		if (! isset ( $kka ))
			$kka = date ( "m", mktime ( 0, 0, 0, date ( "m" ) - 1, date ( "d" ), date ( "Y" ) ) );
		if (! isset ( $vva ))
			$vva = date ( "Y", mktime ( 0, 0, 0, date ( "m" ) - 1, date ( "d" ), date ( "Y" ) ) );
		if (! isset ( $ppa ))
			$ppa = date ( "d", mktime ( 0, 0, 0, date ( "m" ) - 1, date ( "d" ), date ( "Y" ) ) );
		if (! isset ( $kkl ))
			$kkl = date ( "m" );
		if (! isset ( $vvl ))
			$vvl = date ( "Y" );
		if (! isset ( $ppl ))
			$ppl = date ( "d" );
		
		echo "<br>\n\n\n";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='go'>";
		
		$query = "	SELECT *
						FROM yhtio
						WHERE konserni='$yhtiorow[konserni]' and konserni != ''";
		$result = mysql_query ( $query ) or pupe_error ( $query );
		
		// voidaan valita listaukseen useita konserniyhti‰it‰, jos k‰ytt‰j‰ll‰ on "P‰IVITYS" oikeus t‰h‰n raporttiin
		if (mysql_num_rows ( $result ) > 0 and $oikeurow ['paivitys'] != "") {
			echo "<table>";
			echo "<tr>";
			echo "<th>" . t ( "Valitse yhti‰" ) . "</th>";
			
			if (! isset ( $yhtiot ))
				$yhtiot = array ();
			
			while ( $row = mysql_fetch_array ( $result ) ) {
				$sel = "";
				
				if ($kukarow ["yhtio"] == $row ["yhtio"] and count ( $yhtiot ) == 0)
					$sel = "CHECKED";
				if (in_array ( $row ["yhtio"], $yhtiot ))
					$sel = "CHECKED";
				
				echo "<td><input type='checkbox' name='yhtiot[]' onchange='submit()' value='$row[yhtio]' $sel>$row[nimi]</td>";
			}
			
			echo "</tr>";
			echo "</table><br>";
		} else {
			echo "<input type='hidden' name='yhtiot[]' value='$kukarow[yhtio]'>";
		}
		
		echo "<table><tr>";
		
		echo "<th>" . t ( "Valitse tuoteosastot" ) . ":</th>";
		echo "<th>" . t ( "Valitse tuoteryhm‰t" ) . ":</th></tr>";
		
		echo "<tr>";
		echo "<td valign='top'>";
		
		// n‰ytet‰‰n soveltuvat osastot
		// tehd‰‰n avainsana query
		$res2 = t_avainsana ( "OSASTO" );
		
		echo "<select name='mul_osasto[]' multiple='TRUE' size='10' style='width:100%;'>";
		
		$mul_check = '';
		if ($mul_osasto != "") {
			if (in_array ( "PUPEKAIKKIMUUT", $mul_osasto )) {
				$mul_check = 'SELECTED';
			}
		}
		echo "<option value='PUPEKAIKKIMUUT' $mul_check>" . t ( "Ei tuoteosastoa" ) . "</option>";
		
		while ( $rivi = mysql_fetch_array ( $res2 ) ) {
			$mul_check = '';
			if ($mul_osasto != "") {
				if (in_array ( $rivi ['selite'], $mul_osasto )) {
					$mul_check = 'SELECTED';
				}
			}
			
			echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
		}
		
		echo "</select>";
		
		echo "</td>";
		echo "<td valign='top' class='back'>";
		
		// n‰ytet‰‰n soveltuvat tryt
		// tehd‰‰n avainsana query
		$res2 = t_avainsana ( "TRY" );
		
		echo "<select name='mul_try[]' multiple='TRUE' size='10' style='width:100%;'>";
		
		$mul_check = '';
		if ($mul_try != "") {
			if (in_array ( "PUPEKAIKKIMUUT", $mul_try )) {
				$mul_check = 'SELECTED';
			}
		}
		echo "<option value='PUPEKAIKKIMUUT' $mul_check>" . t ( "Ei tuoteryhm‰‰" ) . "</option>";
		
		while ( $rivi = mysql_fetch_array ( $res2 ) ) {
			$mul_check = '';
			if ($mul_try != "") {
				if (in_array ( $rivi ['selite'], $mul_try )) {
					$mul_check = 'SELECTED';
				}
			}
			
			echo "<option value='$rivi[selite]' $mul_check>$rivi[selite] - $rivi[selitetark]</option>";
		}
		
		echo "</select>";
		echo "</td>";
		echo "</tr>";
		echo "</table><br>\n";
		
		if ($nollapiilo != '')
			$nollapiilochk = "CHECKED";
		if ($halyraja != '')
			$halyrajachk = "CHECKED";
		if ($poispiilo != '')
			$poispiilochk = "CHECKED";
		if ($yhtioittain != '')
			$yhtioittainchk = "CHECKED";
		
		echo "<table>
				<tr>
				<tr>
				<th>" . t ( "Piilota nollarivit" ) . "</th>
				<td><input type='checkbox' name='nollapiilo' $nollapiilochk></td>
				</tr>
				<tr>
				<th>" . t ( "N‰yt‰ h‰lyrajat" ) . "</th>
				<td><input type='checkbox' name='halyraja' $halyrajachk></td><td><input type='text' name='raja' $raja></td>
				<th>" . t ( "Anna raja-arvo" ) . "</th>
				</tr>
				
			
				<tr>
				<th>" . t ( "Piilota poistetut tuotteet" ) . "</th>
				<td><input type='checkbox' name='poispiilo' $poispiilochk></td>
				</tr>
				<tr>
				<th>" . t ( "N‰yt‰ saldot yhtiˆitt‰in" ) . "</th>
				<td><input type='checkbox' name='yhtioittain' $yhtioittainchk></td>
				</tr>
				</table><br>";
		
		// p‰iv‰m‰‰r‰rajaus
		echo "<table>";
		echo "<tr>
				<th>" . t ( "syˆt‰ alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)" ) . "</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td>
				</tr>\n
				<tr><th>" . t ( "syˆt‰ loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)" ) . "</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td>
				</tr>\n";
		echo "</table><br>";
		
		echo "<br>";
		echo "<input type='submit' name ='go' value='" . t ( "Aja raportti" ) . "'>";
		echo "</form>";
	}
	
	require ("../inc/footer.inc");
}

?>
