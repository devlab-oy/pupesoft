<?php
	require ("inc/parametrit.inc");
	
	echo "<font class='head'>Tekstiviestin l‰hetys</font><hr>";

	if($tee == 'laheta') {
		
		$kotinum = ereg_replace ("-", "", $kotinum);
		echo "<br>";
		
		if(strlen($kotinum) > 0 && is_numeric($kotinum) && strlen($teksti) > 0){
			$pala = exec ("echo '".$teksti."'"."  | gammu --sendsms TEXT ".$kotinum);
			echo "Viesti l‰hetetty!<br>$pala<br>"; 
			
			$kotinum = '';
			$teksti = '';
		
		}
		else{
			echo "<font class='error'>VIRHE: Viesti tai puhelinnumero puuttui!</font><br>";	
		}
				
		$tee = '';
	}


	if($tee != 'laheta') {
		echo "<script language=\"javascript\">
				function TextMaxLength(evt, field) {
					charCode = evt.keyCode;
					if ((field.length >= 160) && (evt.keyCode == 0)) {
						alert('Tekstiviestiin mahtuu enint‰‰n 160 merkki‰!' + evt.keyCode);
						return false;
					}
				}
				</script>";
		
		echo "	<br><br><table>";
		echo "	<form name='form' action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value = 'laheta'>
				<tr><th>Puh.</th><td><input type='text' size='20' name='kotinum' value='$kotinum'></td></tr>
				<tr><th>Viesti</th><td><textarea name='teksti' cols='45' rows='6' wrap='soft' 
				onkeypress=\"return TextMaxLength(event, document.form.teksti.value);\">$teksti</textarea></td></tr>";
		echo "	</table>";
		
		echo "<br><input type='submit' value = 'L‰het‰'>";
		
		echo "</form>";
	}
?>