<?php

require ("../inc/parametrit.inc");
		include("../phpqrcode/phpqrcode.php");
		include('../phpqrcode/qrconfig.php');


	echo "<font class='head'>" . t ( "QR-koodit" ) . "</font><hr>";
	
		$query = "	SELECT tuote.tuoteno
					FROM tuote
					WHERE yhtio = 'matu'
					AND concat( '', tuoteno *1 ) = tuoteno, and (hinnastoon='W' or tahtituote='S')
					ORDER by tuoteno;"
					
							;
		$result = mysql_query ( $query ) or pupe_error ( $query );
		
		
		if (mysql_num_rows ( $result ) > 0) {
			echo "<font class='head'>" .  ( "Tuotteita " ).mysql_num_rows ( $result ) . "</font><hr>";
			
				$tempDir = '/var/www/html/pupesoft/kuvapankki/matu/tuote/qr/';
				
			while ( $row = mysql_fetch_array ( $result ) ) {
				$tuoteno = $row[0];
				
				$codeContents = 'http://www.maisematukku.fi/tuotteet/'.$tuoteno.'.pdf';
			    $fileName = $tuoteno.'.png';
			    $pngAbsoluteFilePath = $tempDir.$fileName;	
				echo "<font class='head'>" .  $pngAbsoluteFilePath . "</font><hr>";			  

		        QRcode::png($codeContents, $pngAbsoluteFilePath, QR_ECLEVEL_L, 3);		    

			} 
		echo "<br>THE END<br><hr>";	
	
		}

		
	
	echo "<br>THE END<br><hr>";
	require ("../inc/footer.inc");
	
		
?>