<?php

if (isset ( $_POST ["tee"] )) {
	if ($_POST ["tee"] == 'lataa_tiedosto')
		$lataa_tiedosto = 1;
	if ($_POST ["kaunisnimi"] != '')
		$_POST ["kaunisnimi"] = str_replace ( "/", "", $_POST ["kaunisnimi"] );
}

require ("../inc/parametrit.inc");


	echo "<font class='head'>" . t ( "Varastosaldojen siirto nettiin" ) . "</font><hr>";

		
		echo "<br>\n\n\n";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='go'>";
		
		if ($go == "Aja saldot nettiin"){
		$query = "	TRUNCATE varasto";
		$result = mysql_query ( $query ) or pupe_error ( $query );
		$query = "INSERT INTO varasto SELECT * FROM varasto_view";
		$result = mysql_query ( $query ) or pupe_error ( $query );
		$query = "DELETE from pupesoft.varasto WHERE status='P' and saldo<1 ";
		$result = mysql_query ( $query ) or pupe_error ( $query );
		$query = "UPDATE pupesoft.varasto SET saldo=0 WHERE saldo<0";
		$result = mysql_query ( $query ) or pupe_error ( $query );
		
		}

		echo "<input type='submit' name ='go' value='Aja saldot nettiin'>";
		echo "</form>";

		echo "<br>";echo "<p>";
	

		
		echo "<br>\n\n\n";
		echo "<form method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='tee' value='go'>";
		
		if ($go == "Aja Kasvidata www-sivuille"){
		$query = "	TRUNCATE siirtoforal";
		$result = mysql_query ( $query ) or pupe_error ( $query );
		$query = "	INSERT INTO siirtoforal SELECT * FROM siirtoforal_view";
		$result = mysql_query ( $query ) or pupe_error ( $query );
		}

		echo "<input type='submit' name ='go' value='Aja Kasvidata www-sivuille'>";
		echo "</form>";
	
	
	require ("../inc/footer.inc");

?>
